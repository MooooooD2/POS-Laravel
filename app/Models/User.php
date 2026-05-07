<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    // #7 is_active و role في fillable لكن id و remember_token ليسا
    protected $fillable  = ['username', 'password', 'full_name', 'role', 'is_active', 'language'];
    // #35 إخفاء البيانات الحساسة
    protected $hidden    = ['password', 'remember_token', 'deleted_at'];
    protected $casts     = ['is_active' => 'boolean'];

    public function getAuthIdentifierName() { return 'id'; }
    public function invoices()       { return $this->hasMany(Invoice::class, 'cashier_id'); }
    public function stockMovements() { return $this->hasMany(StockMovement::class, 'employee_id'); }
}
