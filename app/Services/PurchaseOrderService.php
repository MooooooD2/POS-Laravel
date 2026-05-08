<?php
namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderService
{
    public function __construct(private StockService $stockService) {}

    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $poNumber    = SequenceService::next('purchase');
            $totalAmount = collect($data['items'])->sum(fn($i) => $i['cost_price'] * $i['quantity']);
            $discount    = $data['discount'] ?? 0;
            $finalAmount = $totalAmount - $discount;
            $supplier    = Supplier::find($data['supplier_id']);

            $po = PurchaseOrder::create([
                'po_number'       => $poNumber,
                'supplier_id'     => $data['supplier_id'],
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
                    'po_id'          => $po->id,
                    'product_id'     => $item['product_id'],
                    'product_name'   => $item['product_name'],
                    'quantity'       => $item['quantity'],
                    'cost_price'     => $item['cost_price'],
                    'selling_price'  => $item['selling_price'] ?? null,
                    'subtotal'       => $item['cost_price'] * $item['quantity'],
                ]);
            }

            $this->recordSupplierDebt($supplier->id, $po->id, $poNumber, $finalAmount);
            return $po->load('items');
        });
    }

    /**
     * سيناريو 2: استلام البضاعة مع تسجيل الفرق بين المطلوب والمستلم فعلاً
     */
    public function receivePurchaseOrder(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems) {
            $hasDiscrepancy = false;

            foreach ($receivedItems as $item) {
                $poItem = PurchaseOrderItem::find($item['item_id']);
                if (!$poItem) continue;

                $requestedQty  = $poItem->quantity;
                $alreadyRcvd   = $poItem->received_quantity;
                $maxReceivable = $requestedQty - $alreadyRcvd;
                $receivedQty   = (int) $item['received_quantity'];

                // السماح باستلام أقل أو أكثر من المطلوب — مع تسجيل الفرق
                $actualQty   = max(0, $receivedQty);
                $discrepancy = $actualQty - $maxReceivable; // سالب = ناقص، موجب = زيادة

                if ($actualQty <= 0) continue;

                $poItem->update([
                    'received_quantity'  => $alreadyRcvd + $actualQty,
                    'discrepancy'        => $discrepancy,
                    'discrepancy_notes'  => $item['discrepancy_notes'] ?? null,
                ]);

                // تسجيل تحذير لو في فرق
                if ($discrepancy !== 0) {
                    $hasDiscrepancy = true;
                    Log::channel('audit')->warning('purchase_order.discrepancy', [
                        'po_number'     => $po->po_number,
                        'product'       => $poItem->product_name,
                        'requested'     => $requestedQty,
                        'received'      => $actualQty,
                        'discrepancy'   => $discrepancy,
                        'user_id'       => Auth::id(),
                        'timestamp'     => now()->toIso8601String(),
                    ]);
                }

                $product = $poItem->product;
                if ($product) {
                    if (isset($item['cost_price']))    $product->update(['cost_price' => $item['cost_price']]);
                    if (isset($item['selling_price'])) $product->update(['price' => $item['selling_price']]);

                    $this->stockService->addStock(
                        $product,
                        $actualQty,
                        __('pos.purchase_receipt', ['po' => $po->po_number]),
                        $po->id
                    );
                }
            }

            // تحديث حالة الأمر
            $po->refresh();
            $allReceived = $po->items->every(fn($i) => $i->received_quantity >= $i->quantity);
            $anyReceived = $po->items->some(fn($i)  => $i->received_quantity > 0);

            $po->update([
                'status'        => $allReceived ? 'received' : ($anyReceived ? 'partial' : 'pending'),
                'received_date' => $allReceived ? now() : null,
            ]);

            return $po->load('items');
        });
    }

    private function recordSupplierDebt(int $supplierId, int $poId, string $poNumber, float $amount): void
    {
        $lastEntry   = SupplierAccount::where('supplier_id', $supplierId)->lockForUpdate()->latest()->first();
        $lastBalance = $lastEntry ? $lastEntry->balance : 0;

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
