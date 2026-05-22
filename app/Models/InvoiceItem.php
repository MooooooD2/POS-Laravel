<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'product_id', 'product_name', 'quantity', 'returned_qty', 'price', 'cost_price', 'subtotal', 'tax_rate', 'tax_amount', 'returned_tax', 'warehouse_id', 'batch_id'];

    protected $casts = [
        'quantity'     => 'integer',
        'returned_qty' => 'integer',
        'price'        => 'decimal:4',
        'cost_price'   => 'decimal:4',
        'subtotal'     => 'decimal:4',
        'tax_rate'     => 'decimal:4',
        'tax_amount'   => 'decimal:4',
        'returned_tax' => 'decimal:4',
    ];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function batch()     { return $this->belongsTo(ProductBatch::class); }
}
