<?php
namespace App\Console\Commands;

use App\Models\Product;
use App\Jobs\ProcessStockAlert;
use Illuminate\Console\Command;

class StockAlertCommand extends Command
{
    protected $signature   = 'stock:alert';
    protected $description = 'فحص المنتجات ذات المخزون المنخفض وإرسال تنبيهات';

    public function handle(): int
    {
        $lowStock = Product::whereRaw('quantity <= min_stock AND quantity > 0')->get();
        $outStock = Product::where('quantity', 0)->get();

        foreach ($lowStock as $product) {
            ProcessStockAlert::dispatch($product->id, $product->quantity);
        }

        $this->info("تم فحص المخزون: {$lowStock->count()} منتج منخفض، {$outStock->count()} منتج نفد.");
        return Command::SUCCESS;
    }
}
