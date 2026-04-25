<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ReturnItem;
use App\Models\SalesReturn;
use App\Services\SequenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Process sales return with quantity validation
     * معالجة مرتجع المبيعات مع التحقق من الكميات
     */
    public function processReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::with('items')->findOrFail($data['invoice_id']);

            // ✅ FIX: Validate return quantities before processing
            $returnableQtys = $this->getReturnableQuantities($invoice);

            foreach ($data['items'] as $item) {
                $maxReturnable = $returnableQtys[$item['product_id']] ?? 0;

                if ($item['quantity'] <= 0) {
                    throw new \Exception(__('pos.return_quantity_invalid', ['name' => $item['product_name']]));
                }

                if ($item['quantity'] > $maxReturnable) {
                    throw new \Exception(__('pos.return_quantity_exceeded', [
                        'name' => $item['product_name'],
                        'max'  => $maxReturnable,
                    ]));
                }
            }

            // ✅ FIX: Atomic return numbering
            $returnNumber = SequenceService::next('return');

            $totalAmount = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

            $return = SalesReturn::create([
                'return_number'     => $returnNumber,
                'invoice_id'        => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'customer_name'     => $data['customer_name'] ?? null,
                'total_amount'      => $totalAmount,
                'reason'            => $data['reason'] ?? null,
                'status'            => 'completed',
                'return_date'       => now()->toDateString(),
                'processed_by'      => Auth::user()->id,
                'processed_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                ReturnItem::create([
                    'return_id'    => $return->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'subtotal'     => $item['price'] * $item['quantity'],
                ]);

                $product = \App\Models\Product::find($item['product_id']);
                if ($product) {
                    $this->stockService->addStock(
                        $product,
                        $item['quantity'],
                        __('pos.return_note', ['ret' => $returnNumber]),
                        $return->id
                    );
                }
            }

            return $return->load('items');
        });
    }

    /**
     * Get returnable quantities per product for an invoice
     * حساب الكميات القابلة للإرجاع لكل منتج في الفاتورة
     */
    private function getReturnableQuantities(Invoice $invoice): array
    {
        $alreadyReturned = ReturnItem::whereHas(
            'salesReturn',
            fn($q) => $q->where('invoice_id', $invoice->id)->where('status', 'completed')
        )->selectRaw('product_id, SUM(quantity) as total_returned')
            ->groupBy('product_id')
            ->pluck('total_returned', 'product_id');

        $returnable = [];
        foreach ($invoice->items as $item) {
            $returned = $alreadyReturned[$item->product_id] ?? 0;
            $returnable[$item->product_id] = max(0, $item->quantity - $returned);
        }

        return $returnable;
    }
}
