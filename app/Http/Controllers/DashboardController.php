<?php

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/DashboardController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function data()
    {
        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        $todaySales = Invoice::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as count, SUM(final_total) as total')
            ->first();

        $yesterdaySales = Invoice::whereDate('created_at', $yesterday)
            ->where('status', 'completed')
            ->selectRaw('SUM(final_total) as total')
            ->first();

        $todayTotal = $todaySales->total ?? 0;
        $yesterdayTotal = $yesterdaySales->total ?? 0;
        $growth = $yesterdayTotal > 0
            ? round((($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100, 2)
            : 0;

        $recentInvoices = Invoice::where('status', 'completed')
            ->latest()->limit(5)
            ->get(['invoice_number', 'total', 'final_total', 'payment_method', 'cashier_name', 'created_at']);

        $recentMovements = StockMovement::latest()->limit(5)->get();

        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->whereDate('invoices.created_at', $today)
            ->where('invoices.status', 'completed')
            ->selectRaw('products.name, SUM(invoice_items.quantity) as total_quantity, SUM(invoice_items.subtotal) as total_sales')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)->get();

        return response()->json([
            'today_sales_count' => $todaySales->count ?? 0,
            'today_sales_total' => $todayTotal,
            'yesterday_sales_total' => $yesterdayTotal,
            'growth_percentage' => $growth,
            'low_stock_count' => Product::whereRaw('quantity <= min_stock AND quantity > 0')->count(),
            'out_of_stock_count' => Product::where('quantity', 0)->count(),
            'total_products' => Product::count(),
            'total_suppliers' => Supplier::count(),
            'total_revenue' => Invoice::where('status', 'completed')->sum('final_total'),
            'recent_invoices' => $recentInvoices,
            'recent_movements' => $recentMovements,
            'top_products' => $topProducts,
        ]);
    }
}
