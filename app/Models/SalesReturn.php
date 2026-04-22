<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'return_number', 'invoice_id', 'invoice_number',
        'customer_name', 'total_amount', 'reason',
        'status', 'return_date', 'processed_by', 'processed_by_name'
    ];

    protected $casts = ['return_date' => 'date', 'total_amount' => 'float'];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function items()     { return $this->hasMany(ReturnItem::class, 'return_id'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}
