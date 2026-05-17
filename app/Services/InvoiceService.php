<?php
namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Jobs\SubmitInvoiceToETA;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\ReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        private StockService               $stockService,
        private ProductRepositoryInterface $productRepo,
        private SettingRepositoryInterface $settingRepo,
        private CustomerService            $customerService,
    ) {}

    public function createInvoice(array $data): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($data) {
            $invoiceNumber = SequenceService::next('invoice', $this->settingRepo->get('invoice_prefix', 'INV'));

            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products   = $this->productRepo->lockForUpdate($productIds);

            $allowNeg = $this->settingRepo->get('allow_negative_stock', false);
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) {
                    throw new \Exception(__('pos.product_not_found_id', ['id' => $item['product_id']]));
                }
                if (!$allowNeg && $product->quantity < $item['quantity']) {
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $product->name]));
                }
            }

            $total = 0;
            foreach ($data['items'] as $item) {
                $total += $products->get($item['product_id'])->price * $item['quantity'];
            }

            $maxDiscountPercent = (float) $this->settingRepo->get(
                'max_discount_percent',
                config('security.invoice.max_discount_percent', 20)
            );
            $requestedDiscount  = (float) ($data['discount'] ?? 0);
            $maxAllowedDiscount = $total * ($maxDiscountPercent / 100);

            if ($requestedDiscount > $maxAllowedDiscount) {
                Log::channel('audit')->warning('invoice.discount_cap_exceeded', [
                    'user_id'            => Auth::id(),
                    'username'           => Auth::user()->username,
                    'requested_discount' => $requestedDiscount,
                    'max_allowed'        => $maxAllowedDiscount,
                    'total'              => $total,
                    'ip'                 => request()->ip(),
                    'timestamp'          => now()->toIso8601String(),
                ]);
                throw new \Exception(__('pos.discount_exceeds_limit', ['max' => $maxDiscountPercent]));
            }

            $discount      = min($requestedDiscount, $total, $maxAllowedDiscount);
            $afterDiscount = $total - $discount;

            // Loyalty point redemption (applied as additional discount)
            $loyaltyPointsUsed = 0;
            $loyaltyDiscount   = 0.0;
            if (!empty($data['redeem_loyalty_points']) && !empty($data['customer_id'])) {
                $customer = Customer::lockForUpdate()->find($data['customer_id']);
                if ($customer) {
                    $pointsToRedeem    = (int) $data['redeem_loyalty_points'];
                    $loyaltyDiscount   = $this->customerService->redeemLoyaltyPoints($customer, $pointsToRedeem);
                    $loyaltyDiscount   = min($loyaltyDiscount, $afterDiscount);
                    $loyaltyPointsUsed = $pointsToRedeem;
                    $afterDiscount    -= $loyaltyDiscount;
                }
            }

            $taxEnabled   = (bool) $this->settingRepo->get('tax_enabled', false);
            $taxRate      = $taxEnabled ? (float) $this->settingRepo->get('tax_rate', 0) : 0;
            $taxInclusive = (bool) $this->settingRepo->get('tax_inclusive', false);

            $taxAmount = 0;
            if ($taxEnabled && $taxRate > 0) {
                $taxAmount = $taxInclusive
                    ? $afterDiscount - ($afterDiscount / (1 + $taxRate / 100))
                    : $afterDiscount * ($taxRate / 100);
            }

            $finalTotal = $afterDiscount + ($taxInclusive ? 0 : $taxAmount);

            $isSplit       = !empty($data['payments']);
            $paymentMethod = $isSplit ? $data['payments'][0]['method'] : $data['payment_method'];

            $cashReceived = null;
            $changeAmount = null;

            if (!$isSplit && $paymentMethod === 'cash') {
                $cashReceived = isset($data['cash_received']) && $data['cash_received'] > 0
                    ? round((float) $data['cash_received'], 2)
                    : round($finalTotal, 2);

                if ($cashReceived < round($finalTotal, 2)) {
                    throw new \Exception(__('pos.cash_received_insufficient', [
                        'total'    => round($finalTotal, 2),
                        'received' => $cashReceived,
                    ]));
                }

                $changeAmount = round($cashReceived - $finalTotal, 2);
            }

            if ($isSplit) {
                $paymentsTotal = collect($data['payments'])->sum('amount');
                if (abs($paymentsTotal - round($finalTotal, 2)) > 0.01) {
                    throw new \Exception(__('pos.payments_total_mismatch', [
                        'expected' => round($finalTotal, 2),
                        'received' => $paymentsTotal,
                    ]));
                }
            }

            $warehouseId = $data['warehouse_id'] ?? \App\Models\Warehouse::where('is_default', true)->value('id');

            $invoice = Invoice::create([
                'invoice_number'      => $invoiceNumber,
                'total'               => round($total, 2),
                'discount'            => round($discount, 2),
                'loyalty_points_used' => $loyaltyPointsUsed,
                'loyalty_discount'    => round($loyaltyDiscount, 2),
                'tax_rate'            => $taxRate,
                'tax_amount'          => round($taxAmount, 2),
                'final_total'         => round($finalTotal, 2),
                'cash_received'       => $cashReceived,
                'change_amount'       => $changeAmount,
                'payment_method'      => $paymentMethod,
                'is_split_payment'    => $isSplit,
                'customer_id'         => $data['customer_id'] ?? null,
                'branch_id'           => $data['branch_id'] ?? \App\Models\Branch::where('is_default', true)->value('id'),
                'warehouse_id'        => $warehouseId,
                'cashier_id'          => Auth::id(),
                'cashier_name'        => Auth::user()->full_name,
                'status'              => 'completed',
                'date'                => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                if ($product->track_batches) {
                    $allocations = $this->stockService->deductBatchStock(
                        $product, $item['quantity'], 'sale',
                        __('pos.sale_deduction'), $invoice->id, 'invoice', $warehouseId
                    );
                    // Create one InvoiceItem per batch allocation
                    foreach ($allocations as $alloc) {
                        InvoiceItem::create([
                            'invoice_id'   => $invoice->id,
                            'product_id'   => $product->id,
                            'product_name' => $product->name,
                            'quantity'     => $alloc['quantity'],
                            'price'        => $product->price,
                            'cost_price'   => $product->avg_cost > 0 ? $product->avg_cost : $product->cost_price,
                            'subtotal'     => round($product->price * $alloc['quantity'], 2),
                            'warehouse_id' => $warehouseId,
                            'batch_id'     => $alloc['batch_id'],
                        ]);
                    }
                } else {
                    InvoiceItem::create([
                        'invoice_id'   => $invoice->id,
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'quantity'     => $item['quantity'],
                        'price'        => $product->price,
                        'cost_price'   => $product->avg_cost > 0 ? $product->avg_cost : $product->cost_price,
                        'subtotal'     => round($product->price * $item['quantity'], 2),
                        'warehouse_id' => $warehouseId,
                    ]);

                    $this->stockService->deductLockedStock(
                        $product, $item['quantity'], 'sale',
                        __('pos.sale_deduction'), $invoice->id, 'invoice', $warehouseId
                    );
                }
            }

            if ($isSplit) {
                foreach ($data['payments'] as $payment) {
                    InvoicePayment::create([
                        'invoice_id' => $invoice->id,
                        'method'     => $payment['method'],
                        'amount'     => round((float) $payment['amount'], 2),
                        'reference'  => $payment['reference'] ?? null,
                    ]);
                }

                $creditAmount = collect($data['payments'])
                    ->where('method', 'credit')
                    ->sum('amount');

                if ($creditAmount > 0) {
                    $this->customerService->createInvoiceCharge($invoice, (float) $creditAmount);
                }
            } else {
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'method'     => $paymentMethod,
                    'amount'     => round($finalTotal, 2),
                ]);

                if ($paymentMethod === 'credit') {
                    $this->customerService->createInvoiceCharge($invoice);
                }
            }

            return $invoice->load(['items.product.unit', 'customer']);
        });

        // Earn loyalty points on the final paid amount (after all discounts)
        if (!empty($invoice->customer_id)) {
            $customer = Customer::find($invoice->customer_id);
            if ($customer) {
                $this->customerService->addLoyaltyPoints($customer, $invoice->final_total);
            }
        }

        if (config('eta.enabled')) {
            SubmitInvoiceToETA::dispatch($invoice->id);
        }

        app(\App\Services\WhatsAppService::class)->sendInvoice($invoice);
        app(\App\Services\WhatsAppService::class)->sendLargeInvoiceAlert($invoice);

        return $invoice;
    }

    public function searchProduct(string $query, bool $exact = false): mixed
    {
        return $this->productRepo->search($query, $exact);
    }

    public function getByNumber(string $number): ?Invoice
    {
        return Invoice::with(['items.product.unit'])->where('invoice_number', $number)->first();
    }

    public function getReturnableItems(Invoice $invoice): array
    {
        $returned = ReturnItem::whereHas(
            'salesReturn',
            fn($q) => $q->where('invoice_id', $invoice->id)->where('status', 'completed')
        )->selectRaw('product_id, SUM(quantity) as total_returned')
            ->groupBy('product_id')
            ->pluck('total_returned', 'product_id');

        return $invoice->items->filter(function ($item) use ($returned) {
            return ($item->quantity - ($returned[$item->product_id] ?? 0)) > 0;
        })->map(function ($item) use ($returned) {
            $ret = $returned[$item->product_id] ?? 0;
            return [
                'product_id'        => $item->product_id,
                'product_name'      => $item->product_name,
                'original_qty'      => $item->quantity,
                'returned_qty'      => $ret,
                'returnable_qty'    => $item->quantity - $ret,
                'price'             => $item->price,
                'unit_abbreviation' => $item->product?->unit?->abbreviation ?? $item->product?->unit?->name,
            ];
        })->values()->toArray();
    }
}
