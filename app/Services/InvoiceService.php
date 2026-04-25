<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Create a new sale invoice with tax support
     * إنشاء فاتورة مبيعات جديدة مع دعم الضريبة
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Atomic invoice numbering — آمن ضد التزامن
            $invoiceNumber = SequenceService::next('invoice', Setting::get('invoice_prefix', 'INV'));

            $total = 0;
            foreach ($data['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            $discount = $data['discount'] ?? 0;
            $afterDiscount = $total - $discount;

            // Tax calculation - حساب الضريبة
            $taxEnabled = Setting::get('tax_enabled', false);
            $taxRate = $taxEnabled ? (float) Setting::get('tax_rate', 0) : 0;
            $taxInclusive = Setting::get('tax_inclusive', false);

            $taxAmount = 0;
            if ($taxEnabled && $taxRate > 0) {
                if ($taxInclusive) {
                    $taxAmount = $afterDiscount - ($afterDiscount / (1 + $taxRate / 100));
                } else {
                    $taxAmount = $afterDiscount * ($taxRate / 100);
                }
            }

            $finalTotal = $afterDiscount + ($taxInclusive ? 0 : $taxAmount);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'total' => $total,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => round($taxAmount, 2),
                'final_total' => round($finalTotal, 2),
                'payment_method' => $data['payment_method'],
                'cashier_id' => Auth::user()->id,
                'cashier_name' => Auth::user()->full_name,
                'status' => 'completed',
                'date' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $allowNeg = Setting::get('allow_negative_stock', false);

                if (!$product || (!$allowNeg && $product->quantity < $item['quantity'])) {
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $item['product_name']]));
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
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
     * Search product — returns single item on exact barcode, array on name search
     * البحث: نتيجة واحدة للباركود الدقيق، قائمة للاسم
     */
    public function searchProduct(string $query, bool $exactBarcode = false): mixed
    {
        $query = trim($query);

        if ($exactBarcode) {
            return Product::where('barcode', $query)->first();
        }

        $exact = Product::where('barcode', $query)->first();
        if ($exact)
            return collect([$exact]);

        return Product::where('name', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$query}%")
            ->limit(10)
            ->get();
    }

    public function getByNumber(string $number): ?Invoice
    {
        return Invoice::with('items')->where('invoice_number', $number)->first();
    }

    public function getReturnableItems(Invoice $invoice): array
    {
        $returnedQuantities = \App\Models\ReturnItem::whereHas(
            'salesReturn',
            fn($q) =>
            $q->where('invoice_id', $invoice->id)->where('status', 'completed')
        )->selectRaw('product_id, SUM(quantity) as total_returned')
            ->groupBy('product_id')
            ->pluck('total_returned', 'product_id');

        $returnableItems = [];
        foreach ($invoice->items as $item) {
            $returned = $returnedQuantities[$item->product_id] ?? 0;
            $remaining = $item->quantity - $returned;
            if ($remaining > 0) {
                $returnableItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'original_qty' => $item->quantity,
                    'returned_qty' => $returned,
                    'returnable_qty' => $remaining,
                    'price' => $item->price,
                ];
            }
        }
        return $returnableItems;
    }
}