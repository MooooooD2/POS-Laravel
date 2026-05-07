<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function __construct(private AccountingService $accountingService) {}

    public function index()
    {
        return view('reports.index');
    }

    public function financialReports()
    {
        return view('financial-reports.index');
    }

    /**
     * FIX-06: تقرير المبيعات — حساب في DB بدل تحميل كل الفواتير في الذاكرة
     */
    public function salesReport(Request $request)
    {
        $data = $request->validate([
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'payment_method' => 'nullable|in:cash,card,transfer,wallet',
            'cashier_id'     => 'nullable|exists:users,id',
        ]);

        $start = $data['start_date'];
        $end   = $data['end_date'] . ' 23:59:59';

        $baseQuery = Invoice::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($data['payment_method']))
            $baseQuery->where('payment_method', $data['payment_method']);
        if (!empty($data['cashier_id']))
            $baseQuery->where('cashier_id', $data['cashier_id']);

        // FIX-06: الإجماليات تُحسب في قاعدة البيانات — لا تحميل للذاكرة
        $totals = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_revenue, SUM(tax_amount) as total_tax, SUM(discount) as total_discount')
            ->first();

        // التجميع حسب الدفع في DB
        $byPayment = (clone $baseQuery)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(final_total) as total')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->selectRaw('invoice_items.product_name, SUM(invoice_items.quantity) as total_qty, SUM(invoice_items.subtotal) as total_sales')
            ->groupBy('invoice_items.product_id', 'invoice_items.product_name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        // FIX-06: Pagination بدل get() بدون حد
        $invoices = (clone $baseQuery)
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'invoices'       => $invoices,
            'total_revenue'  => $totals->total_revenue  ?? 0,
            'total_tax'      => $totals->total_tax      ?? 0,
            'total_discount' => $totals->total_discount ?? 0,
            'total_count'    => $totals->total_count    ?? 0,
            'by_payment'     => $byPayment,
            'top_products'   => $topProducts,
        ]);
    }

    /**
     * FIX-06: تقرير المرتجعات — DB aggregates بدل PHP
     */
    public function returnsReport(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'nullable|in:completed,cancelled',
        ]);

        $query = SalesReturn::whereBetween('return_date', [$data['start_date'], $data['end_date']]);
        if (!empty($data['status'])) $query->where('status', $data['status']);

        // الإجماليات في DB
        $totals = (clone $query)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_count, SUM(total_amount) as total_returned')
            ->first();

        $topReturnedProducts = DB::table('return_items')
            ->join('sales_returns', 'return_items.return_id', '=', 'sales_returns.id')
            ->whereBetween('sales_returns.return_date', [$data['start_date'], $data['end_date']])
            ->where('sales_returns.status', 'completed')
            ->selectRaw('return_items.product_name, SUM(return_items.quantity) as total_qty, SUM(return_items.subtotal) as total_amount')
            ->groupBy('return_items.product_id', 'return_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $returns = (clone $query)->with(['items'])->orderByDesc('return_date')->paginate(50);

        return response()->json([
            'returns'               => $returns,
            'total_returned'        => $totals->total_returned ?? 0,
            'total_count'           => $totals->total_count    ?? 0,
            'top_returned_products' => $topReturnedProducts,
        ]);
    }

    /**
     * FIX: تقرير المخزون مع Cache
     */
    public function stockReport()
    {
        $data = Cache::remember('stock_report', 120, function () {
            $products = Product::orderBy('category')->orderBy('name')->get()
                ->map(fn($p) => array_merge($p->toArray(), [
                    'stock_value'     => $p->quantity * $p->cost_price,
                    'potential_value' => $p->quantity * $p->price,
                    'low_stock'       => $p->low_stock,
                ]));

            return [
                'products'          => $products,
                'total_stock_value' => $products->sum('stock_value'),
                'low_stock_count'   => $products->where('low_stock', true)->count(),
                'out_of_stock'      => $products->where('quantity', 0)->count(),
            ];
        });

        return response()->json($data);
    }

    public function incomeStatement(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);
        return response()->json($this->accountingService->incomeStatement($data['start_date'], $data['end_date']));
    }

    public function balanceSheet()
    {
        return response()->json($this->accountingService->balanceSheet());
    }

    public function accountStatement(Request $request, Account $account)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);

        $lines = $account->lines()
            ->with('entry')
            ->whereHas('entry', fn($q) => $q->whereBetween('entry_date', [$data['start_date'], $data['end_date']]))
            ->get();

        return response()->json([
            'account'      => $account,
            'lines'        => $lines,
            'total_debit'  => $lines->sum('debit'),
            'total_credit' => $lines->sum('credit'),
            'net_balance'  => $lines->sum('debit') - $lines->sum('credit'),
        ]);
    }
}
