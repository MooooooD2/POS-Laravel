<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id', 'product_id', 'product_name',
        'quantity', 'unit_cost', 'subtotal',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'subtotal'  => 'decimal:2',
    ];

    public function purchaseReturn(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
