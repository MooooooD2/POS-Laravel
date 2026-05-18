<?php
namespace App\Services;

use App\Contracts\Repositories\CashRegisterSessionRepositoryInterface;
use App\Models\CashRegisterSession;
use App\Models\CashSessionMovement;
use App\Models\Invoice;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterService
{
    public function __construct(private CashRegisterSessionRepositoryInterface $sessionRepo) {}

    public function currentSession(): ?CashRegisterSession
    {
        $session = $this->sessionRepo->currentOpen(Auth::id());
        if (!$session) return null;

        $stats = $this->calcSessionStats($session);
        $session->setRelation('stats', (object) $stats);

        return $session;
    }

    public function open(array $data): CashRegisterSession
    {
        return DB::transaction(function () use ($data) {
            // Lock any existing open sessions to prevent race condition on concurrent opens
            $existing = CashRegisterSession::where('cashier_id', Auth::id())
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new \Exception('يوجد جلسة مفتوحة بالفعل. أغلقها أولاً.');
            }

            return $this->sessionRepo->create([
                'session_number' => SequenceService::next('session', 'SES'),
                'cashier_id'     => Auth::id(),
                'cashier_name'   => Auth::user()->full_name,
                'opening_amount' => $data['opening_amount'],
                'status'         => 'open',
                'notes'          => $data['notes'] ?? null,
                'opened_at'      => now(),
            ]);
        });
    }

    public function close(CashRegisterSession $session, array $data): CashRegisterSession
    {
        $stats        = $this->calcSessionStats($session);
        $expectedCash = $session->opening_amount + $stats['cash_sales'] - $stats['cash_returns'];
        $actualCash   = (float) $data['actual_cash'];
        $difference   = $actualCash - $expectedCash;

        DB::transaction(function () use ($session, $stats, $expectedCash, $actualCash, $difference, $data) {
            // Re-fetch with lock to prevent double-close race condition
            $locked = CashRegisterSession::lockForUpdate()->findOrFail($session->id);

            if ($locked->cashier_id !== Auth::id()) {
                throw new \Exception('لا يمكنك إغلاق جلسة كاشير آخر.');
            }
            if ($locked->status === 'closed') {
                throw new \Exception('الجلسة مغلقة بالفعل.');
            }

            $this->sessionRepo->update($locked, [
                'expected_cash'  => round($expectedCash, 2),
                'actual_cash'    => round($actualCash, 2),
                'difference'     => round($difference, 2),
                'total_sales'    => round($stats['total_sales'], 2),
                'total_returns'  => round($stats['total_returns'], 2),
                'total_card'     => round($stats['card_sales'], 2),
                'total_transfer' => round($stats['transfer_sales'], 2),
                'invoices_count' => $stats['invoices_count'],
                'status'         => 'closed',
                'notes'          => $data['notes'] ?? null,
                'closed_at'      => now(),
            ]);
        });

        if ($difference < -5) {
            Log::channel('audit')->warning('cash_session.shortage', [
                'session_id' => $session->id,
                'cashier_id' => Auth::id(),
                'expected'   => $expectedCash,
                'actual'     => $actualCash,
                'shortage'   => abs($difference),
                'timestamp'  => now()->toIso8601String(),
            ]);
        }

        return $session->fresh();
    }

    /**
     * Record a manual cash drawer movement (deposit or withdrawal) during an open session.
     */
    public function recordMovement(CashRegisterSession $session, string $type, float $amount, ?string $reason): CashSessionMovement
    {
        if ($session->status !== 'open') {
            throw new \Exception('لا يمكن تسجيل حركة على جلسة مغلقة.');
        }
        if ($session->cashier_id !== Auth::id()) {
            throw new \Exception('لا يمكنك تسجيل حركة على جلسة كاشير آخر.');
        }
        if ($amount <= 0) {
            throw new \Exception('يجب أن يكون المبلغ أكبر من صفر.');
        }

        return CashSessionMovement::create([
            'cash_session_id' => $session->id,
            'type'            => $type,
            'amount'          => round($amount, 2),
            'reason'          => $reason,
            'user_id'         => Auth::id(),
        ]);
    }

    public function history(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user      = Auth::user();
        $canSeeAll = $user->hasPermissionTo('view_reports') || $user->hasPermissionTo('manage_roles');
        return $this->sessionRepo->history($filters, $canSeeAll, $user->id);
    }

    public function calcSessionStats(CashRegisterSession $session): array
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
            'cash_returns'   => $returns,
            'invoices_count' => $invoicesCount,
        ];
    }
}
