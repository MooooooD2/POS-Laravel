<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // #7 fillable صريح — quantity محذوف (يتعدل عبر StockService فقط)
    protected $fillable = ['name', 'price', 'cost_price', 'avg_cost', 'last_cost', 'min_stock', 'track_batches', 'barcode', 'category', 'supplier'];
    protected $hidden   = ['deleted_at'];
    protected $casts    = ['price' => 'float', 'cost_price' => 'float', 'avg_cost' => 'float', 'last_cost' => 'float', 'min_stock' => 'integer', 'quantity' => 'integer', 'track_batches' => 'boolean'];

    public function getLowStockAttribute(): bool  { return $this->quantity <= $this->min_stock; }
    public function invoiceItems()     { return $this->hasMany(InvoiceItem::class); }
    public function stockMovements()   { return $this->hasMany(StockMovement::class); }
    public function batches()          { return $this->hasMany(ProductBatch::class); }
    public function warehouseStock()   { return $this->hasMany(WarehouseStock::class); }
}
