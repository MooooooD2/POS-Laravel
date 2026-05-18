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
use App\Policies\ReportPolicy;
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

        // Report gates (policy-style gates without a bound model)
        $policy = new ReportPolicy();
        Gate::define('report.sales',               fn(User $u) => $policy->viewSales($u));
        Gate::define('report.stock',               fn(User $u) => $policy->viewStock($u));
        Gate::define('report.returns',             fn(User $u) => $policy->viewReturns($u));
        Gate::define('report.financial',           fn(User $u) => $policy->viewFinancial($u));
        Gate::define('report.cashier-performance', fn(User $u) => $policy->viewCashierPerformance($u));
        Gate::define('report.permissions-audit',   fn(User $u) => $policy->viewPermissionsAudit($u));
        Gate::define('report.aged',                fn(User $u) => $policy->viewAged($u));
        Gate::define('report.export',              fn(User $u) => $policy->export($u));
    }
}
