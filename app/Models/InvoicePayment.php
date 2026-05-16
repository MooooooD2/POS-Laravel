<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id', 'method', 'amount', 'reference', 'metadata',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'metadata' => 'array',
    ];

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
