<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        private StockService    $stockService,
        private SequenceService $sequenceService
    ) {}

    /**
     * Process a sales return.
     * Validates quantities against the original invoice before committing.
     */
    public function processReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            /** @var Invoice $invoice */
            $invoice = Invoice::with('items')->lockForUpdate()->findOrFail($data['invoice_id']);

            // Pre-validate: ensure no item exceeds its returnable quantity
            $returnedQtyMap = ReturnItem::whereHas(
                'salesReturn',
                fn($q) => $q->where('invoice_id', $invoice->id)->where('status', 'completed')
            )
                ->selectRaw('product_id, SUM(quantity) as total_returned')
                ->groupBy('product_id')
                ->pluck('total_returned', 'product_id');

            foreach ($data['items'] as $item) {
                $invoiceItem = $invoice->items->firstWhere('product_id', $item['product_id']);
                if (!$invoiceItem) {
                    throw new \Exception(__('pos.item_not_in_invoice', ['name' => $item['product_name']]));
                }

                $alreadyReturned = $returnedQtyMap[$item['product_id']] ?? 0;
                $maxReturnable   = $invoiceItem->quantity - $alreadyReturned;

                if ($item['quantity'] > $maxReturnable) {
                    throw new \Exception(__('pos.return_exceeds_quantity', ['name' => $item['product_name']]));
                }
            }

            $returnNumber = $this->sequenceService->next('sales_return', 'RET');
            $totalAmount  = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

            $return = SalesReturn::create([
                'return_number'     => $returnNumber,
                'invoice_id'        => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'customer_name'     => $data['customer_name'] ?? null,
                'total_amount'      => $totalAmount,
                'reason'            => $data['reason'] ?? null,
                'status'            => 'completed',
                'return_date'       => now()->toDateString(),
                'processed_by'      => Auth::id(),
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

                /** @var Product|null $product */
                $product = Product::find($item['product_id']);
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
}
