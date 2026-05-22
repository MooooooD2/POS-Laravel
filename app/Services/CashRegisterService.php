<?php
namespace App\Services;

use App\Contracts\Repositories\CashRegisterSessionRepositoryInterface;
use App\Models\CashRegisterSession;
use App\Models\CashSessionMovement;
use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Models\Setting;
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
                'cashier_name'   => Auth::user()?->full_name ?? '',
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

        // Link to financial reports: auto-create journal entry if accounting is configured
        $this->postSessionToAccounting($session, $stats, round($actualCash, 2));

        return $session->fresh();
    }

    /**
     * Record a manual cash drawer movement (deposit or withdrawal) during an open session.
     * Returns the movement plus any threshold warnings.
     *
     * @return array{movement: CashSessionMovement, warnings: string[]}
     */
    public function recordMovement(CashRegisterSession $session, string $type, float $amount, ?string $reason): array
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

        // Daily withdrawal limit check
        if ($type === 'withdrawal') {
            $maxDailyWithdrawal = (float) Setting::get('max_daily_withdrawal', 0);
            if ($maxDailyWithdrawal > 0) {
                $todayWithdrawals = CashSessionMovement::whereHas('session', fn($q) => $q->where('cashier_id', Auth::id()))
                    ->where('type', 'withdrawal')
                    ->whereDate('created_at', today())
                    ->sum('amount');

                if ($todayWithdrawals + $amount > $maxDailyWithdrawal) {
                    throw new \Exception(__('pos.daily_withdrawal_limit_exceeded', [
                        'limit' => number_format($maxDailyWithdrawal, 2),
                        'used'  => number_format($todayWithdrawals, 2),
                    ]));
                }
            }
        }

        $movement = CashSessionMovement::create([
            'cash_session_id' => $session->id,
            'type'            => $type,
            'amount'          => round($amount, 2),
            'reason'          => $reason,
            'user_id'         => Auth::id(),
        ]);

        // Low balance alert check after withdrawal
        $warnings = [];
        if ($type === 'withdrawal') {
            $minBalance = (float) Setting::get('min_cash_balance', 0);
            if ($minBalance > 0) {
                $estimatedBalance = $this->estimatedCashBalance($session);
                if ($estimatedBalance < $minBalance) {
                    $warnings[] = __('pos.low_cash_balance_alert', [
                        'balance' => number_format($estimatedBalance, 2),
                        'min'     => number_format($minBalance, 2),
                    ]);
                    Log::channel('audit')->warning('cash_session.low_balance', [
                        'session_id'        => $session->id,
                        'cashier_id'        => Auth::id(),
                        'estimated_balance' => $estimatedBalance,
                        'min_balance'       => $minBalance,
                    ]);
                }
            }
        }

        return compact('movement', 'warnings');
    }

    /**
     * Estimate the current cash balance in an open session.
     */
    public function estimatedCashBalance(CashRegisterSession $session): float
    {
        $stats       = $this->calcSessionStats($session);
        $movements   = CashSessionMovement::where('cash_session_id', $session->id)->get();
        $deposits    = $movements->where('type', 'deposit')->sum('amount');
        $withdrawals = $movements->where('type', 'withdrawal')->sum('amount');

        return round(
            $session->opening_amount + $stats['cash_sales'] - $stats['cash_returns'] + $deposits - $withdrawals,
            2
        );
    }

    public function history(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user      = Auth::user();
        $canSeeAll = $user->hasPermissionTo('view_reports') || $user->hasPermissionTo('manage_roles');
        return $this->sessionRepo->history($filters, $canSeeAll, $user->id);
    }

    /**
     * Optionally post a summary journal entry when a session closes.
     * Only runs if accounts tagged 'cash_account' and 'revenue_account' exist.
     */
    private function postSessionToAccounting(CashRegisterSession $session, array $stats, float $actualCash): void
    {
        try {
            $cashAccount    = \App\Models\Account::where('account_code', Setting::get('cash_account_code', ''))->first();
            $revenueAccount = \App\Models\Account::where('account_code', Setting::get('revenue_account_code', ''))->first();

            if (!$cashAccount || !$revenueAccount || $stats['total_sales'] <= 0) {
                return;
            }

            $entryNumber = 'CASH-SES-' . $session->session_number;
            if (\App\Models\JournalEntry::where('reference_type', 'cash_session')
                    ->where('reference_id', $session->id)->exists()) {
                return; // already posted
            }

            $entry = \App\Models\JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $session->closed_at?->toDateString() ?? today()->toDateString(),
                'description'    => 'تسوية جلسة الكاشير ' . $session->session_number,
                'reference_type' => 'cash_session',
                'reference_id'   => $session->id,
                'created_by'     => $session->cashier_id,
                'is_posted'      => true,
            ]);

            // Debit cash account (actual cash received)
            \App\Models\JournalEntryLine::create([
                'entry_id'    => $entry->id,
                'account_id'  => $cashAccount->id,
                'debit'       => $actualCash,
                'credit'      => 0,
                'description' => 'إيرادات نقدية - جلسة ' . $session->session_number,
            ]);

            // Credit revenue account (total sales)
            \App\Models\JournalEntryLine::create([
                'entry_id'    => $entry->id,
                'account_id'  => $revenueAccount->id,
                'debit'       => 0,
                'credit'      => round($stats['total_sales'], 2),
                'description' => 'إيرادات مبيعات - جلسة ' . $session->session_number,
            ]);
        } catch (\Throwable $e) {
            // Non-fatal: accounting link is optional; log and continue
            Log::warning('cash_session.accounting_link_failed', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);
        }
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

        $returnsQuery = SalesReturn::where('processed_by', $session->cashier_id)
            ->where('status', 'completed')
            ->whereBetween('return_date', [$from->toDateString(), $to->toDateString()]);

        // Total returns for reporting
        $totalReturns = (float) $returnsQuery->sum('total_amount');

        // Only cash refunds reduce the expected cash balance in the drawer
        $cashReturns = (float) $returnsQuery->where('refund_method', 'cash')->sum('refund_amount');

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
            'total_returns'  => $totalReturns,
            'cash_returns'   => $cashReturns,
            'invoices_count' => $invoicesCount,
        ];
    }
}
