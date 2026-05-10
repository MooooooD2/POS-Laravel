<?php
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->where('locale', 'ar|en')->name('lang.switch');
Route::get('/lang/{locale}/translations', [LanguageController::class, 'getTranslations'])->where('locale', 'ar|en')->name('lang.translations');

// ── Authenticated web views ───────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/session-info', [AuthController::class, 'sessionInfo'])->name('session.info');
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock'])->name('dashboard.low-stock');
    Route::middleware(['permission:view_pos'])->group(function () {
        Route::get('/pos', [InvoiceController::class, 'posPage'])->name('pos');
    });
    Route::middleware(['permission:view_returns'])->get('/returns', fn() => view('returns.index'))->name('returns');
    Route::middleware(['permission:view_warehouse'])->group(function () {
        Route::get('/warehouse', fn() => view('warehouse.index'))->name('warehouse');
        Route::get('/suppliers', fn() => view('suppliers.index'))->name('suppliers');
        Route::get('/purchase-orders', fn() => view('purchase-orders.index'))->name('purchase-orders');
        Route::get('/supplier-payments', fn() => view('supplier-payments.index'))->name('supplier-payments');
        Route::get('/supplier-accounts', fn() => view('supplier-accounts.index'))->name('supplier-accounts');
    });
    Route::middleware(['permission:view_accounting'])->group(function () {
        Route::get('/accounting', fn() => view('accounting.index'))->name('accounting');
        Route::get('/financial-reports', fn() => view('financial-reports.index'))->name('financial-reports');
        Route::get('/settings', fn() => view('settings.index'))->name('settings');
    });
    Route::middleware(['permission:view_reports'])->group(function () {
        Route::get('/reports', fn() => view('reports.index'))->name('reports');
        Route::get('/reports/export/sales',   [ReportController::class, 'exportSales'])->name('reports.export.sales');
        Route::get('/reports/export/returns', [ReportController::class, 'exportReturns'])->name('reports.export.returns');
        Route::get('/reports/export/stock',   [ReportController::class, 'exportStock'])->name('reports.export.stock');
    });
    Route::middleware(['permission:manage_roles'])->get('/roles', fn() => view('roles.index'))->name('roles');
});

// تسوية الخزينة
Route::middleware(['auth', 'permission:view_pos'])->group(function () {
    Route::get('/cash-register', fn() => view('cash-register.index'))->name('cash-register');
});

// تقارير الربحية
Route::middleware(['auth', 'permission:view_reports'])->group(function () {
    Route::get('/profit-reports', fn() => view('profit-reports.index'))->name('profit-reports');
});
