<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id', 'warehouse_id', 'batch_number', 'lot_number',
        'manufacture_date', 'expiry_date', 'original_qty', 'remaining_qty',
        'cost_price', 'supplier_id', 'notes', 'status',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'cost_price'       => 'float',
        'original_qty'     => 'integer',
        'remaining_qty'    => 'integer',
    ];

    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function supplier()  { return $this->belongsTo(Supplier::class); }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(function ($q) {
                         $q->whereNull('expiry_date')
                           ->orWhere('expiry_date', '>=', now()->toDateString());
                     });
    }

    public function scopeFefo($query)
    {
        return $query->active()->orderByRaw('expiry_date IS NULL, expiry_date ASC');
    }
}
