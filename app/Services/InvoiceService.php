<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    public function __construct(private StockService $stockService) {}

    /**
     * Create a new sale invoice - إنشاء فاتورة مبيعات جديدة
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(
                Invoice::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
            );

            // Calculate totals - حساب الإجماليات
            $total = 0;
            foreach ($data['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            $discount = $data['discount'] ?? 0;
            $finalTotal = $total - $discount;

            // Create invoice - إنشاء الفاتورة
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'total'          => $total,
                'discount'       => $discount,
                'final_total'    => $finalTotal,
                'payment_method' => $data['payment_method'],
                'cashier_id'     => Auth::user()->id,
                'cashier_name'   => Auth::user()->full_name,
                'status'         => 'completed',
            ]);

            // Create items & deduct stock - إنشاء العناصر وخصم المخزون
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || $product->quantity < $item['quantity']) {
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $item['product_name']]));
                }

                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'subtotal'     => $item['price'] * $item['quantity'],
                ]);

                $this->stockService->deductStock(
                    $product, $item['quantity'],
                    'sale', __('pos.sale_deduction'), $invoice->id
                );
            }

            return $invoice->load('items');
        });
    }

    /**
     * Search product by barcode or name - البحث عن منتج بالباركود أو الاسم
     */
    public function searchProduct(string $query): ?Product
    {
        return Product::where('barcode', $query)
            ->orWhere('name', 'like', "%{$query}%")
            ->first();
    }

    /**
     * Get invoice with items - الحصول على الفاتورة مع عناصرها
     */
    public function getByNumber(string $number): ?Invoice
    {
        return Invoice::with('items')->where('invoice_number', $number)->first();
    }

    /**
     * Get returnable items for an invoice - الحصول على العناصر القابلة للإرجاع
     */
    public function getReturnableItems(Invoice $invoice): array
    {
        $returnedQuantities = \App\Models\ReturnItem::whereHas('salesReturn', fn($q) =>
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
