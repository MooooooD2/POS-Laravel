<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    protected $fillable = [
        'transfer_number', 'from_warehouse_id', 'to_warehouse_id',
        'requested_by', 'received_by', 'status', 'notes', 'received_at',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function fromWarehouse() { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse()   { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function requestedBy()   { return $this->belongsTo(User::class, 'requested_by'); }
    public function receivedBy()    { return $this->belongsTo(User::class, 'received_by'); }
    public function items()         { return $this->hasMany(WarehouseTransferItem::class, 'transfer_id'); }
}
