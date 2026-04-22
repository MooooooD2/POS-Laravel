<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Models\ReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Process sales return - معالجة مرتجع المبيعات
     */
    public function processReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::with('items')->findOrFail($data['invoice_id']);

            $returnNumber = 'RET-' . date('Ymd') . '-' . str_pad(
                SalesReturn::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $totalAmount = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);
            $return = SalesReturn::create([
                'return_number' => $returnNumber,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $data['customer_name'] ?? null,
                'total_amount' => $totalAmount,
                'reason' => $data['reason'] ?? null,
                'status' => 'completed',
                'return_date' => now()->toDateString(),
                'processed_by' => Auth::user()->id,
                'processed_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
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
}
