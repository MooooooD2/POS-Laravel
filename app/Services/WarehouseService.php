<?php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseService
{
    public function __construct(private StockService $stockService) {}

    // ── Warehouse CRUD ──────────────────────────────────────────────────────

    public function all(bool $activeOnly = false)
    {
        return Warehouse::with('branch:id,name,code')
            ->when($activeOnly, fn($q) => $q->where('is_active', true))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            if (!empty($data['is_default'])) {
                Warehouse::where('is_default', true)->update(['is_default' => false]);
            }
            return Warehouse::create($data);
        });
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            if (!empty($data['is_default'])) {
                Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            }
            $warehouse->update($data);
            return $warehouse->fresh();
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        if ($warehouse->is_default) {
            throw new \Exception(__('pos.cannot_delete_default_warehouse'));
        }
        if ($warehouse->stock()->where('quantity', '>', 0)->exists()) {
            throw new \Exception(__('pos.warehouse_has_stock'));
        }
        $warehouse->delete();
    }

    public function defaultId(): ?int
    {
        return Warehouse::where('is_default', true)->value('id');
    }

    // ── Stock per warehouse ──────────────────────────────────────────────────

    public function stockList(Warehouse $warehouse)
    {
        return WarehouseStock::with(['product' => fn($q) => $q->select('id','name','barcode','category','min_stock','unit_id')->with('unit:id,name,abbreviation')])
            ->where('warehouse_id', $warehouse->id)
            ->orderBy('product_id')
            ->get();
    }

    public function getOrCreateStock(int $warehouseId, int $productId): WarehouseStock
    {
        return WarehouseStock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['quantity' => 0, 'reserved_qty' => 0, 'min_stock' => 0]
        );
    }

    // ── Transfers ────────────────────────────────────────────────────────────

    public function createTransfer(array $data): WarehouseTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = WarehouseTransfer::create([
                'transfer_number'    => SequenceService::next('transfer', 'TRF'),
                'from_warehouse_id'  => $data['from_warehouse_id'],
                'to_warehouse_id'    => $data['to_warehouse_id'],
                'requested_by'       => Auth::id(),
                'status'             => 'pending',
                'notes'              => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                // Lock source warehouse stock
                $stock = WarehouseStock::where('warehouse_id', $data['from_warehouse_id'])
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $item['quantity']) {
                    $product = Product::find($item['product_id']);
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $product?->name ?? "#{$item['product_id']}"]));
                }

                // Reserve stock
                $stock->increment('reserved_qty', $item['quantity']);

                WarehouseTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id'  => $item['product_id'],
                    'batch_id'    => $item['batch_id'] ?? null,
                    'quantity'    => $item['quantity'],
                ]);
            }

            $transfer->update(['status' => 'in_transit']);
            Log::channel('audit')->info('warehouse.transfer_created', [
                'transfer_id' => $transfer->id,
                'user_id'     => Auth::id(),
            ]);

            return $transfer->load('items.product', 'fromWarehouse', 'toWarehouse');
        });
    }

    public function receiveTransfer(WarehouseTransfer $transfer): WarehouseTransfer
    {
        if ($transfer->status !== 'in_transit') {
            throw new \Exception(__('pos.transfer_not_in_transit'));
        }

        return DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                // Release reservation from source
                $srcStock = WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)->lockForUpdate()->first();
                if ($srcStock) {
                    $srcStock->decrement('reserved_qty', $item->quantity);
                    $srcStock->decrement('quantity', $item->quantity);
                    // Sync aggregate
                    Product::where('id', $item->product_id)->decrement('quantity', $item->quantity);
                }

                // Add to destination
                $dstStock = $this->getOrCreateStock($transfer->to_warehouse_id, $item->product_id);
                $dstStock->increment('quantity', $item->quantity);
                // Aggregate already decremented above — net is zero (transfer)

                // Log movements
                $product = Product::find($item->product_id);
                if ($product) {
                    $this->stockService->logTransferMovement(
                        $product, $item->quantity,
                        $transfer->from_warehouse_id, $transfer->to_warehouse_id,
                        $transfer->id, $item->batch_id
                    );
                }
            }

            $transfer->update([
                'status'      => 'received',
                'received_by' => Auth::id(),
                'received_at' => now(),
            ]);

            Log::channel('audit')->info('warehouse.transfer_received', [
                'transfer_id' => $transfer->id,
                'user_id'     => Auth::id(),
            ]);

            return $transfer->fresh('items.product');
        });
    }

    public function cancelTransfer(WarehouseTransfer $transfer): void
    {
        if (!in_array($transfer->status, ['pending', 'in_transit'])) {
            throw new \Exception(__('pos.transfer_cannot_be_cancelled'));
        }

        DB::transaction(function () use ($transfer) {
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->items as $item) {
                    WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->decrement('reserved_qty', $item->quantity);
                }
            }
            $transfer->update(['status' => 'cancelled']);
        });
    }

    // ── Product Batches ──────────────────────────────────────────────────────

    public function createBatch(array $data): ProductBatch
    {
        return DB::transaction(function () use ($data) {
            $batch = ProductBatch::create(array_merge($data, [
                'remaining_qty' => $data['original_qty'],
                'status'        => 'active',
            ]));

            // Add stock to warehouse
            $stock = $this->getOrCreateStock($data['warehouse_id'], $data['product_id']);
            $stock->increment('quantity', $data['original_qty']);

            // Sync product aggregate
            Product::where('id', $data['product_id'])->increment('quantity', $data['original_qty']);

            Log::channel('audit')->info('batch.created', ['batch_id' => $batch->id, 'user_id' => Auth::id()]);
            return $batch;
        });
    }

    public function batchesForProduct(Product $product, ?int $warehouseId = null)
    {
        return ProductBatch::with('warehouse:id,name,code')
            ->where('product_id', $product->id)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->get();
    }

    public function expireOldBatches(): int
    {
        return ProductBatch::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);
    }
}
