<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LanguageController;
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

// ─── Public Routes ──────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/session-info', [AuthController::class, 'sessionInfo'])->name('session.info');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch')
    ->where('locale', 'ar|en');
Route::get('/lang/{locale}/translations', [LanguageController::class, 'getTranslations'])->name('lang.translations')
    ->where('locale', 'ar|en');

// ─── Authenticated Routes ────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard-data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/api/low-stock-products', [DashboardController::class, 'lowStockProducts'])->name('dashboard.low-stock');

    // ── POS ───────────────────────────────────────────────────────────────────
    Route::middleware(['permission:view_pos'])->group(function () {
        Route::get('/pos', [InvoiceController::class, 'posPage'])->name('pos');
        Route::get('/api/search-product', [InvoiceController::class, 'searchProduct'])->name('products.search');
        Route::post('/api/invoices', [InvoiceController::class, 'createInvoice'])->name('invoices.create');
        Route::get('/api/invoices', [InvoiceController::class, 'getByNumber'])->name('invoices.by-number');
        Route::get('/api/invoices/{invoice}/returnable-items', [InvoiceController::class, 'returnableItems'])->name('invoices.returnable-items');
    });

    // ── Returns ───────────────────────────────────────────────────────────────
    Route::middleware(['permission:view_returns'])->group(function () {
        Route::get('/returns', [ReturnController::class, 'index'])->name('returns');
        Route::post('/api/returns', [ReturnController::class, 'store'])->name('returns.store');
    });

    // ── Warehouse / Products ──────────────────────────────────────────────────
    Route::middleware(['permission:view_warehouse'])->group(function () {
        Route::get('/warehouse', [ProductController::class, 'index'])->name('warehouse');
        Route::get('/api/products', [ProductController::class, 'all'])->name('products.all');
        Route::post('/api/products', [ProductController::class, 'store'])->name('products.store')
            ->middleware('permission:add_product');
        Route::put('/api/products/{product}', [ProductController::class, 'update'])->name('products.update')
            ->middleware('permission:edit_product');
        Route::delete('/api/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')
            ->middleware('permission:delete_product');
        Route::post('/api/products/{product}/add-stock', [ProductController::class, 'addStock'])->name('products.add-stock')
            ->middleware('permission:add_stock');

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers');
        Route::get('/api/suppliers', [SupplierController::class, 'all'])->name('suppliers.all');
        Route::post('/api/suppliers', [SupplierController::class, 'store'])->name('suppliers.store')
            ->middleware('permission:add_supplier');
        Route::put('/api/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')
            ->middleware('permission:edit_supplier');
        Route::delete('/api/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy')
            ->middleware('permission:delete_supplier');

        // Purchase Orders
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders');
        Route::get('/api/purchase-orders', [PurchaseOrderController::class, 'all'])->name('purchase-orders.all');
        Route::post('/api/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store')
            ->middleware('permission:create_purchase_order');
        Route::post('/api/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive')
            ->middleware('permission:receive_purchase_order');

        // Supplier Payments
        Route::get('/supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments');
        Route::get('/api/supplier-payments', [SupplierPaymentController::class, 'all'])->name('supplier-payments.all');
        Route::post('/api/supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store')
            ->middleware('permission:create_supplier_payment');

        // Supplier Accounts
        Route::get('/supplier-accounts', [SupplierAccountController::class, 'index'])->name('supplier-accounts');
        Route::get('/api/supplier-accounts/{supplier}', [SupplierAccountController::class, 'show'])->name('supplier-accounts.show');
    });

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::middleware(['permission:view_reports'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::post('/api/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
        Route::get('/api/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
        Route::post('/api/reports/returns', [ReportController::class, 'returnsReport'])->name('reports.returns');
    });

    // ── Accounting & Financial Reports ────────────────────────────────────────
    Route::middleware(['permission:view_accounting'])->group(function () {
        Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting');
        Route::get('/api/accounts', [AccountingController::class, 'allAccounts'])->name('accounts.all');
        Route::post('/api/accounts', [AccountingController::class, 'storeAccount'])->name('accounts.store')
            ->middleware('permission:manage_accounts');
        Route::put('/api/accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accounts.update')
            ->middleware('permission:manage_accounts');
        Route::delete('/api/accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accounts.destroy')
            ->middleware('permission:manage_accounts');
        Route::get('/api/journal-entries', [AccountingController::class, 'allJournalEntries'])->name('journal-entries.all');
        Route::post('/api/journal-entries', [AccountingController::class, 'storeJournalEntry'])->name('journal-entries.store')
            ->middleware('permission:create_journal_entry');

        Route::get('/financial-reports', [ReportController::class, 'financialReports'])->name('financial-reports');
        Route::post('/api/reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
        Route::get('/api/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::post('/api/reports/account-statement/{account}', [ReportController::class, 'accountStatement'])->name('reports.account-statement');
    });

    // ── Settings & User Management (admin) ───────────────────────────────────
    Route::middleware(['permission:view_accounting'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::get('/api/settings', [SettingController::class, 'all'])->name('settings.all');
        Route::post('/api/settings', [SettingController::class, 'update'])->name('settings.update')
            ->middleware('permission:update_settings');
        Route::get('/api/settings/group/{group}', [SettingController::class, 'group'])->name('settings.group');
    });

    // ── Roles, Permissions & Users (admin only) ───────────────────────────────
    Route::middleware(['permission:manage_roles'])->group(function () {
        // Users
        Route::get('/api/users', [UserController::class, 'all'])->name('users.all');
        Route::post('/api/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/api/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/api/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Roles
        Route::get('/api/roles', [RolePermissionController::class, 'getRoles'])->name('roles.all');
        Route::get('/api/permissions', [RolePermissionController::class, 'getPermissions'])->name('permissions.all');
        Route::post('/api/roles', [RolePermissionController::class, 'storeRole'])->name('roles.store');
        Route::put('/api/roles/{role}', [RolePermissionController::class, 'updateRole'])->name('roles.update');
        Route::delete('/api/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/api/roles/{role}/permissions', [RolePermissionController::class, 'syncPermissions'])->name('roles.permissions');
        Route::get('/api/users/{user}/roles', [RolePermissionController::class, 'getUserRoles'])->name('users.roles');
        Route::post('/api/users/{user}/roles', [RolePermissionController::class, 'assignUserRole'])->name('users.assign-role');
    });
});
