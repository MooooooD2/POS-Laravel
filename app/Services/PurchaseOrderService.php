<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private StockService     $stockService,
        private SequenceService  $sequenceService
    ) {}

    /**
     * Create a purchase order and record the supplier debt.
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $poNumber = $this->sequenceService->next('purchase_order', 'PO');

            $totalAmount = collect($data['items'])->sum(fn($i) => $i['cost_price'] * $i['quantity']);
            $discount    = $data['discount'] ?? 0;
            $finalAmount = $totalAmount - $discount;

            /** @var Supplier $supplier */
            $supplier = Supplier::findOrFail($data['supplier_id']);

            $po = PurchaseOrder::create([
                'po_number'       => $poNumber,
                'supplier_id'     => $supplier->id,
                'supplier_name'   => $supplier->name,
                'total_amount'    => $totalAmount,
                'discount'        => $discount,
                'final_amount'    => $finalAmount,
                'status'          => 'pending',
                'order_date'      => $data['order_date'],
                'expected_date'   => $data['expected_date'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'created_by'      => Auth::id(),
                'created_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'po_id'         => $po->id,
                    'product_id'    => $item['product_id'] ?? null,
                    'product_name'  => $item['product_name'],
                    'quantity'      => $item['quantity'],
                    'received_quantity' => 0,
                    'cost_price'    => $item['cost_price'],
                    'selling_price' => $item['selling_price'] ?? null,
                    'subtotal'      => $item['cost_price'] * $item['quantity'],
                ]);
            }

            $this->recordSupplierDebt($supplier->id, $po->id, $poNumber, $finalAmount);

            return $po->load('items');
        });
    }

    /**
     * Mark received quantities and update stock / product pricing.
     */
    public function receivePurchaseOrder(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems) {
            if ($po->status === 'received') {
                throw new \Exception(__('pos.po_already_received'));
            }

            foreach ($receivedItems as $item) {
                /** @var PurchaseOrderItem|null $poItem */
                $poItem = PurchaseOrderItem::lockForUpdate()->find($item['item_id']);
                if (!$poItem) {
                    continue;
                }

                $canReceive  = $poItem->quantity - $poItem->received_quantity;
                $receivedQty = min((int) $item['received_quantity'], $canReceive);
                if ($receivedQty <= 0) {
                    continue;
                }

                $poItem->increment('received_quantity', $receivedQty);

                $product = $poItem->product;
                if ($product) {
                    $updates = [];
                    if (isset($item['cost_price']) && $item['cost_price'] > 0) {
                        $updates['cost_price'] = $item['cost_price'];
                    }
                    if (isset($item['selling_price']) && $item['selling_price'] > 0) {
                        $updates['price'] = $item['selling_price'];
                    }
                    if ($updates) {
                        $product->update($updates);
                    }

                    $this->stockService->addStock(
                        $product,
                        $receivedQty,
                        __('pos.purchase_receipt', ['po' => $po->po_number]),
                        $po->id
                    );
                }
            }

            // Refresh and recalculate status
            $po->refresh();
            $allReceived = $po->items->every(fn($i) => $i->received_quantity >= $i->quantity);
            $anyReceived = $po->items->some(fn($i) => $i->received_quantity > 0);

            $po->update([
                'status'        => $allReceived ? 'received' : ($anyReceived ? 'partial' : 'pending'),
                'received_date' => $allReceived ? now() : null,
            ]);

            return $po->load('items');
        });
    }

    /**
     * Append a debit entry to the supplier ledger.
     */
    private function recordSupplierDebt(int $supplierId, int $poId, string $poNumber, float $amount): void
    {
        $lastBalance = SupplierAccount::where('supplier_id', $supplierId)
            ->latest('id')
            ->lockForUpdate()
            ->value('balance') ?? 0;

        SupplierAccount::create([
            'supplier_id'      => $supplierId,
            'transaction_type' => 'purchase_order',
            'reference_id'     => $poId,
            'reference_number' => $poNumber,
            'debit'            => $amount,
            'credit'           => 0,
            'balance'          => $lastBalance + $amount,
            'notes'            => __('pos.po_debt_note', ['po' => $poNumber]),
            'created_by'       => Auth::id(),
        ]);
    }
}
