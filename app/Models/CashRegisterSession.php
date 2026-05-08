<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterSession extends Model
{
    protected $fillable = [
        'session_number', 'cashier_id', 'cashier_name',
        'opening_amount', 'expected_cash', 'actual_cash', 'difference',
        'total_sales', 'total_returns', 'total_card', 'total_transfer',
        'invoices_count', 'status', 'notes', 'opened_at', 'closed_at',
    ];
    protected $casts = [
        'cashier_id'      => 'integer',
        'invoices_count'  => 'integer',
        'opening_amount'  => 'float',
        'expected_cash'   => 'float',
        'actual_cash'     => 'float',
        'difference'      => 'float',
        'total_sales'     => 'float',
        'total_returns'   => 'float',
        'total_card'      => 'float',
        'total_transfer'  => 'float',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
}
