<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $fillable = ['return_id', 'product_id', 'product_name', 'quantity', 'price', 'subtotal'];

    public function salesReturn() { return $this->belongsTo(SalesReturn::class, 'return_id'); }
    public function product()     { return $this->belongsTo(Product::class); }
}
