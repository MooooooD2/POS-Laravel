<?php
namespace App\Services;

use App\Contracts\Repositories\AccountRepositoryInterface;
use App\Contracts\Repositories\JournalEntryRepositoryInterface;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function __construct(
        private AccountRepositoryInterface      $accountRepo,
        private JournalEntryRepositoryInterface $journalRepo,
    ) {}

    public function createJournalEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $totalDebit  = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \Exception(__('pos.journal_unbalanced'));
            }

            $entry = $this->journalRepo->create([
                'entry_number'   => SequenceService::next('journal'),
                'entry_date'     => $data['entry_date'],
                'description'    => $data['description'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id'] ?? null,
                'created_by'     => Auth::id(),
            ]);

            foreach ($data['lines'] as $line) {
                $this->journalRepo->createLine([
                    'entry_id'    => $entry->id,
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit'] ?? 0,
                    'credit'      => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);

                $account = $this->accountRepo->findOrFail($line['account_id']);
                $this->accountRepo->updateBalance($account, $line['debit'] ?? 0, $line['credit'] ?? 0);
            }

            return $entry->load('lines.account');
        });
    }

    public function incomeStatement(string $startDate, string $endDate): array
    {
        $revenues = $this->accountRepo->totalsByType('revenue', $startDate, $endDate);
        $expenses = $this->accountRepo->totalsByType('expense', $startDate, $endDate);

        $totalRevenue = collect($revenues)->sum('total');
        $totalExpense = collect($expenses)->sum('total');
        $netIncome    = $totalRevenue - $totalExpense;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome');
    }

    public function balanceSheet(): array
    {
        $assets      = $this->accountRepo->rootsByType('asset');
        $liabilities = $this->accountRepo->rootsByType('liability');
        $equity      = $this->accountRepo->rootsByType('equity');

        return compact('assets', 'liabilities', 'equity');
    }
}
