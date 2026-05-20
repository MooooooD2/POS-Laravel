<?php
namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Jobs\ProcessStockAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StockAlertCommand extends Command
{
    protected $signature   = 'stock:alert';
    protected $description = 'فحص المخزون المنخفض، تنبيهات انتهاء الصلاحية، ومقترحات إعادة الطلب الذكية';

    public function handle(): int
    {
        $this->checkLowStock();
        $this->checkExpiryAlerts();
        $this->suggestSmartReorders();

        return Command::SUCCESS;
    }

    private function checkLowStock(): void
    {
        $lowStock = Product::whereRaw('quantity <= min_stock AND quantity > 0')->get();
        $outStock = Product::where('quantity', 0)->get();

        foreach ($lowStock as $product) {
            ProcessStockAlert::dispatch($product->id, $product->quantity);
        }

        $this->info("المخزون: {$lowStock->count()} منخفض، {$outStock->count()} نفد.");
    }

    private function checkExpiryAlerts(): void
    {
        $within24h = now()->addHours(24)->toDateString();
        $today     = now()->toDateString();

        $expiringBatches = ProductBatch::with('product:id,name')
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $within24h])
            ->where('remaining_qty', '>', 0)
            ->get();

        foreach ($expiringBatches as $batch) {
            Log::channel('audit')->warning('batch.expiry_alert_24h', [
                'product_id'   => $batch->product_id,
                'product_name' => $batch->product?->name,
                'batch_id'     => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date'  => $batch->expiry_date?->toDateString(),
                'remaining_qty'=> $batch->remaining_qty,
                'timestamp'    => now()->toIso8601String(),
            ]);
        }

        if ($expiringBatches->count() > 0) {
            $this->warn("تنبيه انتهاء صلاحية: {$expiringBatches->count()} دُفعة تنتهي خلال 24 ساعة.");
        }
    }

    private function suggestSmartReorders(): void
    {
        $sevenDaysAgo = now()->subDays(7)->toDateString();

        // Calculate 7-day avg daily consumption per product from sale movements
        $consumption = StockMovement::where('type', 'sale')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('product_id, SUM(ABS(quantity)) as total_sold')
            ->groupBy('product_id')
            ->pluck('total_sold', 'product_id');

        $reorderNeeded = Product::whereRaw('reorder_point > 0 AND quantity <= reorder_point')->get();

        foreach ($reorderNeeded as $product) {
            $avgDailyConsumption = (float) ($consumption[$product->id] ?? 0) / 7;
            // Suggested qty = max of configured reorder_qty and 14-day demand forecast
            $suggestedQty = max(
                (int) ($product->reorder_qty ?? 0),
                (int) ceil($avgDailyConsumption * 14)
            );

            Log::channel('audit')->info('stock.reorder_suggested', [
                'product_id'           => $product->id,
                'product_name'         => $product->name,
                'quantity'             => $product->quantity,
                'reorder_point'        => $product->reorder_point,
                'suggested_order_qty'  => $suggestedQty,
                'avg_daily_consumption'=> round($avgDailyConsumption, 2),
                'supplier'             => $product->supplier,
                'timestamp'            => now()->toIso8601String(),
            ]);
        }

        $this->info("{$reorderNeeded->count()} منتج يحتاج إعادة طلب.");
    }
}
