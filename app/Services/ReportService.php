<?php
namespace App\Services;

use App\Contracts\Repositories\ReportRepositoryInterface;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PurchaseReturn;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private ReportRepositoryInterface $reportRepo,
        private AccountingService         $accountingService,
    ) {}

    public function salesReport(array $filters): array
    {
        $start = $filters['start_date'];
        $end   = $filters['end_date'] . ' 23:59:59';
        return $this->reportRepo->salesReport($start, $end, $filters);
    }

    public function salesReportForExport(array $filters): \Illuminate\Support\Collection
    {
        $start = $filters['start_date'];
        $end   = $filters['end_date'] . ' 23:59:59';
        return $this->reportRepo->salesReportAll($start, $end, $filters);
    }

    public function returnsReport(array $filters): array
    {
        return $this->reportRepo->returnsReport(
            $filters['start_date'],
            $filters['end_date'],
            $filters['status'] ?? null
        );
    }

    public function returnsReportForExport(array $filters): \Illuminate\Support\Collection
    {
        return $this->reportRepo->returnsReportAll(
            $filters['start_date'],
            $filters['end_date'],
            $filters['status'] ?? null
        );
    }

    public function stockReport(): array
    {
        return $this->reportRepo->stockReport();
    }

    public function incomeStatement(string $start, string $end): array
    {
        return $this->accountingService->incomeStatement($start, $end);
    }

    public function balanceSheet(): array
    {
        return $this->accountingService->balanceSheet();
    }

    public function accountStatement(Account $account, string $start, string $end): array
    {
        return $this->reportRepo->accountStatement($account, $start, $end);
    }

    public function profitByProduct(array $filters): array
    {
        return $this->reportRepo->profitByProduct(
            $filters['start_date'],
            $filters['end_date'],
            $filters['category'] ?? null
        );
    }

    public function profitDaily(array $filters): array
    {
        return $this->reportRepo->profitDaily($filters['start_date'], $filters['end_date']);
    }

    public function inventoryMovements(array $filters): array
    {
        $start   = $filters['start_date'];
        $end     = $filters['end_date'] . ' 23:59:59';
        $perPage = (int) ($filters['per_page'] ?? 50);

        $query = DB::table('stock_movements')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'stock_movements.warehouse_id')
            ->whereBetween('stock_movements.created_at', [$start, $end]);

        if (!empty($filters['product_id'])) {
            $query->where('stock_movements.product_id', $filters['product_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('stock_movements.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['movement_type'])) {
            $query->where('stock_movements.movement_type', $filters['movement_type']);
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn($q) => $q
                ->where('stock_movements.product_name', 'like', "%{$s}%")
                ->orWhere('stock_movements.reason', 'like', "%{$s}%")
            );
        }

        $totals = (clone $query)
            ->selectRaw('
                SUM(CASE WHEN movement_type LIKE "%add%" OR movement_type IN("purchase_order","return","adjustment_add","transfer_in") THEN quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN movement_type LIKE "%sale%" OR movement_type IN("adjustment_remove","transfer_out") THEN quantity ELSE 0 END) as total_out,
                COUNT(*) as total_rows
            ')
            ->first();

        $rows = $query
            ->selectRaw('
                stock_movements.id,
                stock_movements.product_id,
                stock_movements.product_name,
                stock_movements.quantity,
                stock_movements.balance_after,
                stock_movements.movement_type,
                stock_movements.reference_type,
                stock_movements.reference_id,
                stock_movements.reason,
                stock_movements.employee_name,
                stock_movements.created_at,
                warehouses.name as warehouse_name
            ')
            ->orderByDesc('stock_movements.created_at')
            ->paginate($perPage);

        return [
            'movements'  => $rows,
            'totals'     => [
                'total_in'   => (int) ($totals->total_in ?? 0),
                'total_out'  => (int) ($totals->total_out ?? 0),
                'total_rows' => (int) ($totals->total_rows ?? 0),
            ],
            'start_date' => $start,
            'end_date'   => $filters['end_date'],
        ];
    }

    public function agedReceivables(): array
    {
        $buckets = [
            'current'  => [0, 30],
            '31_60'    => [31, 60],
            '61_90'    => [61, 90],
            'over_90'  => [91, PHP_INT_MAX],
        ];

        $rows = DB::table('customers')
            ->join('customer_accounts', 'customers.id', '=', 'customer_accounts.customer_id')
            ->join('invoices', function ($j) {
                $j->on('invoices.id', '=', 'customer_accounts.reference_id')
                  ->where('customer_accounts.reference_type', 'invoice');
            })
            ->where('customer_accounts.debit', '>', 0)
            ->where('customers.balance', '>', 0)
            ->selectRaw('
                customers.id,
                customers.name,
                customers.phone,
                invoices.invoice_number,
                customer_accounts.debit as amount,
                DATEDIFF(NOW(), invoices.date) as age_days
            ')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $bucket = 'over_90';
            foreach ($buckets as $key => [$min, $max]) {
                if ($row->age_days >= $min && $row->age_days <= $max) {
                    $bucket = $key;
                    break;
                }
            }
            $cid = $row->id;
            if (!isset($result[$cid])) {
                $result[$cid] = [
                    'customer_id' => $cid,
                    'name'        => $row->name,
                    'phone'       => $row->phone,
                    'current'     => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0, 'total' => 0,
                ];
            }
            $result[$cid][$bucket] += $row->amount;
            $result[$cid]['total']  += $row->amount;
        }

        return [
            'rows'   => array_values($result),
            'totals' => [
                'current' => collect($result)->sum('current'),
                '31_60'   => collect($result)->sum('31_60'),
                '61_90'   => collect($result)->sum('61_90'),
                'over_90' => collect($result)->sum('over_90'),
                'total'   => collect($result)->sum('total'),
            ],
        ];
    }

    public function agedPayables(): array
    {
        $buckets = [
            'current'  => [0, 30],
            '31_60'    => [31, 60],
            '61_90'    => [61, 90],
            'over_90'  => [91, PHP_INT_MAX],
        ];

        $rows = DB::table('suppliers')
            ->join('supplier_accounts', 'suppliers.id', '=', 'supplier_accounts.supplier_id')
            ->join('purchase_orders', function ($j) {
                $j->on('purchase_orders.id', '=', 'supplier_accounts.reference_id')
                  ->where('supplier_accounts.transaction_type', 'purchase_order');
            })
            ->where('supplier_accounts.debit', '>', 0)
            ->selectRaw('
                suppliers.id,
                suppliers.name,
                suppliers.phone,
                purchase_orders.po_number,
                supplier_accounts.debit as amount,
                DATEDIFF(NOW(), purchase_orders.order_date) as age_days
            ')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $bucket = 'over_90';
            foreach ($buckets as $key => [$min, $max]) {
                if ($row->age_days >= $min && $row->age_days <= $max) {
                    $bucket = $key;
                    break;
                }
            }
            $sid = $row->id;
            if (!isset($result[$sid])) {
                $result[$sid] = [
                    'supplier_id' => $sid,
                    'name'        => $row->name,
                    'phone'       => $row->phone,
                    'current'     => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0, 'total' => 0,
                ];
            }
            $result[$sid][$bucket] += $row->amount;
            $result[$sid]['total']  += $row->amount;
        }

        return [
            'rows'   => array_values($result),
            'totals' => [
                'current' => collect($result)->sum('current'),
                '31_60'   => collect($result)->sum('31_60'),
                '61_90'   => collect($result)->sum('61_90'),
                'over_90' => collect($result)->sum('over_90'),
                'total'   => collect($result)->sum('total'),
            ],
        ];
    }

    public function bestSellingProducts(string $start, string $end, int $limit = 20): array
    {
        $endOfDay = $end . ' 23:59:59';

        $products = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('products', 'products.id', '=', 'invoice_items.product_id')
            ->whereBetween('invoices.created_at', [$start, $endOfDay])
            ->where('invoices.status', 'completed')
            ->selectRaw('
                invoice_items.product_id,
                invoice_items.product_name,
                products.barcode,
                products.category,
                SUM(invoice_items.quantity) as total_qty,
                SUM(invoice_items.subtotal) as total_revenue,
                SUM(invoice_items.quantity * invoice_items.cost_price) as total_cost,
                COUNT(DISTINCT invoices.id) as invoice_count
            ')
            ->groupBy('invoice_items.product_id', 'invoice_items.product_name', 'products.barcode', 'products.category')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn($r) => array_merge((array) $r, [
                'gross_profit'        => round($r->total_revenue - $r->total_cost, 2),
                'gross_profit_margin' => $r->total_revenue > 0
                    ? round(($r->total_revenue - $r->total_cost) / $r->total_revenue * 100, 2)
                    : 0,
            ]));

        return [
            'products'   => $products,
            'start_date' => $start,
            'end_date'   => $end,
        ];
    }

    public function cashierPerformance(string $start, string $end): array
    {
        $endOfDay = $end . ' 23:59:59';

        $stats = DB::table('invoices')
            ->whereBetween('created_at', [$start, $endOfDay])
            ->where('status', 'completed')
            ->selectRaw('
                cashier_id,
                cashier_name,
                COUNT(*) as invoice_count,
                SUM(final_total) as total_sales,
                AVG(final_total) as avg_invoice,
                SUM(discount) as total_discount,
                SUM(tax_amount) as total_tax,
                MAX(final_total) as max_invoice
            ')
            ->groupBy('cashier_id', 'cashier_name')
            ->orderByDesc('total_sales')
            ->get();

        $returnsByUser = DB::table('sales_returns')
            ->whereBetween('return_date', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('processed_by, SUM(total_amount) as total_returns, COUNT(*) as return_count')
            ->groupBy('processed_by')
            ->get()
            ->keyBy('processed_by');

        $result = $stats->map(function ($row) use ($returnsByUser) {
            $ret = $returnsByUser->get($row->cashier_id);
            return [
                'cashier_id'     => $row->cashier_id,
                'cashier_name'   => $row->cashier_name,
                'invoice_count'  => (int) $row->invoice_count,
                'total_sales'    => round($row->total_sales, 2),
                'avg_invoice'    => round($row->avg_invoice, 2),
                'max_invoice'    => round($row->max_invoice, 2),
                'total_discount' => round($row->total_discount, 2),
                'total_tax'      => round($row->total_tax, 2),
                'total_returns'  => $ret ? round($ret->total_returns, 2) : 0,
                'return_count'   => $ret ? (int) $ret->return_count : 0,
                'net_sales'      => round($row->total_sales - ($ret ? $ret->total_returns : 0), 2),
            ];
        });

        return [
            'cashiers'   => $result,
            'start_date' => $start,
            'end_date'   => $end,
            'totals'     => [
                'invoice_count'  => $result->sum('invoice_count'),
                'total_sales'    => round($result->sum('total_sales'), 2),
                'total_returns'  => round($result->sum('total_returns'), 2),
                'net_sales'      => round($result->sum('net_sales'), 2),
            ],
        ];
    }

    public function nearExpiryProducts(int $days = 30): array
    {
        $cutoff = now()->addDays($days)->toDateString();

        $batches = DB::table('product_batches')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'product_batches.warehouse_id')
            ->whereNotNull('product_batches.expiry_date')
            ->where('product_batches.expiry_date', '<=', $cutoff)
            ->where('product_batches.remaining_qty', '>', 0)
            ->where('product_batches.status', '!=', 'exhausted')
            ->selectRaw('
                product_batches.id as batch_id,
                product_batches.batch_number,
                product_batches.expiry_date,
                product_batches.remaining_qty,
                product_batches.status,
                products.id as product_id,
                products.name as product_name,
                products.barcode,
                products.category,
                products.price,
                warehouses.name as warehouse_name,
                DATEDIFF(product_batches.expiry_date, NOW()) as days_to_expiry
            ')
            ->orderBy('product_batches.expiry_date')
            ->get();

        $expired = $batches->where('days_to_expiry', '<', 0);
        $expiring = $batches->where('days_to_expiry', '>=', 0);

        return [
            'days_window'    => $days,
            'expired_count'  => $expired->count(),
            'expiring_count' => $expiring->count(),
            'expired'        => $expired->values(),
            'expiring_soon'  => $expiring->values(),
        ];
    }

    public function cashFlowReport(string $start, string $end): array
    {
        $endOfDay = $end . ' 23:59:59';

        // Inflows: sales by payment method
        $salesInflows = InvoicePayment::whereHas(
            'invoice',
            fn($q) => $q->whereBetween('created_at', [$start, $endOfDay])
                        ->whereNotIn('status', ['cancelled', 'draft'])
        )->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        // Inflows: purchase returns refunded in cash
        $purchaseReturnRefunds = (float) PurchaseReturn::whereBetween('return_date', [$start, $end])
            ->where('refund_method', 'cash')
            ->where('status', 'completed')
            ->sum('total_amount');

        // Outflows: supplier payments
        $supplierPaymentsByMethod = SupplierPayment::whereBetween('payment_date', [$start, $end])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        // Outflows: operating expenses
        $expensesByCategory = Expense::whereBetween('expense_date', [$start, $end])
            ->with('category:id,name')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(fn($r) => [
                ($r->category?->name ?? __('pos.uncategorized')) => (float) $r->total,
            ]);

        $totalSalesInflow      = (float) $salesInflows->sum();
        $totalSupplierOutflow  = (float) $supplierPaymentsByMethod->sum();
        $totalExpenseOutflow   = (float) $expensesByCategory->sum();
        $totalInflows          = $totalSalesInflow + $purchaseReturnRefunds;
        $totalOutflows         = $totalSupplierOutflow + $totalExpenseOutflow;

        // Daily breakdown
        $dailyInflows = InvoicePayment::whereHas(
            'invoice',
            fn($q) => $q->whereBetween('created_at', [$start, $endOfDay])
                        ->whereNotIn('status', ['cancelled', 'draft'])
        )->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $dailyExpenses = Expense::whereBetween('expense_date', [$start, $end])
            ->selectRaw('expense_date as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $dailyDates = collect($dailyInflows->keys()->merge($dailyExpenses->keys())->unique()->sort());

        $dailyRows = $dailyDates->map(fn($date) => [
            'date'     => $date,
            'inflow'   => round((float) ($dailyInflows[$date] ?? 0), 2),
            'outflow'  => round((float) ($dailyExpenses[$date] ?? 0), 2),
            'net'      => round((float) ($dailyInflows[$date] ?? 0) - (float) ($dailyExpenses[$date] ?? 0), 2),
        ])->values();

        return [
            'period'        => ['from' => $start, 'to' => $end],
            'inflows'       => [
                'sales'              => $salesInflows->map(fn($v) => round((float) $v, 2))->toArray(),
                'purchase_refunds'   => round($purchaseReturnRefunds, 2),
                'total'              => round($totalInflows, 2),
            ],
            'outflows'      => [
                'supplier_payments'  => $supplierPaymentsByMethod->map(fn($v) => round((float) $v, 2))->toArray(),
                'expenses'           => $expensesByCategory->map(fn($v) => round($v, 2))->toArray(),
                'total'              => round($totalOutflows, 2),
            ],
            'net_cash_flow' => round($totalInflows - $totalOutflows, 2),
            'daily'         => $dailyRows,
        ];
    }

    public function budgetVsActual(int $year, ?int $month = null): array
    {
        $months = $month ? [$month] : range(1, 12);

        $rows = [];
        foreach ($months as $m) {
            $startDate = sprintf('%04d-%02d-01', $year, $m);
            $endDate   = date('Y-m-t', strtotime($startDate));
            $endOfDay  = $endDate . ' 23:59:59';

            // Actual revenue: completed invoices final_total
            $actualRevenue = (float) Invoice::whereBetween('date', [$startDate, $endOfDay])
                ->where('status', 'completed')
                ->sum('final_total');

            // Actual expenses by category
            $actualExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
                ->with('category:id,name')
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->get()
                ->mapWithKeys(fn($r) => [
                    ($r->category?->name ?? __('pos.uncategorized')) => (float) $r->total,
                ]);

            $actualTotalExpense = $actualExpenses->sum();

            // Budgeted amounts for this month
            $budgets = Budget::where('year', $year)->where('month', $m)->get();

            $budgetRevenue       = (float) $budgets->where('type', 'revenue')->sum('amount');
            $budgetExpenseByType = $budgets->where('type', 'expense')
                ->mapWithKeys(fn($b) => [$b->category ?? __('pos.general') => (float) $b->amount]);
            $budgetTotalExpense  = (float) $budgetExpenseByType->sum();

            $revenueVariance = $actualRevenue - $budgetRevenue;
            $expenseVariance = $budgetTotalExpense - $actualTotalExpense; // positive = underspent (good)

            $rows[] = [
                'year'                   => $year,
                'month'                  => $m,
                'month_label'            => date('F', mktime(0, 0, 0, $m, 1)),
                'revenue' => [
                    'budget'          => round($budgetRevenue, 2),
                    'actual'          => round($actualRevenue, 2),
                    'variance'        => round($revenueVariance, 2),
                    'variance_pct'    => $budgetRevenue > 0 ? round($revenueVariance / $budgetRevenue * 100, 2) : null,
                ],
                'expenses' => [
                    'budget'          => round($budgetTotalExpense, 2),
                    'actual'          => round($actualTotalExpense, 2),
                    'variance'        => round($expenseVariance, 2),
                    'variance_pct'    => $budgetTotalExpense > 0 ? round($expenseVariance / $budgetTotalExpense * 100, 2) : null,
                    'by_category'     => [
                        'budget' => $budgetExpenseByType->map(fn($v) => round($v, 2))->toArray(),
                        'actual' => $actualExpenses->map(fn($v) => round($v, 2))->toArray(),
                    ],
                ],
                'net' => [
                    'budget' => round($budgetRevenue - $budgetTotalExpense, 2),
                    'actual' => round($actualRevenue - $actualTotalExpense, 2),
                ],
            ];
        }

        return [
            'year'    => $year,
            'month'   => $month,
            'rows'    => $rows,
            'totals'  => [
                'revenue' => [
                    'budget' => round(collect($rows)->sum('revenue.budget'), 2),
                    'actual' => round(collect($rows)->sum('revenue.actual'), 2),
                ],
                'expenses' => [
                    'budget' => round(collect($rows)->sum('expenses.budget'), 2),
                    'actual' => round(collect($rows)->sum('expenses.actual'), 2),
                ],
            ],
        ];
    }
}
