<?php
namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnService
{
    public function __construct(private StockService $stockService) {}

    /**
     * سيناريو 4: معالجة المرتجع مع تحديد طريقة رد المبلغ
     */
    public function processReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::with('items')->lockForUpdate()->findOrFail($data['invoice_id']);

            if ($invoice->status !== 'completed') {
                throw new \Exception(__('pos.invoice_not_completed'));
            }

            $returnableQtys = $this->getReturnableQuantities($invoice);

            foreach ($data['items'] as $item) {
                $max = $returnableQtys[$item['product_id']] ?? 0;
                if ($item['quantity'] <= 0 || $item['quantity'] > $max) {
                    throw new \Exception(__('pos.return_quantity_exceeded', [
                        'name' => $item['product_id'],
                        'max'  => $max,
                    ]));
                }
            }

            $returnNumber      = SequenceService::next('return');
            $invoiceItemPrices = $invoice->items->keyBy('product_id');

            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $invoiceItem  = $invoiceItemPrices->get($item['product_id']);
                $price        = $invoiceItem ? $invoiceItem->price : 0;
                $totalAmount += $price * $item['quantity'];
            }

            // سيناريو 4: تحديد طريقة رد المبلغ
            $refundMethod = $data['refund_method'] ?? 'cash';
            $refundAmount = round($totalAmount, 2);

            // التحقق من صحة طريقة الرد
            if (!in_array($refundMethod, ['cash', 'store_credit', 'exchange'])) {
                throw new \Exception('طريقة رد المبلغ غير صالحة.');
            }

            // لو استبدال، المبلغ المردود صفر
            if ($refundMethod === 'exchange') {
                $refundAmount = 0;
            }

            $return = SalesReturn::create([
                'return_number'     => $returnNumber,
                'invoice_id'        => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'customer_name'     => $data['customer_name'] ?? null,
                'total_amount'      => round($totalAmount, 2),
                'refund_method'     => $refundMethod,
                'refund_amount'     => $refundAmount,
                'reason'            => $data['reason'] ?? null,
                'status'            => 'completed',
                'return_date'       => now()->toDateString(),
                'processed_by'      => Auth::id(),
                'processed_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                $invoiceItem = $invoiceItemPrices->get($item['product_id']);
                $price       = $invoiceItem ? $invoiceItem->price : 0;

                ReturnItem::create([
                    'return_id'    => $return->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $invoiceItem?->product_name ?? '',
                    'quantity'     => $item['quantity'],
                    'price'        => $price,
                    'subtotal'     => round($price * $item['quantity'], 2),
                ]);

                // إرجاع المخزون في كل الحالات (نقدي / رصيد / استبدال)
                $product = Product::find($item['product_id']);
                if ($product) {
                    $this->stockService->addStock(
                        $product,
                        $item['quantity'],
                        __('pos.return_note', ['ret' => $returnNumber]),
                        $return->id,
                        'return'
                    );
                }
            }

            // تسجيل في الـ audit log مع طريقة الرد
            Log::channel('audit')->info('return.processed', [
                'return_number' => $returnNumber,
                'invoice'       => $invoice->invoice_number,
                'total'         => $totalAmount,
                'refund_method' => $refundMethod,
                'refund_amount' => $refundAmount,
                'user_id'       => Auth::id(),
                'timestamp'     => now()->toIso8601String(),
            ]);

            return $return->load('items');
        });
    }

    private function getReturnableQuantities(Invoice $invoice): array
    {
        $already = ReturnItem::whereHas(
            'salesReturn',
            fn($q) => $q->where('invoice_id', $invoice->id)->where('status', 'completed')
        )->selectRaw('product_id, SUM(quantity) as total_returned')
            ->groupBy('product_id')
            ->pluck('total_returned', 'product_id');

        $result = [];
        foreach ($invoice->items as $item) {
            $result[$item->product_id] = max(0, $item->quantity - ($already[$item->product_id] ?? 0));
        }
        return $result;
    }
}
