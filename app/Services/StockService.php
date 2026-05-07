<?php
namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * #6  التحقق من الكمية + #5 Lock + #16-19 تسجيل كل حركة
     */
    public function addStock(
        Product $product,
        int $quantity,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual'
    ): void {
        DB::transaction(function () use ($product, $quantity, $reason, $referenceId, $referenceType) {
            // #5 قفل السجل لمنع Race Condition
            $fresh = Product::where('id', $product->id)->lockForUpdate()->first();
            $fresh->increment('quantity', $quantity);
            $this->logMovement($fresh, $quantity, 'add', $reason, $referenceId, $referenceType);
        });
    }

    /**
     * #6 يمنع الخصم إذا الكمية غير كافية ولا يوجد إذن بالسالب
     */
    public function deductStock(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId = null,
        string $referenceType = 'manual'
    ): void {
        // lockForUpdate only holds for the duration of a transaction —
        // wrapping here makes this method safe to call standalone as well.
        DB::transaction(function () use ($product, $quantity, $type, $reason, $referenceId, $referenceType) {
            $fresh = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            if ($fresh->quantity < $quantity) {
                throw new \Exception(__('pos.insufficient_stock', ['name' => $fresh->name]));
            }

            $fresh->decrement('quantity', $quantity);
            $this->logMovement($fresh, $quantity, $type, $reason, $referenceId, $referenceType);
        });
    }

    /**
     * تعديل يدوي للمخزون (جرد دوري)
     * #20 لا تعديل مباشر بدون log
     */
    public function adjustStock(Product $product, int $newQuantity, string $reason): void
    {
        DB::transaction(function () use ($product, $newQuantity, $reason) {
            $fresh      = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();
            $difference = $newQuantity - $fresh->quantity;

            $fresh->update(['quantity' => $newQuantity]);

            $type = $difference >= 0 ? 'adjustment_add' : 'adjustment_remove';
            $this->logMovement($fresh, abs($difference), $type, $reason, null, 'adjustment');
        });
    }

    /**
     * #16 #18 #19 #39 — تسجيل كل حركة مع السبب والمستخدم والمرجع
     */
    private function logMovement(
        Product $product,
        int $quantity,
        string $type,
        string $reason,
        ?int $referenceId,
        string $referenceType
    ): void {
        StockMovement::create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'quantity'       => $quantity,
            'balance_after'  => $product->quantity,  // already updated in-memory by increment/decrement
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
