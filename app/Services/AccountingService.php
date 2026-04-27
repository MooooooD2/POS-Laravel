<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function __construct(private SequenceService $sequenceService) {}

    /**
     * Create a balanced journal entry.
     */
    public function createJournalEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $totalDebit  = collect($data['lines'])->sum(fn($l) => (float) ($l['debit']  ?? 0));
            $totalCredit = collect($data['lines'])->sum(fn($l) => (float) ($l['credit'] ?? 0));

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \Exception(__('pos.journal_unbalanced'));
            }

            $entryNumber = $this->sequenceService->next('journal_entry', 'JE');

            $entry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $data['entry_date'],
                'description'    => $data['description'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id'] ?? null,
                'created_by'     => Auth::id(),
            ]);

            foreach ($data['lines'] as $line) {
                $debit  = (float) ($line['debit']  ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                JournalEntryLine::create([
                    'entry_id'    => $entry->id,
                    'account_id'  => $line['account_id'],
                    'debit'       => $debit,
                    'credit'      => $credit,
                    'description' => $line['description'] ?? null,
                ]);

                $account = Account::findOrFail($line['account_id']);
                $this->updateAccountBalance($account, $debit, $credit);
            }

            return $entry->load('lines.account');
        });
    }

    private function updateAccountBalance(Account $account, float $debit, float $credit): void
    {
        // Asset & Expense: debit increases balance; Credit & Liability & Equity: credit increases
        $delta = in_array($account->account_type, ['asset', 'expense'])
            ? $debit - $credit
            : $credit - $debit;

        $account->increment('balance', $delta);
    }

    /**
     * Income statement for a date range.
     */
    public function incomeStatement(string $startDate, string $endDate): array
    {
        $revenues = $this->getAccountsTotals('revenue', $startDate, $endDate);
        $expenses = $this->getAccountsTotals('expense', $startDate, $endDate);

        $totalRevenue = collect($revenues)->sum('total');
        $totalExpense = collect($expenses)->sum('total');
        $netIncome    = $totalRevenue - $totalExpense;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome');
    }

    /**
     * Balance sheet snapshot (current balances).
     */
    public function balanceSheet(): array
    {
        $fetch = fn(string $type) => Account::where('account_type', $type)
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        $assets      = $fetch('asset');
        $liabilities = $fetch('liability');
        $equity      = $fetch('equity');

        $totalAssets      = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity      = $equity->sum('balance');

        return compact('assets', 'liabilities', 'equity', 'totalAssets', 'totalLiabilities', 'totalEquity');
    }

    private function getAccountsTotals(string $type, string $start, string $end): array
    {
        $creditSide = in_array($type, ['asset', 'expense']) ? 'debit' : 'credit';

        return Account::where('account_type', $type)
            ->whereNotNull('parent_id')
            ->withSum([
                'lines as total' => function ($q) use ($start, $end) {
                    $q->whereHas(
                        'entry',
                        fn($q2) => $q2->whereBetween('entry_date', [$start, $end])
                    );
                },
            ], $creditSide)
            ->get()
            ->toArray();
    }
}
