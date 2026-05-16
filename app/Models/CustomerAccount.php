<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAccount extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'debit', 'credit',
        'balance_after', 'reference_type', 'reference_id',
        'notes', 'created_by',
    ];

    protected $casts = [
        'debit'         => 'decimal:2',
        'credit'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
