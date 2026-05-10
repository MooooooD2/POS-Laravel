<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ReportService;
use Illuminate\Http\Request;

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
