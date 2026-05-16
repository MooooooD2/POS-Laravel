<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'type', 'name', 'phone', 'email',
        'national_id', 'tax_number', 'commercial_register',
        'governate', 'city', 'address',
        'credit_limit', 'loyalty_points', 'notes', 'is_active',
    ];

    // balance is never in fillable — only CustomerAccountService touches it
    protected $casts = [
        'is_active'    => 'boolean',
        'credit_limit' => 'decimal:2',
        'balance'      => 'decimal:2',
    ];

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function accountEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerAccount::class);
    }

    public function getAvailableCreditAttribute(): float
    {
        return (float) ($this->credit_limit - $this->balance);
    }
}
