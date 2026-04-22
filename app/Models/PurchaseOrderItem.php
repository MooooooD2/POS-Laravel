<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'po_id', 'product_id', 'product_name',
        'quantity', 'cost_price', 'selling_price',
        'subtotal', 'received_quantity'
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function product()       { return $this->belongsTo(Product::class); }
}
