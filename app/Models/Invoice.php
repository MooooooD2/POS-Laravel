<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_number', 'total', 'discount', 'loyalty_points_used', 'loyalty_discount',
        'tax_rate', 'tax_amount', 'final_total',
        'cash_received', 'change_amount',
        'payment_method', 'is_split_payment',
        'cashier_id', 'cashier_name', 'customer_id', 'status', 'date',
    ];

    protected $hidden = ['cashier_id'];

    protected $casts = [
        'total'            => 'float',
        'final_total'      => 'float',
        'discount'         => 'float',
        'tax_rate'         => 'float',
        'tax_amount'       => 'float',
        'loyalty_points_used' => 'integer',
        'loyalty_discount'    => 'float',
        'cash_received'       => 'float',
        'change_amount'       => 'float',
        'is_split_payment'    => 'boolean',
        'date'             => 'date',
    ];

    public function items()    { return $this->hasMany(InvoiceItem::class); }
    public function cashier()  { return $this->belongsTo(User::class, 'cashier_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function returns()  { return $this->hasMany(SalesReturn::class); }
    public function payments() { return $this->hasMany(InvoicePayment::class); }
}
