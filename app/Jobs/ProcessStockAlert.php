<?php
namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * #27 #37 تنبيه تلقائي عند انخفاض المخزون
 */
class ProcessStockAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $productId, private int $currentQty) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);
        if (!$product) return;

        Log::channel('audit')->warning('low_stock_alert', [
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => $this->currentQty,
            'min_stock'    => $product->min_stock,
            'timestamp'    => now()->toIso8601String(),
        ]);
    }
}
