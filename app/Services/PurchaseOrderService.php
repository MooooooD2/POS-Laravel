<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierAccount;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Create purchase order - إنشاء أمر شراء
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            // ✅ FIX: Atomic PO numbering
            $poNumber = SequenceService::next('purchase');

            $totalAmount = collect($data['items'])->sum(fn($i) => $i['cost_price'] * $i['quantity']);
            $discount = $data['discount'] ?? 0;
            $finalAmount = $totalAmount - $discount;

            $supplier = Supplier::find($data['supplier_id']);

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $data['supplier_id'],
                'supplier_name' => $supplier->name,
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::user()->id,
                'created_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'selling_price' => $item['selling_price'] ?? null,
                    'subtotal' => $item['cost_price'] * $item['quantity'],
                ]);
            }

            // Record in supplier account - تسجيل في حساب المورد
            $this->recordSupplierDebt($supplier->id, $po->id, $poNumber, $finalAmount);

            return $po->load('items');
        });
    }

    /**
     * Receive purchase order - استلام أمر الشراء
     */
    public function receivePurchaseOrder(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $item) {
                $poItem = PurchaseOrderItem::find($item['item_id']);
                if (!$poItem)
                    continue;

                $receivedQty = min($item['received_quantity'], $poItem->quantity - $poItem->received_quantity);
                if ($receivedQty <= 0)
                    continue;

                $poItem->increment('received_quantity', $receivedQty);

                $product = $poItem->product;
                if ($product) {
                    // Update cost price if changed - تحديث سعر التكلفة إذا تغير
                    if (isset($item['cost_price'])) {
                        $product->update(['cost_price' => $item['cost_price']]);
                    }
                    if (isset($item['selling_price'])) {
                        $product->update(['price' => $item['selling_price']]);
                    }

                    $this->stockService->addStock(
                        $product,
                        $receivedQty,
                        __('pos.purchase_receipt', ['po' => $po->po_number]),
                        $po->id
                    );
                }
            }

            // Update PO status - تحديث حالة أمر الشراء
            $po->refresh();
            $allReceived = $po->items->every(fn($i) => $i->received_quantity >= $i->quantity);
            $anyReceived = $po->items->some(fn($i) => $i->received_quantity > 0);

            $po->update([
                'status' => $allReceived ? 'received' : ($anyReceived ? 'partial' : 'pending'),
                'received_date' => $allReceived ? now() : null,
            ]);

            return $po->load('items');
        });
    }

    private function recordSupplierDebt(int $supplierId, int $poId, string $poNumber, float $amount): void
    {
        $lastEntry = SupplierAccount::where('supplier_id', $supplierId)->latest()->first();
        $lastBalance = $lastEntry ? $lastEntry->balance : 0;

        SupplierAccount::create([
            'supplier_id' => $supplierId,
            'transaction_type' => 'purchase_order',
            'reference_id' => $poId,
            'reference_number' => $poNumber,
            'debit' => $amount,
            'credit' => 0,
            'balance' => $lastBalance + $amount,
            'notes' => __('pos.po_debt_note', ['po' => $poNumber]),
            'created_by' => Auth::user()->id,
        ]);
    }
}
