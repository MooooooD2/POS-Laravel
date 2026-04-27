<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function salesReport(Request $request)
    {
        $data = $request->validate([
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'payment_method' => 'nullable|in:cash,card,transfer,wallet',
            'cashier_id'     => 'nullable|exists:users,id',
        ]);

        $query = Invoice::with('items')
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $data['start_date'] . ' 00:00:00',
                $data['end_date']   . ' 23:59:59',
            ]);

        if (!empty($data['payment_method'])) {
            $query->where('payment_method', $data['payment_method']);
        }
        if (!empty($data['cashier_id'])) {
            $query->where('cashier_id', $data['cashier_id']);
        }

        $invoices     = $query->orderByDesc('created_at')->get();
        $totalRevenue = $invoices->sum('final_total');
        $totalCount   = $invoices->count();

        $byPayment = $invoices->groupBy('payment_method')
            ->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('final_total')]);

        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.created_at', [
                $data['start_date'] . ' 00:00:00',
                $data['end_date']   . ' 23:59:59',
            ])
            ->selectRaw('invoice_items.product_name, SUM(invoice_items.quantity) as total_qty, SUM(invoice_items.subtotal) as total_sales')
            ->groupBy('invoice_items.product_id', 'invoice_items.product_name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        // Profit calculation using stored cost_price on invoice items
        $totalCost   = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.created_at', [
                $data['start_date'] . ' 00:00:00',
                $data['end_date']   . ' 23:59:59',
            ])
            ->sum(DB::raw('invoice_items.cost_price * invoice_items.quantity'));

        return response()->json([
            'invoices'      => $invoices,
            'total_revenue' => $totalRevenue,
            'total_cost'    => $totalCost,
            'gross_profit'  => $totalRevenue - $totalCost,
            'total_count'   => $totalCount,
            'by_payment'    => $byPayment,
            'top_products'  => $topProducts,
        ]);
    }

    public function stockReport()
    {
        $products = Product::orderBy('category')->orderBy('name')->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'stock_value'     => $p->quantity * $p->cost_price,
                'potential_value' => $p->quantity * $p->price,
                'profit_margin'   => $p->price > 0
                    ? round((($p->price - $p->cost_price) / $p->price) * 100, 2)
                    : 0,
                'low_stock'       => $p->low_stock,
            ]));

        return response()->json([
            'products'          => $products,
            'total_stock_value' => $products->sum('stock_value'),
            'low_stock_count'   => $products->where('low_stock', true)->count(),
            'out_of_stock'      => $products->where('quantity', 0)->count(),
        ]);
    }

    public function returnsReport(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $returns = \App\Models\SalesReturn::with('items')
            ->where('status', 'completed')
            ->whereBetween('return_date', [$data['start_date'], $data['end_date']])
            ->orderByDesc('return_date')
            ->get();

        return response()->json([
            'returns'      => $returns,
            'total_amount' => $returns->sum('total_amount'),
            'total_count'  => $returns->count(),
        ]);
    }

    public function incomeStatement(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->accountingService->incomeStatement($data['start_date'], $data['end_date'])
        );
    }

    public function balanceSheet()
    {
        return response()->json($this->accountingService->balanceSheet());
    }

    public function accountStatement(Request $request, Account $account)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $lines = $account->lines()
            ->with('entry')
            ->whereHas('entry', fn($q) => $q->whereBetween('entry_date', [$data['start_date'], $data['end_date']]))
            ->orderBy(
                \App\Models\JournalEntry::select('entry_date')
                    ->whereColumn('journal_entry_lines.entry_id', 'journal_entries.id')
            )
            ->get();

        return response()->json([
            'account'       => $account,
            'lines'         => $lines,
            'total_debit'   => $lines->sum('debit'),
            'total_credit'  => $lines->sum('credit'),
            'net_balance'   => $lines->sum('debit') - $lines->sum('credit'),
        ]);
    }
}
