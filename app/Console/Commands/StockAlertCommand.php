<?php
namespace App\Console\Commands;

use App\Models\Product;
use App\Jobs\ProcessStockAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StockAlertCommand extends Command
{
    protected $signature   = 'stock:alert';
    protected $description = 'فحص المنتجات ذات المخزون المنخفض وإرسال تنبيهات ومقترحات إعادة الطلب';

    public function handle(): int
    {
        $lowStock = Product::whereRaw('quantity <= min_stock AND quantity > 0')->get();
        $outStock = Product::where('quantity', 0)->get();

        foreach ($lowStock as $product) {
            ProcessStockAlert::dispatch($product->id, $product->quantity);
        }

        // Auto-reorder suggestions: products at or below their reorder_point
        $reorderNeeded = Product::whereRaw('reorder_point > 0 AND quantity <= reorder_point')->get();
        foreach ($reorderNeeded as $product) {
            Log::channel('audit')->info('stock.reorder_suggested', [
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'quantity'      => $product->quantity,
                'reorder_point' => $product->reorder_point,
                'reorder_qty'   => $product->reorder_qty,
                'supplier'      => $product->supplier,
                'timestamp'     => now()->toIso8601String(),
            ]);
        }

        $this->info(
            "المخزون: {$lowStock->count()} منخفض، {$outStock->count()} نفد، {$reorderNeeded->count()} يحتاج إعادة طلب."
        );

        return Command::SUCCESS;
    }
}
