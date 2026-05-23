<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    // #7 is_active و role في fillable لكن id و remember_token ليسا
    protected $fillable  = [
        'username', 'password', 'full_name', 'role', 'is_active', 'language',
        'branch_id',
        'google2fa_secret', 'google2fa_enabled', 'google2fa_recovery_codes',
    ];
    // #35 إخفاء البيانات الحساسة
    protected $hidden    = ['password', 'remember_token', 'deleted_at', 'google2fa_secret', 'google2fa_recovery_codes'];
    protected $casts     = [
        'is_active'               => 'boolean',
        'google2fa_enabled'       => 'boolean',
        'google2fa_recovery_codes' => 'array',
    ];
    protected $connection = 'tenant';
    public function getAuthIdentifierName() { return 'id'; }
    public function invoices()       { return $this->hasMany(Invoice::class, 'cashier_id'); }
    public function stockMovements() { return $this->hasMany(StockMovement::class, 'employee_id'); }
    public function branch()         { return $this->belongsTo(Branch::class); }
}
