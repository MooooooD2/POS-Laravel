<?php
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// ── Landing page (default root) ───────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
    return view('welcome', compact('plans'));
})->name('welcome');

Route::redirect('/home', '/');

// ── Auth ──────────────────────────────────────────────────────────────────
Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',   [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

// ── Register (new store sign-up) ──────────────────────────────────────────
Route::get('/register',  [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,60')->name('register.post');

// ── 2FA (FIX-5) ───────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/verify',          [TwoFactorController::class, 'showVerify'])->name('verify');
    Route::post('/verify',         [TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('verify.post');
    Route::get('/setup',           [TwoFactorController::class, 'showSetup'])->name('setup');
    Route::post('/setup/confirm',  [TwoFactorController::class, 'confirmSetup'])->name('setup.confirm');
    Route::post('/disable',        [TwoFactorController::class, 'disable'])->name('disable');
});
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->where('locale', 'ar|en')->name('lang.switch');
Route::get('/lang/{locale}/translations', [LanguageController::class, 'getTranslations'])->where('locale', 'ar|en')->name('lang.translations');


// ── Subscribe (auth + tenancy, no subscription-check to avoid loop) ──────
Route::middleware(['auth', 'tenancy'])->group(function () {
    Route::get('/subscribe', function () {
        $tenant    = tenancy()->tenant;
        $plans     = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
        $methods   = \App\Models\PaymentAccount::configured();
        $waAccount = \App\Models\PaymentAccount::where('method', 'whatsapp')->where('is_active', true)->first();
        $whatsapp  = $waAccount?->account_number ?? '201000000000';
        return view('subscription.subscribe', compact('tenant', 'plans', 'methods', 'whatsapp'));
    })->name('subscribe');
});

// ── Impersonate leave — only auth+tenancy; no 2FA/subscription gate needed ──
Route::middleware(['auth', 'tenancy'])->post('/impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');

// ── Authenticated web views ───────────────────────────────────────────────
Route::middleware(['auth', 'tenancy', '2fa', \App\Http\Middleware\CheckSubscriptionActive::class])->group(function () {
    Route::get('/session-info', [AuthController::class, 'sessionInfo'])->name('session.info');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock'])->name('dashboard.low-stock');
    Route::middleware(['permission:view_pos'])->group(function () {
        Route::get('/pos', [InvoiceController::class, 'posPage'])->name('pos');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
    });
    Route::middleware(['permission:view_returns'])->get('/returns', fn() => view('returns.index'))->name('returns');
    Route::middleware(['permission:view_warehouse'])->group(function () {
        Route::get('/warehouse', fn() => view('warehouse.index'))->name('warehouse');
        Route::get('/warehouses', [WarehouseController::class, 'page'])->name('warehouses');
        Route::get('/waste', [\App\Http\Controllers\WasteController::class, 'index'])->name('waste');
        Route::get('/suppliers', fn() => view('suppliers.index'))->name('suppliers');
        Route::get('/purchase-orders', fn() => view('purchase-orders.index'))->name('purchase-orders');
        Route::get('/supplier-payments', fn() => view('supplier-payments.index'))->name('supplier-payments');
        Route::get('/supplier-accounts', fn() => view('supplier-accounts.index'))->name('supplier-accounts');
        Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
        Route::get('/customer-groups', fn() => view('customer-groups.index'))->name('customer-groups');
        Route::get('/promotions',      fn() => view('promotions.index'))->name('promotions');
    });
    Route::middleware(['permission:view_accounting'])->group(function () {
        Route::get('/accounting', fn() => view('accounting.index'))->name('accounting');
        Route::get('/financial-reports', fn() => view('financial-reports.index'))->name('financial-reports');
        Route::get('/settings', fn() => view('settings.index'))->name('settings');
    });
    Route::middleware(['permission:view_reports'])->group(function () {
        Route::get('/reports', fn() => view('reports.index'))->name('reports');
        Route::get('/reports/budget', fn() => view('reports.budget'))->name('reports.budget');
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/reports/export/sales',   [ReportController::class, 'exportSales'])->name('reports.export.sales');
            Route::post('/reports/export/returns', [ReportController::class, 'exportReturns'])->name('reports.export.returns');
            Route::post('/reports/export/stock',   [ReportController::class, 'exportStock'])->name('reports.export.stock');
        });
    });
    Route::middleware(['permission:manage_roles'])->get('/roles', fn() => view('roles.index'))->name('roles');
    Route::middleware(['permission:manage_roles'])->get('/branches', [BranchController::class, 'page'])->name('branches');
    Route::middleware(['permission:manage_roles'])->get('/whatsapp', fn() => view('whatsapp.index'))->name('whatsapp');

    // ── Impersonation ─────────────────────────────────────────────────────
    Route::middleware(['permission:manage_roles'])->group(function () {
        Route::post('/impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');
    });

    // ── Tenant management (master-tenant admin only) ───────────────────────
    Route::middleware(['permission:manage_tenants'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/cpanel',                    [TenantController::class, 'cpanel'])->name('cpanel');
        Route::get('/tenants',                   [TenantController::class, 'index'])->name('tenants');
        Route::get('/tenants/stats',             [TenantController::class, 'stats'])->name('tenants.stats');
        Route::post('/tenants',                  [TenantController::class, 'store'])->name('tenants.store');
        Route::put('/tenants/{id}',              [TenantController::class, 'update'])->name('tenants.update');
        Route::patch('/tenants/{id}/toggle',     [TenantController::class, 'toggle'])->name('tenants.toggle');
        Route::delete('/tenants/{id}',           [TenantController::class, 'destroy'])->name('tenants.destroy');
        Route::post('/tenants/{id}/seed',        [TenantController::class, 'seed'])->name('tenants.seed');
        Route::post('/tenants/{id}/extend',              [TenantController::class, 'extend'])->name('tenants.extend');
        Route::patch('/tenants/{id}/suspend',            [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::patch('/tenants/{id}/cancel',             [TenantController::class, 'cancelSubscription'])->name('tenants.cancel');
        Route::get('/tenants/{id}/users',                [TenantController::class, 'tenantUsers'])->name('tenants.users');
        Route::patch('/tenants/{id}/users/{userId}/toggle', [TenantController::class, 'toggleTenantUser'])->name('tenants.users.toggle');

        // ── Plans & Pricing ───────────────────────────────────────────────
        Route::get('/plans',              [PlanController::class, 'index'])->name('plans');
        Route::post('/plans',             [PlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{id}',         [PlanController::class, 'update'])->name('plans.update');
        Route::patch('/plans/{id}/toggle',[PlanController::class, 'toggle'])->name('plans.toggle');
        Route::delete('/plans/{id}',      [PlanController::class, 'destroy'])->name('plans.destroy');

        // ── Payment Accounts (wallet numbers) ─────────────────────────────
        Route::get('/payment-accounts',         [\App\Http\Controllers\PaymentAccountController::class, 'index'])->name('payment-accounts.index');
        Route::get('/payment-accounts/page',    [\App\Http\Controllers\PaymentAccountController::class, 'page'])->name('payment-accounts.page');
        Route::put('/payment-accounts/{id}',    [\App\Http\Controllers\PaymentAccountController::class, 'update'])->name('payment-accounts.update');
    });
});

// تسوية الخزينة
Route::middleware(['auth', '2fa', 'permission:view_pos'])->group(function () {
    Route::get('/cash-register', fn() => view('cash-register.index'))->name('cash-register');
});

Route::middleware(['auth', '2fa', 'permission:view_reports'])->group(function () {
    Route::get('/profit-reports', fn() => view('profit-reports.index'))->name('profit-reports');
});
