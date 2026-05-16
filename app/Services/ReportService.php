<?php
namespace App\Services;

use App\Contracts\Repositories\ReportRepositoryInterface;
use App\Models\Account;
use App\Models\Expense;
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
}
