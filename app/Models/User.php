<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $fillable = ['username', 'password', 'full_name', 'role', 'is_active', 'language'];
    protected $hidden   = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Use 'username' as login field instead of 'email'
    // public function getAuthIdentifierName() { return 'username'; }

    public function invoices()       { return $this->hasMany(Invoice::class, 'cashier_id'); }
    public function stockMovements() { return $this->hasMany(StockMovement::class, 'employee_id'); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class, 'created_by'); }
}
