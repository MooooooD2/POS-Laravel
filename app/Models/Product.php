<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'price', 'cost_price', 'quantity',
        'min_stock', 'barcode', 'category', 'supplier',
    ];

    protected $casts = [
        'price'      => 'float',
        'cost_price' => 'float',
        'quantity'   => 'integer',
        'min_stock'  => 'integer',
    ];

    /** True when current quantity is at or below the minimum stock level. */
    public function getLowStockAttribute(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    /** Profit margin as a percentage. */
    public function getProfitMarginAttribute(): float
    {
        return $this->price > 0
            ? round((($this->price - $this->cost_price) / $this->price) * 100, 2)
            : 0;
    }

    public function invoiceItems():  \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
