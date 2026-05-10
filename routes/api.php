<?php
// #25 API routes منفصلة عن web
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierAccountController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// #12 Rate Limiting: 60 طلب/دقيقة على كل APIs
Route::middleware(['auth', 'throttle:60,1'])->group(function () {

    Route::get('/dashboard-data', [DashboardController::class, 'data'])->name('api.dashboard.data');

    // POS
    Route::middleware('permission:view_pos')->group(function () {
        Route::get('/search-product', [InvoiceController::class, 'searchProduct'])->name('products.search');
        Route::post('/invoices', [InvoiceController::class, 'createInvoice'])->name('invoices.create');
        Route::get('/invoices', [InvoiceController::class, 'getByNumber'])->name('invoices.by-number');
        Route::get('/invoices/{invoice}/returnable-items', [InvoiceController::class, 'returnableItems'])->name('invoices.returnable-items');
    });

    // Returns
    Route::middleware('permission:view_returns')
        ->post('/returns', [ReturnController::class, 'store'])
        ->name('returns.store');

    // Warehouse
    Route::middleware('permission:view_warehouse')->group(function () {
        Route::get('/products', [ProductController::class, 'all'])->name('products.all');
        Route::post('/products', [ProductController::class, 'store'])->middleware('throttle:30,1')->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/add-stock', [ProductController::class, 'addStock'])->middleware('throttle:30,1')->name('products.add-stock');

        Route::get('/suppliers', [SupplierController::class, 'all'])->name('suppliers.all');
        Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('throttle:20,1')->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'all'])->name('purchase-orders.all');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('throttle:20,1')->name('purchase-orders.store');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');

        Route::get('/supplier-payments', [SupplierPaymentController::class, 'all'])->name('supplier-payments.all');
        Route::post('/supplier-payments', [SupplierPaymentController::class, 'store'])->middleware('throttle:20,1')->name('supplier-payments.store');

        Route::get('/supplier-accounts/{supplier}', [SupplierAccountController::class, 'show'])->name('supplier-accounts.show');
    });

    // Accounting
    Route::middleware('permission:view_accounting')->group(function () {
        Route::get('/accounts', [AccountingController::class, 'allAccounts'])->name('accounts.all');
        Route::post('/accounts', [AccountingController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::get('/journal-entries', [AccountingController::class, 'allJournalEntries'])->name('journal-entries.all');
        Route::post('/journal-entries', [AccountingController::class, 'storeJournalEntry'])->middleware('throttle:30,1')->name('journal-entries.store');

        Route::get('/settings', [SettingController::class, 'all'])->name('settings.all');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/group/{group}', [SettingController::class, 'group'])->name('settings.group');
    });

    // Reports
    Route::middleware('permission:view_reports')->group(function () {
        Route::post('/reports/sales', [ReportController::class, 'salesReport'])->middleware('throttle:60,1')->name('reports.sales');
        Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
        Route::post('/reports/returns', [ReportController::class, 'returnsReport'])->middleware('throttle:60,1')->name('reports.returns');
        Route::post('/reports/income-statement', [ReportController::class, 'incomeStatement'])->middleware('throttle:60,1')->name('reports.income-statement');
        Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::post('/reports/account-statement/{account}', [ReportController::class, 'accountStatement'])->name('reports.account-statement');
    });

    // User & Role Management
    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('/users', [UserController::class, 'all'])->name('users.all');
        Route::post('/users', [UserController::class, 'store'])->middleware('throttle:20,1')->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('/roles', [RolePermissionController::class, 'getRoles'])->name('roles.all');
        Route::get('/permissions', [RolePermissionController::class, 'getPermissions'])->name('permissions.all');
        Route::post('/roles', [RolePermissionController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [RolePermissionController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/roles/{role}/permissions', [RolePermissionController::class, 'syncPermissions'])->name('roles.sync-permissions');
        Route::get('/users/{user}/roles', [RolePermissionController::class, 'getUserRoles'])->name('users.roles');
        Route::post('/users/{user}/roles', [RolePermissionController::class, 'assignUserRole'])->name('users.assign-role');
    });
});

// Stock Reconciliation #21
Route::middleware(['auth', 'permission:add_stock', 'throttle:30,1'])->group(function () {
    Route::post('/stock/reconcile', [\App\Http\Controllers\StockReconciliationController::class, 'reconcile'])->name('stock.reconcile');
    Route::get('/stock/audit-trail/{productId}', [\App\Http\Controllers\StockReconciliationController::class, 'auditTrail'])->name('stock.audit-trail');
});

// ── تسوية الخزينة ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'permission:view_pos', 'throttle:60,1'])->group(function () {
    Route::get('/cash-session/current',     [\App\Http\Controllers\CashRegisterController::class, 'currentSession'])->name('cash-session.current');
    Route::post('/cash-session/open',       [\App\Http\Controllers\CashRegisterController::class, 'open'])->name('cash-session.open');
    Route::post('/cash-session/{id}/close', [\App\Http\Controllers\CashRegisterController::class, 'close'])->name('cash-session.close');
    Route::get('/cash-session/history',     [\App\Http\Controllers\CashRegisterController::class, 'history'])->name('cash-session.history');

    // تقارير الربحية
    Route::middleware('permission:view_reports')->group(function () {
        Route::post('/reports/profit-by-product', [\App\Http\Controllers\ProfitReportController::class, 'byProduct'])->name('reports.profit-product');
        Route::post('/reports/profit-daily',      [\App\Http\Controllers\ProfitReportController::class, 'daily'])->name('reports.profit-daily');
    });
});
