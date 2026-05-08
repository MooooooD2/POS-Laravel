<?php
namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitReportController extends Controller
{
    use ApiResponse;

    /** تقرير الربحية حسب المنتج */
    public function byProduct(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'category'   => 'nullable|string|max:100',
        ]);

        $start = $request->start_date;
        $end   = $request->end_date . ' 23:59:59';

        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->selectRaw('
                products.id,
                invoice_items.product_name,
                products.category,
                SUM(invoice_items.quantity)             as total_qty,
                AVG(products.cost_price)                as avg_cost,
                AVG(invoice_items.price)                as avg_sell,
                SUM(invoice_items.subtotal)             as total_revenue,
                SUM(invoice_items.quantity * products.cost_price) as total_cost,
                SUM(invoice_items.subtotal) - SUM(invoice_items.quantity * products.cost_price) as gross_profit
            ')
            ->groupBy('products.id', 'invoice_items.product_name', 'products.category')
            ->orderByDesc('gross_profit');

        if ($request->filled('category')) {
            $query->where('products.category', $request->category);
        }

        $rows = $query->get()->map(function ($r) {
            $r->profit_margin = $r->total_revenue > 0
                ? round(($r->gross_profit / $r->total_revenue) * 100, 2)
                : 0;
            return $r;
        });

        // إجماليات
        $totals = [
            'total_revenue'  => round($rows->sum('total_revenue'), 2),
            'total_cost'     => round($rows->sum('total_cost'), 2),
            'gross_profit'   => round($rows->sum('gross_profit'), 2),
            'profit_margin'  => $rows->sum('total_revenue') > 0
                ? round(($rows->sum('gross_profit') / $rows->sum('total_revenue')) * 100, 2)
                : 0,
            'products_count' => $rows->count(),
        ];

        return $this->success([
            'products' => $rows->values(),
            'totals'   => $totals,
            'period'   => ['start' => $start, 'end' => $request->end_date],
        ]);
    }

    /** تقرير ربحية يومية */
    public function daily(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $rows = DB::table('invoices')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$request->start_date, $request->end_date . ' 23:59:59'])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as invoices_count,
                SUM(final_total) as revenue,
                SUM(discount) as total_discount
            ')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // ضم التكلفة من invoice_items + products
        $costs = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.created_at', [$request->start_date, $request->end_date . ' 23:59:59'])
            ->selectRaw('DATE(invoices.created_at) as date, SUM(invoice_items.quantity * products.cost_price) as cost')
            ->groupByRaw('DATE(invoices.created_at)')
            ->pluck('cost', 'date');

        $daily = $rows->map(function ($r) use ($costs) {
            $cost          = $costs[$r->date] ?? 0;
            $r->cost       = round($cost, 2);
            $r->profit     = round($r->revenue - $cost, 2);
            $r->margin     = $r->revenue > 0 ? round(($r->profit / $r->revenue) * 100, 2) : 0;
            return $r;
        });

        return $this->success([
            'daily'  => $daily,
            'totals' => [
                'revenue' => round($daily->sum('revenue'), 2),
                'cost'    => round($daily->sum('cost'), 2),
                'profit'  => round($daily->sum('profit'), 2),
            ],
        ]);
    }
}
