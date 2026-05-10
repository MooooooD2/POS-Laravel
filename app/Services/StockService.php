<?php
namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        private ProductRepositoryInterface      $productRepo,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function addStock(
        Product $product,
        int $quantity,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual'
    ): void {
        DB::transaction(function () use ($product, $quantity, $reason, $referenceId, $referenceType) {
            $fresh = $this->productRepo->lockForUpdate([$product->id])->first();
            $fresh->increment('quantity', $quantity);
            $this->logMovement($fresh, $quantity, 'add', $reason, $referenceId, $referenceType);
        });
    }

    public function deductStock(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual'
    ): void {
        DB::transaction(function () use ($product, $quantity, $type, $reason, $referenceId, $referenceType) {
            $fresh = $this->productRepo->lockForUpdate([$product->id])->firstOrFail();

            if ($fresh->quantity < $quantity) {
                throw new \Exception(__('pos.insufficient_stock', ['name' => $fresh->name]));
            }

            $fresh->decrement('quantity', $quantity);
            $this->logMovement($fresh, $quantity, $type, $reason, $referenceId, $referenceType);
        });
    }

    public function adjustStock(Product $product, int $newQuantity, string $reason): void
    {
        DB::transaction(function () use ($product, $newQuantity, $reason) {
            $fresh      = $this->productRepo->lockForUpdate([$product->id])->firstOrFail();
            $difference = $newQuantity - $fresh->quantity;

            // quantity is not in fillable — use direct assignment + save
            $fresh->quantity = $newQuantity;
            $fresh->save();

            $type = $difference >= 0 ? 'adjustment_add' : 'adjustment_remove';
            $this->logMovement($fresh, abs($difference), $type, $reason, null, 'adjustment');
        });
    }

    private function logMovement(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId,
        string $referenceType
    ): void {
        $this->movementRepo->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'quantity'       => $quantity,
            'balance_after'  => $product->quantity,
            'movement_type'  => $type,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'reason'         => $reason,
            'employee_id'    => Auth::id(),
            'employee_name'  => Auth::user()?->full_name,
            'ip_address'     => request()->ip(),
        ]);
    }
}
