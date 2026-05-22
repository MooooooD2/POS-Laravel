<?php
namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        private ProductRepositoryInterface       $productRepo,
        private StockMovementRepositoryInterface $movementRepo,
        private InventoryValuationService        $valuationService,
    ) {}

    // ── Public API ───────────────────────────────────────────────────────────

    public function addStock(
        Product $product,
        int $quantity,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual',
        ?float $unitCost = null,
        ?int $warehouseId = null,
        ?int $batchId = null
    ): void {
        DB::transaction(function () use ($product, $quantity, $reason, $referenceId, $referenceType, $unitCost, $warehouseId, $batchId) {
            $fresh = $this->productRepo->lockForUpdate([$product->id])->first();

            if ($unitCost !== null && $unitCost > 0) {
                $currentQty = $fresh->quantity;
                $currentAvg = (float) ($fresh->avg_cost ?? $fresh->cost_price ?? 0);
                $newQty     = $currentQty + $quantity;
                $newAvgCost = $newQty > 0
                    ? (($currentQty * $currentAvg) + ($quantity * $unitCost)) / $newQty
                    : $unitCost;

                $fresh->avg_cost  = round($newAvgCost, 4);
                $fresh->last_cost = round($unitCost, 4);
                $fresh->save();

                // Create FIFO/LIFO cost layer for this stock addition
                $this->valuationService->createLayer($fresh, $quantity, $unitCost, $referenceType, $referenceId, $warehouseId);
            }

            $fresh->increment('quantity', $quantity);
            $this->syncWarehouseStock($fresh->id, $warehouseId, $quantity);
            $this->logMovement($fresh, $quantity, 'add', $reason, $referenceId, $referenceType, $warehouseId, $batchId);
        });
    }

    /**
     * Deduct stock that was already locked by lockForUpdate.
     * Returns the unit cost consumed (WAC, FIFO, or LIFO depending on setting).
     */
    public function deductLockedStock(
        Product $lockedProduct,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual',
        ?int $warehouseId = null,
        ?int $batchId = null
    ): float {
        if ($lockedProduct->quantity < $quantity) {
            throw new \Exception(__('pos.insufficient_stock', ['name' => $lockedProduct->name]));
        }

        // Deduct from FIFO/LIFO cost layers and get the actual unit COGS
        $unitCost = $this->valuationService->deductLayers($lockedProduct, $quantity, $warehouseId);

        $lockedProduct->decrement('quantity', $quantity);
        $this->syncWarehouseStock($lockedProduct->id, $warehouseId, -$quantity);
        $this->logMovement($lockedProduct, $quantity, $type, $reason, $referenceId, $referenceType, $warehouseId, $batchId);

        return $unitCost;
    }

    public function deductStock(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual',
        ?int $warehouseId = null,
        ?int $batchId = null
    ): void {
        DB::transaction(function () use ($product, $quantity, $type, $reason, $referenceId, $referenceType, $warehouseId, $batchId) {
            $fresh = $this->productRepo->lockForUpdate([$product->id])->firstOrFail();

            if ($fresh->quantity < $quantity) {
                throw new \Exception(__('pos.insufficient_stock', ['name' => $fresh->name]));
            }

            $fresh->decrement('quantity', $quantity);
            $this->syncWarehouseStock($fresh->id, $warehouseId, -$quantity);
            $this->logMovement($fresh, $quantity, $type, $reason, $referenceId, $referenceType, $warehouseId, $batchId);
        });
    }

    /**
     * FEFO batch deduction — returns array of [batch_id, quantity] allocations.
     * Called by InvoiceService when product->track_batches is true.
     */
    public function deductBatchStock(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual',
        ?int $warehouseId = null
    ): array {
        $allocations = [];

        DB::transaction(function () use ($product, $quantity, $type, $reason, $referenceId, $referenceType, $warehouseId, &$allocations) {
            $fresh = $this->productRepo->lockForUpdate([$product->id])->firstOrFail();

            if ($fresh->quantity < $quantity) {
                throw new \Exception(__('pos.insufficient_stock', ['name' => $fresh->name]));
            }

            // FEFO: nearest expiry first, nulls last
            $batches = ProductBatch::where('product_id', $fresh->id)
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->fefo()
                ->lockForUpdate()
                ->get();

            // Decrement product total before logging so balance_after is accurate
            $fresh->decrement('quantity', $quantity);
            $this->syncWarehouseStock($fresh->id, $warehouseId, -$quantity);

            $remaining = $quantity;
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $take = min($batch->remaining_qty, $remaining);
                $batch->decrement('remaining_qty', $take);

                if ($batch->remaining_qty <= 0) {
                    $batch->update(['status' => 'exhausted']);
                }

                $allocations[] = ['batch_id' => $batch->id, 'quantity' => $take];
                $remaining -= $take;

                $this->logMovement($fresh, $take, $type, $reason, $referenceId, $referenceType, $batch->warehouse_id, $batch->id);
            }

            if ($remaining > 0) {
                throw new \Exception(__('pos.insufficient_batch_stock', ['name' => $fresh->name]));
            }
        });

        return $allocations;
    }

    public function adjustStock(Product $product, int $newQuantity, string $reason, ?int $warehouseId = null): void
    {
        DB::transaction(function () use ($product, $newQuantity, $reason, $warehouseId) {
            $fresh      = $this->productRepo->lockForUpdate([$product->id])->firstOrFail();
            $difference = $newQuantity - $fresh->quantity;

            $fresh->quantity = $newQuantity;
            $fresh->save();

            $this->syncWarehouseStock($fresh->id, $warehouseId, $difference);

            $type = $difference >= 0 ? 'adjustment_add' : 'adjustment_remove';
            $this->logMovement($fresh, abs($difference), $type, $reason, null, 'adjustment', $warehouseId);
        });
    }

    // ── Transfer movement logger (called by WarehouseService) ───────────────

    public function logTransferMovement(
        Product $product,
        int $quantity,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $transferId,
        ?int $batchId = null
    ): void {
        $this->movementRepo->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'quantity'       => $quantity,
            'balance_after'  => $product->quantity,
            'movement_type'  => 'transfer_out',
            'reference_type' => 'transfer',
            'reference_id'   => $transferId,
            'warehouse_id'   => $fromWarehouseId,
            'batch_id'       => $batchId,
            'reason'         => "Transfer to warehouse #{$toWarehouseId}",
            'employee_id'    => Auth::id(),
            'employee_name'  => Auth::user()?->full_name,
            'ip_address'     => request()->ip(),
        ]);

        $this->movementRepo->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'quantity'       => $quantity,
            'balance_after'  => $product->quantity,
            'movement_type'  => 'transfer_in',
            'reference_type' => 'transfer',
            'reference_id'   => $transferId,
            'warehouse_id'   => $toWarehouseId,
            'batch_id'       => $batchId,
            'reason'         => "Transfer from warehouse #{$fromWarehouseId}",
            'employee_id'    => Auth::id(),
            'employee_name'  => Auth::user()?->full_name,
            'ip_address'     => request()->ip(),
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function syncWarehouseStock(int $productId, ?int $warehouseId, int $delta): void
    {
        if ($delta === 0) return;

        $wid = $warehouseId ?? Warehouse::where('is_default', true)->value('id');
        if (!$wid) return;

        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $wid, 'product_id' => $productId],
            ['quantity' => 0, 'reserved_qty' => 0, 'min_stock' => 0]
        );

        if ($delta > 0) {
            WarehouseStock::where('warehouse_id', $wid)
                ->where('product_id', $productId)
                ->increment('quantity', $delta);
        } else {
            WarehouseStock::where('warehouse_id', $wid)
                ->where('product_id', $productId)
                ->decrement('quantity', abs($delta));
        }
    }

    public function logMovement(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId,
        string $referenceType,
        ?int $warehouseId = null,
        ?int $batchId = null
    ): void {
        $this->movementRepo->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'quantity'       => $quantity,
            'balance_after'  => $product->quantity,
            'movement_type'  => $type,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'warehouse_id'   => $warehouseId,
            'batch_id'       => $batchId,
            'reason'         => $reason,
            'employee_id'    => Auth::id(),
            'employee_name'  => Auth::user()?->full_name,
            'ip_address'     => request()->ip(),
        ]);
    }
}
