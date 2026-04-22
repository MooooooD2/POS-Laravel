<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'cost_price', 'quantity',
        'min_stock', 'barcode', 'category', 'supplier'
    ];

    protected $casts = ['price' => 'float', 'cost_price' => 'float'];

    public function getLowStockAttribute(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    public function invoiceItems()  { return $this->hasMany(InvoiceItem::class); }
    public function stockMovements(){ return $this->hasMany(StockMovement::class); }
}
