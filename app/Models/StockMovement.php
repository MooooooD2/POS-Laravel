<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'product_name', 'quantity',
        'movement_type', 'reason', 'reference_id',
        'employee_id', 'employee_name'
    ];

    public function product()  { return $this->belongsTo(Product::class); }
    public function employee() { return $this->belongsTo(User::class, 'employee_id'); }
}
