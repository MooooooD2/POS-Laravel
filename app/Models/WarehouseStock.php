<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    public $timestamps = false;

    protected $table    = 'warehouse_stock';
    protected $fillable = ['warehouse_id', 'product_id', 'quantity', 'reserved_qty', 'min_stock'];
    protected $casts    = ['quantity' => 'integer', 'reserved_qty' => 'integer', 'min_stock' => 'integer'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function product()   { return $this->belongsTo(Product::class); }

    public function getAvailableQtyAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_qty);
    }
}
