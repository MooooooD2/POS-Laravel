<?php

namespace App\Providers;

use App\Models\SalesReturn;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Policies\SalesReturnPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProductPolicy;
use App\Policies\AccountPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends AuthServiceProvider
{
    /**
     * FIX-3: تسجيل كل الـ Policies صراحةً — لا اعتماد على الـ auto-discovery وحده
     */
    protected $policies = [
        SalesReturn::class    => SalesReturnPolicy::class,
        Invoice::class        => InvoicePolicy::class,
        Product::class        => ProductPolicy::class,
        Account::class        => AccountPolicy::class,
        Supplier::class       => SupplierPolicy::class,
        SupplierPayment::class => SupplierPaymentPolicy::class,
        PurchaseOrder::class  => PurchaseOrderPolicy::class,
        User::class           => UserPolicy::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->registerPolicies();

        // Custom Blade directives for permissions
        Blade::if('permission', function ($permission) {
            return auth()->user() && auth()->user()->can($permission);
        });

        Blade::if('role', function ($role) {
            return auth()->user() && auth()->user()->hasRole($role);
        });

        Blade::if('anyrole', function ($roles) {
            if (!auth()->user()) return false;
            $roles = is_array($roles) ? $roles : func_get_args();
            return auth()->user()->hasAnyRole($roles);
        });

        Blade::if('allroles', function ($roles) {
            if (!auth()->user()) return false;
            $roles = is_array($roles) ? $roles : func_get_args();
            return auth()->user()->hasAllRoles($roles);
        });
    }
}
