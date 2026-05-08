<?php
namespace App\Http\Controllers;

use App\Models\CashRegisterSession;
use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Services\SequenceService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    use ApiResponse, AuditLog;

    /** الجلسة المفتوحة للكاشير الحالي */
    public function currentSession()
    {
        $session = CashRegisterSession::where('cashier_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$session) return $this->success(['session' => null]);

        // حساب المبيعات النقدية منذ فتح الجلسة
        $stats = $this->calcSessionStats($session);
        return $this->success(['session' => array_merge($session->toArray(), $stats)]);
    }

    /** فتح جلسة جديدة */
    public function open(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        // منع فتح جلسة لو في جلسة مفتوحة
        $existing = CashRegisterSession::where('cashier_id', Auth::id())
            ->where('status', 'open')->first();
        if ($existing) {
            return $this->error('يوجد جلسة مفتوحة بالفعل. أغلقها أولاً.', 422);
        }

        $session = CashRegisterSession::create([
            'session_number' => SequenceService::next('session', 'SES'),
            'cashier_id'     => Auth::id(),
            'cashier_name'   => Auth::user()->full_name,
            'opening_amount' => $request->opening_amount,
            'status'         => 'open',
            'notes'          => $request->notes,
            'opened_at'      => now(),
        ]);

        $this->audit('cash_session.opened', CashRegisterSession::class, $session->id, [
            'opening_amount' => $request->opening_amount,
        ]);

        return $this->success(['session' => $session], '', 201);
    }

    /** إغلاق الجلسة وتسوية الخزينة */
    public function close(Request $request, int $id)
    {
        $session = CashRegisterSession::findOrFail($id);

        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:500',
        ]);
        dd($session->cashier_id !== Auth::id());

        if ($session->cashier_id !== Auth::user()->id) {
            return $this->error('لا يمكنك إغلاق جلسة كاشير آخر.', 403);
        }
        if ($session->status === 'closed') {
            return $this->error('الجلسة مغلقة بالفعل.', 422);
        }

        $stats = $this->calcSessionStats($session);

        $expectedCash = $session->opening_amount + $stats['cash_sales'] - $stats['cash_returns'];
        $actualCash   = (float) $request->actual_cash;
        $difference   = $actualCash - $expectedCash;

        DB::transaction(function () use ($session, $stats, $expectedCash, $actualCash, $difference, $request) {
            $session->update([
                'expected_cash'  => round($expectedCash, 2),
                'actual_cash'    => round($actualCash, 2),
                'difference'     => round($difference, 2),
                'total_sales'    => round($stats['total_sales'], 2),
                'total_returns'  => round($stats['total_returns'], 2),
                'total_card'     => round($stats['card_sales'], 2),
                'total_transfer' => round($stats['transfer_sales'], 2),
                'invoices_count' => $stats['invoices_count'],
                'status'         => 'closed',
                'notes'          => $request->notes,
                'closed_at'      => now(),
            ]);
        });

        // تسجيل تحذير لو في عجز
        if ($difference < -5) {
            Log::channel('audit')->warning('cash_session.shortage', [
                'session_id'  => $session->id,
                'cashier_id'  => Auth::id(),
                'expected'    => $expectedCash,
                'actual'      => $actualCash,
                'shortage'    => abs($difference),
                'timestamp'   => now()->toIso8601String(),
            ]);
        }

        $this->audit('cash_session.closed', CashRegisterSession::class, $session->id, [
            'expected'   => $expectedCash,
            'actual'     => $actualCash,
            'difference' => $difference,
        ]);

        return $this->success(['session' => $session->fresh()]);
    }

    /** سجل الجلسات */
    public function history(Request $request)
    {
        $request->validate([
            'cashier_id' => 'nullable|exists:users,id',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
            'status'     => 'nullable|in:open,closed',
        ]);

        $query = CashRegisterSession::with('cashier')->orderByDesc('opened_at');
        if ($request->filled('cashier_id')) $query->where('cashier_id', $request->cashier_id);
        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('date_from'))  $query->whereDate('opened_at', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('opened_at', '<=', $request->date_to);

        return $this->success(['sessions' => $query->paginate(20)]);
    }

    /** حساب إحصائيات الجلسة من الفواتير */
    private function calcSessionStats(CashRegisterSession $session): array
    {
        $from = $session->opened_at->copy()->startOfDay();
        $to   = $session->closed_at ?? now();

        $invoices = Invoice::where('cashier_id', $session->cashier_id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('payment_method, COUNT(*) as cnt, SUM(final_total) as total')
            ->groupBy('payment_method')
            ->get()->keyBy('payment_method');

        $returns = SalesReturn::where('processed_by', $session->cashier_id)
            ->where('status', 'completed')
            ->whereBetween('return_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_amount');

        $cashSales     = $invoices->get('cash')?->total     ?? 0;
        $cardSales     = $invoices->get('card')?->total     ?? 0;
        $transferSales = $invoices->get('transfer')?->total ?? 0;
        $totalSales    = collect($invoices)->sum('total');
        $invoicesCount = collect($invoices)->sum('cnt');

        return [
            'cash_sales'     => $cashSales,
            'card_sales'     => $cardSales,
            'transfer_sales' => $transferSales,
            'total_sales'    => $totalSales,
            'total_returns'  => $returns,
            'cash_returns'   => $returns, // حالياً كل المرتجعات نقدي
            'invoices_count' => $invoicesCount,
        ];
    }
}
