<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class StockService
{
    /**
     * Add stock to a product and log the movement.
     */
    public function addStock(Product $product, int $quantity, string $reason, ?int $referenceId = null): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Stock quantity must be positive, got {$quantity}.");
        }

        $product->increment('quantity', $quantity);
        $this->logMovement($product, $quantity, 'add', $reason, $referenceId);
    }

    /**
     * Deduct stock from a product and log the movement.
     * Caller is responsible for checking sufficient stock before calling.
     */
    public function deductStock(Product $product, int $quantity, string $type, string $reason, ?int $referenceId = null): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Stock quantity must be positive, got {$quantity}.");
        }

        $product->decrement('quantity', $quantity);
        $this->logMovement($product, $quantity, $type, $reason, $referenceId);
    }

    private function logMovement(Product $product, int $quantity, string $type, string $reason, ?int $referenceId): void
    {
        StockMovement::create([
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'quantity'      => $quantity,
            'movement_type' => $type,
            'reason'        => $reason,
            'reference_id'  => $referenceId,
            'employee_id'   => Auth::id(),
            'employee_name' => Auth::user()?->full_name,
        ]);
    }
}
