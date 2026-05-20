<?php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductRecipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RecipeService
{
    public function __construct(private StockService $stockService) {}

    /**
     * خصم مكوّنات الوصفة لكل وحدة مباعة من المنتج النهائي.
     * يُستدعى من InvoiceService بعد إتمام الفاتورة.
     */
    public function deductIngredients(int $productId, float $quantitySold, int $invoiceId, ?int $warehouseId = null): void
    {
        $ingredients = ProductRecipe::where('product_id', $productId)
            ->with('ingredient')
            ->get();

        if ($ingredients->isEmpty()) {
            return;
        }

        foreach ($ingredients as $line) {
            $needed     = $line->quantity * $quantitySold;
            $ingredient = $line->ingredient;

            if (!$ingredient || $needed <= 0) continue;

            try {
                DB::transaction(function () use ($ingredient, $needed, $invoiceId, $warehouseId) {
                    $fresh = Product::lockForUpdate()->findOrFail($ingredient->id);

                    if ($fresh->quantity < $needed) {
                        Log::channel('audit')->warning('recipe.ingredient_low', [
                            'ingredient_id'   => $fresh->id,
                            'ingredient_name' => $fresh->name,
                            'needed'          => $needed,
                            'available'       => $fresh->quantity,
                            'invoice_id'      => $invoiceId,
                        ]);
                        // خصم ما هو متاح فقط دون إيقاف البيع
                        $needed = min($needed, (float) $fresh->quantity);
                        if ($needed <= 0) return;
                    }

                    $this->stockService->deductLockedStock(
                        $fresh,
                        (int) ceil($needed),
                        'recipe_deduction',
                        __('pos.recipe_deduction_note', ['invoice' => $invoiceId]),
                        $invoiceId,
                        'invoice',
                        $warehouseId
                    );
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('recipe.deduction_failed', [
                    'ingredient_id' => $line->ingredient_id,
                    'invoice_id'    => $invoiceId,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    public function syncRecipe(int $productId, array $ingredients): void
    {
        $rows = collect($ingredients)->map(fn($i) => [
            'product_id'    => $productId,
            'ingredient_id' => (int) $i['ingredient_id'],
            'quantity'      => (float) $i['quantity'],
        ])->filter(fn($r) => $r['quantity'] > 0 && $r['ingredient_id'] !== $productId);

        DB::transaction(function () use ($productId, $rows) {
            ProductRecipe::where('product_id', $productId)->delete();
            if ($rows->isNotEmpty()) {
                ProductRecipe::insert($rows->toArray());
            }
        });

        Log::channel('audit')->info('recipe.synced', [
            'product_id'       => $productId,
            'ingredient_count' => $rows->count(),
            'user_id'          => Auth::id(),
        ]);
    }
}
