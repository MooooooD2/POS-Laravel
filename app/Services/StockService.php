<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class StockService
{
    /**
     * Add stock to product - إضافة مخزون للمنتج
     */
    public function addStock(Product $product, int $quantity, string $reason, ?int $referenceId = null): void
    {
        $product->increment('quantity', $quantity);
        $this->logMovement($product, $quantity, 'add', $reason, $referenceId);
    }

    /**
     * Deduct stock from product - خصم مخزون من المنتج
     */
    public function deductStock(Product $product, int $quantity, string $type, string $reason, ?int $referenceId = null): void
    {
        $product->decrement('quantity', $quantity);
        $this->logMovement($product, $quantity, $type, $reason, $referenceId);
    }

    /**
     * Log stock movement - تسجيل حركة المخزون
     */
    private function logMovement(Product $product, int $quantity, string $type, string $reason, ?int $referenceId): void
    {
        StockMovement::create([
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'quantity'      => $quantity,
            'movement_type' => $type,
            'reason'        => $reason,
            'reference_id'  => $referenceId,
            'employee_id'   => Auth::user()?->id,
            'employee_name' => Auth::user()?->full_name,
        ]);
    }
}
