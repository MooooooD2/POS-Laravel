<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'supplier_id', 'supplier_name',
        'total_amount', 'discount', 'final_amount',
        'status', 'order_date', 'expected_date',
        'received_date', 'notes', 'created_by', 'created_by_name'
    ];

    protected $casts = ['order_date' => 'date', 'expected_date' => 'date', 'received_date' => 'date'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items()    { return $this->hasMany(PurchaseOrderItem::class, 'po_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}
