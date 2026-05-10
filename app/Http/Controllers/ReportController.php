<?php
namespace App\Http\Controllers;

use App\Exports\ReturnsReportExport;
use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Models\Account;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

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
}
