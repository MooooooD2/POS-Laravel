<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'return_number', 'invoice_id', 'invoice_number', 'customer_name',
        'total_amount', 'reason', 'status', 'return_date',
        'refund_method', 'refund_amount',
        'processed_by', 'processed_by_name',
    ];
    protected $hidden = ['processed_by'];
    protected $casts  = [
        'return_date'   => 'date',
        'total_amount'  => 'float',
        'refund_amount' => 'float',
    ];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function items()     { return $this->hasMany(ReturnItem::class, 'return_id'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}
