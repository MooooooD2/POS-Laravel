<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'product_id', 'product_name', 'quantity', 'returned_qty', 'price', 'cost_price', 'subtotal', 'tax_rate', 'tax_amount', 'returned_tax', 'warehouse_id', 'batch_id'];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function product()   { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function batch()     { return $this->belongsTo(ProductBatch::class); }
}
