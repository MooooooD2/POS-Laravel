<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // #7 fillable صريح — quantity محذوف (يتعدل عبر StockService فقط)
    protected $fillable = ['name', 'price', 'cost_price', 'min_stock', 'barcode', 'category', 'supplier'];
    protected $hidden   = ['deleted_at'];
    protected $casts    = ['price' => 'float', 'cost_price' => 'float', 'min_stock' => 'integer', 'quantity' => 'integer'];

    public function getLowStockAttribute(): bool  { return $this->quantity <= $this->min_stock; }
    public function invoiceItems()   { return $this->hasMany(InvoiceItem::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}
