<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['branch_id', 'name', 'code', 'address', 'keeper_name', 'is_default', 'is_active'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function branch()        { return $this->belongsTo(Branch::class); }
    public function stock()         { return $this->hasMany(WarehouseStock::class); }
    public function batches()       { return $this->hasMany(ProductBatch::class); }
    public function transfersFrom() { return $this->hasMany(WarehouseTransfer::class, 'from_warehouse_id'); }
    public function transfersTo()   { return $this->hasMany(WarehouseTransfer::class, 'to_warehouse_id'); }
}
