<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\SupplierAccountController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;

/*
|--------------------------------------------------------------------------
| Web Routes - مسارات الويب
|--------------------------------------------------------------------------
*/

// Auth Routes - مسارات المصادقة
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/session-info', [AuthController::class, 'sessionInfo'])->name('session.info');

// Protected Routes - المسارات المحمية
Route::middleware(['auth'])->group(function () {

    // Home & Dashboard - الرئيسية ولوحة التحكم
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard-data', [DashboardController::class, 'data'])->name('dashboard.data');

    // POS / Cashier - نقطة البيع / الكاشير
    Route::get('/pos', [InvoiceController::class, 'posPage'])->name('pos');
    Route::get('/api/search-product', [InvoiceController::class, 'searchProduct'])->name('products.search');
    Route::post('/api/invoices', [InvoiceController::class, 'createInvoice'])->name('invoices.create');
    Route::get('/api/invoices', [InvoiceController::class, 'getByNumber'])->name('invoices.by-number');
    Route::get('/api/invoices/{invoice}/returnable-items', [InvoiceController::class, 'returnableItems'])->name('invoices.returnable-items');

    // Warehouse / Products - المخزن / المنتجات
    Route::get('/warehouse', [ProductController::class, 'index'])->name('warehouse');
    Route::get('/api/products', [ProductController::class, 'all'])->name('products.all');
    Route::post('/api/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/api/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/api/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/api/products/{product}/add-stock', [ProductController::class, 'addStock'])->name('products.add-stock');

    // Suppliers - الموردين
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers');
    Route::get('/api/suppliers', [SupplierController::class, 'all'])->name('suppliers.all');
    Route::post('/api/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/api/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/api/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Purchase Orders - أوامر الشراء
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders');
    Route::get('/api/purchase-orders', [PurchaseOrderController::class, 'all'])->name('purchase-orders.all');
    Route::post('/api/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::post('/api/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');

    // Supplier Payments - مدفوعات الموردين
    Route::get('/supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments');
    Route::get('/api/supplier-payments', [SupplierPaymentController::class, 'all'])->name('supplier-payments.all');
    Route::post('/api/supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');

    // Supplier Accounts - حسابات الموردين
    Route::get('/supplier-accounts', [SupplierAccountController::class, 'index'])->name('supplier-accounts');
    Route::get('/api/supplier-accounts/{supplier}', [SupplierAccountController::class, 'show'])->name('supplier-accounts.show');

    // Accounting - المحاسبة
    Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting');
    Route::get('/api/accounts', [AccountingController::class, 'allAccounts'])->name('accounts.all');
    Route::post('/api/accounts', [AccountingController::class, 'storeAccount'])->name('accounts.store');
    Route::put('/api/accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accounts.update');
    Route::delete('/api/accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accounts.destroy');
    Route::get('/api/journal-entries', [AccountingController::class, 'allJournalEntries'])->name('journal-entries.all');
    Route::post('/api/journal-entries', [AccountingController::class, 'storeJournalEntry'])->name('journal-entries.store');

    // Financial Reports - التقارير المالية
    Route::get('/financial-reports', [ReportController::class, 'financialReports'])->name('financial-reports');
    Route::post('/api/reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('/api/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::post('/api/reports/account-statement/{account}', [ReportController::class, 'accountStatement'])->name('reports.account-statement');

    // Sales Reports - تقارير المبيعات
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::post('/api/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
    Route::get('/api/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');

    // Returns - المرتجعات
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns');
    Route::post('/api/returns', [ReturnController::class, 'store'])->name('returns.store');

    // Language Switch - تغيير اللغة
    Route::get('/lang/{locale}', function ($locale) {
        if (in_array($locale, ['ar', 'en'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    })->name('lang.switch');
});
