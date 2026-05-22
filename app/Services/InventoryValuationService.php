<?php

namespace App\Services;

use App\Models\InventoryCostLayer;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    public const METHODS = [
        'weighted_average' => ['ar' => 'المتوسط المرجح', 'en' => 'Weighted Average'],
        'fifo'             => ['ar' => 'أول داخل أول خارج (FIFO)', 'en' => 'First In First Out (FIFO)'],
        'lifo'             => ['ar' => 'آخر داخل أول خارج (LIFO)', 'en' => 'Last In First Out (LIFO)'],
    ];

    public function getMethod(): string
    {
        return Setting::get('inventory_valuation_method', 'weighted_average');
    }

    /**
     * Create a cost layer when stock is added (purchase/manual).
     * Only creates layers when method is FIFO or LIFO.
     */
    public function createLayer(
        Product $product,
        int $quantity,
        float $unitCost,
        string $referenceType,
        ?int $referenceId,
        ?int $warehouseId = null
    ): void {
        if (!in_array($this->getMethod(), ['fifo', 'lifo'])) {
            return;
        }

        if ($unitCost <= 0 || $quantity <= 0) {
            return;
        }

        InventoryCostLayer::create([
            'product_id'     => $product->id,
            'warehouse_id'   => $warehouseId,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'original_qty'   => $quantity,
            'remaining_qty'  => $quantity,
            'unit_cost'      => round($unitCost, 4),
        ]);
    }

    /**
     * Deduct from cost layers on sale and return the weighted average unit cost consumed.
     * Must be called inside a DB transaction with rows already locked.
     */
    public function deductLayers(
        Product $product,
        int $quantity,
        ?int $warehouseId = null
    ): float {
        $method = $this->getMethod();

        if ($method === 'weighted_average') {
            return $product->avg_cost > 0 ? (float) $product->avg_cost : (float) ($product->cost_price ?? 0);
        }

        $query = InventoryCostLayer::where('product_id', $product->id)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->lockForUpdate();

        $layers = $method === 'fifo'
            ? $query->fifo()->get()
            : $query->lifo()->get();

        $remaining  = $quantity;
        $totalCost  = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $take       = min($layer->remaining_qty, $remaining);
            $totalCost += $take * $layer->unit_cost;
            $layer->decrement('remaining_qty', $take);
            $remaining -= $take;
        }

        // Fallback for quantity not covered by existing layers
        if ($remaining > 0) {
            $fallback   = $product->avg_cost > 0 ? (float) $product->avg_cost : (float) ($product->cost_price ?? 0);
            $totalCost += $remaining * $fallback;
        }

        return $quantity > 0 ? round($totalCost / $quantity, 4) : 0.0;
    }

    /**
     * Return current inventory valuation report comparing all three methods.
     */
    public function valuationReport(?int $warehouseId = null): array
    {
        $products = Product::withoutTrashed()
            ->with('warehouseStock')
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($products as $product) {
            $qty = $warehouseId
                ? (int) ($product->warehouseStock->where('warehouse_id', $warehouseId)->first()?->quantity ?? 0)
                : (int) $product->quantity;

            $wacUnit = $product->avg_cost > 0 ? (float) $product->avg_cost : (float) ($product->cost_price ?? 0);

            $fifoValue = $this->layerValue($product->id, $warehouseId, 'fifo', $qty, $wacUnit);
            $lifoValue = $this->layerValue($product->id, $warehouseId, 'lifo', $qty, $wacUnit);

            $rows[] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'quantity'     => $qty,
                'wac_unit'     => round($wacUnit, 4),
                'wac_value'    => round($qty * $wacUnit, 2),
                'fifo_value'   => round($fifoValue, 2),
                'lifo_value'   => round($lifoValue, 2),
                'layers_count' => InventoryCostLayer::where('product_id', $product->id)
                    ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                    ->withStock()
                    ->count(),
            ];
        }

        $totals = [
            'wac_total'  => round(collect($rows)->sum('wac_value'),  2),
            'fifo_total' => round(collect($rows)->sum('fifo_value'), 2),
            'lifo_total' => round(collect($rows)->sum('lifo_value'), 2),
        ];

        return [
            'method'  => $this->getMethod(),
            'methods' => self::METHODS,
            'rows'    => $rows,
            'totals'  => $totals,
        ];
    }

    /**
     * Calculate the value of on-hand stock using the specified FIFO or LIFO ordering.
     * Consumes layers in order until totalQty is covered; falls back to WAC for any
     * remainder not covered by existing layers. Read-only — no deductions occur.
     */
    private function layerValue(int $productId, ?int $warehouseId, string $order, int $totalQty, float $fallbackUnit): float
    {
        if ($totalQty <= 0) {
            return 0.0;
        }

        $query = InventoryCostLayer::where('product_id', $productId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));

        $layers = $order === 'fifo'
            ? $query->fifo()->get()
            : $query->lifo()->get();

        $remaining = $totalQty;
        $total     = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;
            $take       = min($layer->remaining_qty, $remaining);
            $total     += $take * (float) $layer->unit_cost;
            $remaining -= $take;
        }

        // Quantity not covered by layers — fall back to weighted average cost
        if ($remaining > 0) {
            $total += $remaining * $fallbackUnit;
        }

        return $total;
    }
}
