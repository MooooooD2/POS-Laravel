<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private StockService $stockService, private SequenceService $sequenceService)
    {
    }

    /**
     * Create a new sale invoice with optional tax support.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoiceNumber = $this->sequenceService->next('invoice', 'INV');

            // Calculate subtotal
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $discount = $data['discount'] ?? 0;

            // Tax calculation
            $taxEnabled  = Setting::get('tax_enabled', false);
            $taxRate     = (float) Setting::get('tax_rate', 0);
            $taxInclusive = Setting::get('tax_inclusive', false);

            $taxAmount = 0;
            if ($taxEnabled && $taxRate > 0) {
                $taxableAmount = $subtotal - $discount;
                $taxAmount = $taxInclusive
                    ? $taxableAmount - ($taxableAmount / (1 + $taxRate / 100))
                    : $taxableAmount * ($taxRate / 100);
                $taxAmount = round($taxAmount, 2);
            }

            $finalTotal = $subtotal - $discount + ($taxInclusive ? 0 : $taxAmount);

            // Validate stock before any DB writes
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                if (!$product || $product->quantity < $item['quantity']) {
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $item['product_name']]));
                }
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'total'          => $subtotal,
                'discount'       => $discount,
                'tax_amount'     => $taxAmount,
                'tax_rate'       => $taxEnabled ? $taxRate : 0,
                'final_total'    => $finalTotal,
                'payment_method' => $data['payment_method'],
                'cashier_id'     => Auth::id(),
                'cashier_name'   => Auth::user()->full_name,
                'status'         => 'completed',
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'cost_price'   => $product->cost_price,
                    'subtotal'     => $item['price'] * $item['quantity'],
                ]);

                $this->stockService->deductStock(
                    $product,
                    $item['quantity'],
                    'sale',
                    __('pos.sale_deduction'),
                    $invoice->id
                );
            }

            return $invoice->load('items');
        });
    }

    /**
     * Search product by barcode (exact) or name (fuzzy).
     * Returns a single Product for barcode, Collection for name search.
     */
    public function searchProduct(string $query, bool $exactBarcode = false): Product|Collection|null
    {
        $query = trim($query);

        if ($exactBarcode) {
            return Product::where('barcode', $query)->first();
        }

        // Exact barcode match takes priority
        $byBarcode = Product::where('barcode', $query)->first();
        if ($byBarcode) {
            return $byBarcode;
        }

        // Fuzzy name search
        return Product::where('name', 'like', "%{$query}%")->get();
    }

    /**
     * Get invoice by number with items.
     */
    public function getByNumber(string $number): ?Invoice
    {
        return Invoice::with('items')->where('invoice_number', $number)->first();
    }

    /**
     * Return the remaining (un-returned) quantities for each invoice item.
     */
    public function getReturnableItems(Invoice $invoice): array
    {
        $invoice->loadMissing('items');

        $returnedQuantities = \App\Models\ReturnItem::whereHas(
            'salesReturn',
            fn($q) => $q->where('invoice_id', $invoice->id)->where('status', 'completed')
        )
            ->selectRaw('product_id, SUM(quantity) as total_returned')
            ->groupBy('product_id')
            ->pluck('total_returned', 'product_id');

        $returnableItems = [];
        foreach ($invoice->items as $item) {
            $returned  = $returnedQuantities[$item->product_id] ?? 0;
            $remaining = $item->quantity - $returned;
            if ($remaining > 0) {
                $returnableItems[] = [
                    'product_id'    => $item->product_id,
                    'product_name'  => $item->product_name,
                    'original_qty'  => $item->quantity,
                    'returned_qty'  => $returned,
                    'returnable_qty'=> $remaining,
                    'price'         => $item->price,
                ];
            }
        }

        return $returnableItems;
    }
}
