<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'email'];

    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    public function payments()       { return $this->hasMany(SupplierPayment::class); }
    public function accounts()       { return $this->hasMany(SupplierAccount::class); }
}
