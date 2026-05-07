<?php
namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private StockService $stockService) {}

    /**
     * #3 السعر يُحسب من قاعدة البيانات فقط — لا من المستخدم
     * #4 Transaction كامل
     * #5 Lock لمنع Race Condition على المخزون
     * #6 التحقق من الكمية قبل الخصم
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoiceNumber = SequenceService::next('invoice', Setting::get('invoice_prefix', 'INV'));

            // #5 قفل جميع المنتجات في الفاتورة دفعة واحدة لمنع Race Condition
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products   = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // #6 التحقق من توفر الكميات قبل أي خصم
            $allowNeg = Setting::get('allow_negative_stock', false);
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) {
                    throw new \Exception(__('pos.product_not_found_id', ['id' => $item['product_id']]));
                }
                if (!$allowNeg && $product->quantity < $item['quantity']) {
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $product->name]));
                }
            }

            // #3 حساب الإجمالي من أسعار DB — تجاهل أي سعر يُرسله المستخدم
            $total = 0;
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                $total  += $product->price * $item['quantity'];
            }

            // الخصم: لا يتجاوز الإجمالي
            $discount      = min((float)($data['discount'] ?? 0), $total);
            $afterDiscount = $total - $discount;

            // حساب الضريبة من الإعدادات — لا من المستخدم
            $taxEnabled   = (bool) Setting::get('tax_enabled', false);
            $taxRate      = $taxEnabled ? (float) Setting::get('tax_rate', 0) : 0;
            $taxInclusive = (bool) Setting::get('tax_inclusive', false);

            $taxAmount = 0;
            if ($taxEnabled && $taxRate > 0) {
                $taxAmount = $taxInclusive
                    ? $afterDiscount - ($afterDiscount / (1 + $taxRate / 100))
                    : $afterDiscount * ($taxRate / 100);
            }

            $finalTotal = $afterDiscount + ($taxInclusive ? 0 : $taxAmount);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'total'          => round($total, 2),
                'discount'       => round($discount, 2),
                'tax_rate'       => $taxRate,
                'tax_amount'     => round($taxAmount, 2),
                'final_total'    => round($finalTotal, 2),
                'payment_method' => $data['payment_method'],
                'cashier_id'     => Auth::id(),
                'cashier_name'   => Auth::user()->full_name,
                'status'         => 'completed',
                'date'           => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $item['quantity'],
                    'price'        => $product->price,          // السعر من DB
                    'subtotal'     => round($product->price * $item['quantity'], 2),
                ]);

                // #16-20 خصم المخزون مع تسجيل الحركة والمستخدم
                $this->stockService->deductStock(
                    $product,
                    $item['quantity'],
                    'sale',
                    __('pos.sale_deduction'),
                    $invoice->id,
                    'invoice'
                );
            }

            return $invoice->load('items');
        });
    }

    public function searchProduct(string $query, bool $exact = false): mixed
    {
        $query = trim(strip_tags($query));
        if (!$query) return collect();

        if ($exact) return Product::where('barcode', $query)->where('quantity', '>', 0)->first();

        $exact = Product::where('barcode', $query)->first();
        if ($exact) return collect([$exact]);

        // #13 استخدام bindings — لا SQL injection
        return Product::where('name', 'like', '%' . $query . '%')
            ->orWhere('barcode', 'like', '%' . $query . '%')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();
    }

    public function getByNumber(string $number): ?Invoice
    {
        return Invoice::with('items')->where('invoice_number', $number)->first();
    }

    public function getReturnableItems(Invoice $invoice): array
    {
        $returned = \App\Models\ReturnItem::whereHas(
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
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name,
                'original_qty'   => $item->quantity,
                'returned_qty'   => $ret,
                'returnable_qty' => $item->quantity - $ret,
                'price'          => $item->price,
            ];
        })->values()->toArray();
    }
}
