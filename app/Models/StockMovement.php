<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    // #19 balance_after و ip_address للتتبع
    protected $fillable = [
        'product_id', 'product_name', 'quantity', 'balance_after',
        'movement_type', 'reference_type', 'reference_id',
        'reason', 'employee_id', 'employee_name', 'ip_address',
    ];
    // StockMovement لا تُحذف ولا تُعدَّل — للتدقيق فقط
    public function product()  { return $this->belongsTo(Product::class); }
    public function employee() { return $this->belongsTo(User::class, 'employee_id'); }
}
