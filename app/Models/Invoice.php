<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'total', 'discount', 'tax_rate',
        'tax_amount', 'final_total',
        'cash_received', 'change_amount',   // المبلغ المدفوع والباقي
        'payment_method',
        'cashier_id', 'cashier_name', 'status', 'date',
    ];

    protected $hidden = ['cashier_id'];

    protected $casts = [
        'total'         => 'float',
        'final_total'   => 'float',
        'discount'      => 'float',
        'tax_rate'      => 'float',
        'tax_amount'    => 'float',
        'cash_received' => 'float',
        'change_amount' => 'float',
        'date'          => 'date',
    ];

    public function items()   { return $this->hasMany(InvoiceItem::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function returns() { return $this->hasMany(SalesReturn::class); }
}
