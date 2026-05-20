<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // #7 fillable صريح — quantity محذوف (يتعدل عبر StockService فقط)
    protected $fillable = ['name', 'price', 'wholesale_price', 'vip_price', 'cost_price', 'avg_cost', 'last_cost', 'min_stock', 'reorder_point', 'reorder_qty', 'track_batches', 'barcode', 'category', 'supplier', 'unit_id', 'tax_category_id'];
    protected $hidden   = ['deleted_at'];
    protected $casts    = ['price' => 'float', 'wholesale_price' => 'float', 'vip_price' => 'float', 'cost_price' => 'float', 'avg_cost' => 'float', 'last_cost' => 'float', 'min_stock' => 'integer', 'quantity' => 'integer', 'track_batches' => 'boolean'];

    /** Return the effective selling price for the given customer price level. */
    public function priceFor(string $level): float
    {
        return match ($level) {
            'wholesale' => $this->wholesale_price > 0 ? $this->wholesale_price : $this->price,
            'vip'       => $this->vip_price > 0       ? $this->vip_price       : $this->price,
            default     => $this->price,
        };
    }

    public function getLowStockAttribute(): bool  { return $this->quantity <= $this->min_stock; }
    public function unit()             { return $this->belongsTo(\App\Models\Unit::class); }
    public function taxCategory()      { return $this->belongsTo(\App\Models\TaxCategory::class); }
    public function invoiceItems()     { return $this->hasMany(InvoiceItem::class); }
    public function stockMovements()   { return $this->hasMany(StockMovement::class); }
    public function batches()          { return $this->hasMany(ProductBatch::class); }
    public function warehouseStock()   { return $this->hasMany(WarehouseStock::class); }
    public function recipe()           { return $this->hasMany(ProductRecipe::class); }
    public function usedInRecipes()    { return $this->hasMany(ProductRecipe::class, 'ingredient_id'); }
    public function unitConversion()   { return $this->hasOne(UnitConversion::class); }
}
