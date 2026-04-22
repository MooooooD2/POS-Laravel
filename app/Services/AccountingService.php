<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    /**
     * Create journal entry - إنشاء قيد يومية
     */
    public function createJournalEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            // Validate debit = credit - التحقق من توازن القيد
            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \Exception(__('pos.journal_unbalanced'));
            }

            $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad(
                JournalEntry::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => Auth::user()->id,
            ]);

            foreach ($data['lines'] as $line) {
                JournalEntryLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);

                // Update account balance - تحديث رصيد الحساب
                $account = Account::find($line['account_id']);
                $this->updateAccountBalance($account, $line['debit'] ?? 0, $line['credit'] ?? 0);
            }

            return $entry->load('lines.account');
        });
    }

    private function updateAccountBalance(Account $account, float $debit, float $credit): void
    {
        if (in_array($account->account_type, ['asset', 'expense'])) {
            $account->increment('balance', $debit - $credit);
        } else {
            $account->increment('balance', $credit - $debit);
        }
    }

    /**
     * Generate income statement - إعداد قائمة الدخل
     */
    public function incomeStatement(string $startDate, string $endDate): array
    {
        $revenues = $this->getAccountsTotals('revenue', $startDate, $endDate);
        $expenses = $this->getAccountsTotals('expense', $startDate, $endDate);

        $totalRevenue = collect($revenues)->sum('total');
        $totalExpense = collect($expenses)->sum('total');
        $netIncome = $totalRevenue - $totalExpense;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome');
    }

    /**
     * Generate balance sheet - إعداد الميزانية العمومية
     */
    public function balanceSheet(): array
    {
        $assets = Account::where('account_type', 'asset')->with('children')->whereNull('parent_id')->get();
        $liabilities = Account::where('account_type', 'liability')->with('children')->whereNull('parent_id')->get();
        $equity = Account::where('account_type', 'equity')->with('children')->whereNull('parent_id')->get();

        return compact('assets', 'liabilities', 'equity');
    }

    private function getAccountsTotals(string $type, string $start, string $end): array
    {
        return Account::where('account_type', $type)
            ->whereNotNull('parent_id')
            ->withSum([
                'lines as total' => function ($q) use ($start, $end) {
                    $q->whereHas(
                        'entry',
                        fn($q2) =>
                        $q2->whereBetween('entry_date', [$start, $end])
                    );
                }
            ], in_array($type, ['asset', 'expense']) ? 'debit' : 'credit')
            ->get()
            ->toArray();
    }
}
