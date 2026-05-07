<?php
namespace App\Providers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalesReturnPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class        => ProductPolicy::class,
        Invoice::class        => InvoicePolicy::class,
        Supplier::class       => SupplierPolicy::class,
        User::class           => UserPolicy::class,
        Account::class        => AccountPolicy::class,
        PurchaseOrder::class  => PurchaseOrderPolicy::class,
        SalesReturn::class    => SalesReturnPolicy::class,
        SupplierPayment::class => SupplierPaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Admin bypasses every Gate/policy check — safety net on top of Spatie permissions.
        // Returns null (not false) for non-admins so normal checks continue.
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('admin') ? true : null;
        });
    }
}
