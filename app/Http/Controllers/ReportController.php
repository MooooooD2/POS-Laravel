<?php
namespace App\Http\Controllers;

use App\Exports\ReturnsReportExport;
use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Models\Account;
use App\Models\AuditLog as AuditLogModel;
use App\Models\User;
use App\Services\InventoryValuationService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        private ReportService              $reportService,
        private InventoryValuationService  $valuationService,
    ) {}

    public function index()
    {
        return view('reports.index');
    }

    public function financialReports()
    {
        Gate::authorize('report.financial');
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

        return response()->json($this->reportService->salesReport($data));
    }

    public function returnsReport(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'nullable|in:completed,cancelled',
        ]);

        return response()->json($this->reportService->returnsReport($data));
    }

    public function stockReport()
    {
        return response()->json($this->reportService->stockReport());
    }

    public function exportSales(Request $request)
    {
        $data = $request->validate([
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'payment_method' => 'nullable|in:cash,card,transfer,wallet',
            'format'         => 'required|in:csv,pdf',
        ]);

        $invoices  = $this->reportService->salesReportForExport($data);
        $filename  = "sales-{$data['start_date']}-{$data['end_date']}";

        if ($data['format'] === 'pdf') {
            $totals = $this->reportService->salesReport($data)['totals'];
            return Pdf::loadView('reports.pdf.sales', [
                'invoices' => $invoices,
                'totals'   => $totals,
                'start'    => $data['start_date'],
                'end'      => $data['end_date'],
            ])->setPaper('a4', 'landscape')->download("{$filename}.pdf");
        }

        return Excel::download(new SalesReportExport($invoices), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportReturns(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'nullable|in:completed,cancelled',
            'format'     => 'required|in:csv,pdf',
        ]);

        $returns  = $this->reportService->returnsReportForExport($data);
        $filename = "returns-{$data['start_date']}-{$data['end_date']}";

        if ($data['format'] === 'pdf') {
            $totals = $this->reportService->returnsReport($data)['totals'];
            return Pdf::loadView('reports.pdf.returns', [
                'returns' => $returns,
                'totals'  => $totals,
                'start'   => $data['start_date'],
                'end'     => $data['end_date'],
            ])->setPaper('a4', 'landscape')->download("{$filename}.pdf");
        }

        return Excel::download(new ReturnsReportExport($returns), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportStock(Request $request)
    {
        $data   = $request->validate(['format' => 'required|in:csv,pdf']);
        $report = $this->reportService->stockReport();

        if ($data['format'] === 'pdf') {
            return Pdf::loadView('reports.pdf.stock', $report)
                ->setPaper('a4', 'landscape')
                ->download('stock-report.pdf');
        }

        return Excel::download(
            new StockReportExport(collect($report['products'])),
            'stock-report.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    public function incomeStatement(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json($this->reportService->incomeStatement($data['start_date'], $data['end_date']));
    }

    public function balanceSheet()
    {
        return response()->json($this->reportService->balanceSheet());
    }

    public function accountStatement(Request $request, Account $account)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);

        return response()->json(
            $this->reportService->accountStatement($account, $data['start_date'], $data['end_date'])
        );
    }

    public function cashFlowReport(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reportService->cashFlowReport($data['start_date'], $data['end_date'])
        );
    }

    public function inventoryMovements(Request $request)
    {
        $data = $request->validate([
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'product_id'    => 'nullable|integer|exists:products,id',
            'warehouse_id'  => 'nullable|integer|exists:warehouses,id',
            'movement_type' => 'nullable|string|max:50',
            'search'        => 'nullable|string|max:100',
            'per_page'      => 'nullable|integer|min:10|max:200',
        ]);

        return response()->json($this->reportService->inventoryMovements($data));
    }

    public function agedReceivables()
    {
        Gate::authorize('report.aged');
        return response()->json($this->reportService->agedReceivables());
    }

    public function agedPayables()
    {
        Gate::authorize('report.aged');
        return response()->json($this->reportService->agedPayables());
    }

    public function bestSellingProducts(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'limit'      => 'nullable|integer|min:5|max:100',
        ]);

        return response()->json(
            $this->reportService->bestSellingProducts($data['start_date'], $data['end_date'], (int) ($data['limit'] ?? 20))
        );
    }

    public function cashierPerformance(Request $request)
    {
        Gate::authorize('report.cashier-performance');
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->reportService->cashierPerformance($data['start_date'], $data['end_date'])
        );
    }

    public function nearExpiryProducts(Request $request)
    {
        $data = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        return response()->json($this->reportService->nearExpiryProducts((int) ($data['days'] ?? 30)));
    }

    /**
     * Inventory valuation report — compares WAC, FIFO, and LIFO values side by side.
     */
    public function inventoryValuation(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        return response()->json($this->valuationService->valuationReport($warehouseId));
    }

    /**
     * Permissions audit report — current permission matrix + recent changes + auth failures.
     */
    public function permissionsAudit(Request $request)
    {
        Gate::authorize('report.permissions-audit');
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $since = now()->subDays($request->integer('days', 30));

        // Current permission matrix: all users with their roles and effective permissions
        $matrix = User::with('roles.permissions')
            ->orderBy('username')
            ->get()
            ->map(fn($u) => [
                'id'          => $u->id,
                'username'    => $u->username,
                'full_name'   => $u->full_name,
                'is_active'   => (bool) $u->is_active,
                'roles'       => $u->getRoleNames()->values(),
                'permissions' => $u->getAllPermissions()->pluck('name')->sort()->values(),
            ]);

        // Recent role/permission changes from the audit trail
        $permissionChanges = AuditLogModel::whereIn('action', [
                'role.created', 'role.updated', 'role.deleted',
                'role.permissions_synced', 'user.role_assigned',
            ])
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['action', 'model', 'record_id', 'username', 'ip_address', 'changes', 'created_at']);

        // Auth event summary over the period
        $authSummary = AuditLogModel::selectRaw('action, COUNT(*) as count')
            ->whereIn('action', ['auth.login_success', 'auth.login_failed', 'auth.login_blocked', 'auth.logout'])
            ->where('created_at', '>=', $since)
            ->groupBy('action')
            ->pluck('count', 'action');

        // Top failed-login IPs (potential brute-force)
        $suspiciousIps = AuditLogModel::where('action', 'auth.login_failed')
            ->where('created_at', '>=', $since)
            ->selectRaw('ip_address, COUNT(*) as attempts')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        return response()->json([
            'period_days'        => $request->integer('days', 30),
            'matrix'             => $matrix,
            'permission_changes' => $permissionChanges,
            'auth_summary'       => $authSummary,
            'suspicious_ips'     => $suspiciousIps,
        ]);
    }
}
