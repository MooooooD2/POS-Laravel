<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private AccountingService $accountingService) {}

    public function index() { return view('accounting.index'); }

    public function allAccounts()
    {
        return $this->success(['accounts' => Account::with('children', 'parent')->orderBy('account_code')->get()]);
    }

    public function storeAccount(StoreAccountRequest $request)
    {
        $this->authorize('create', Account::class);
        $account = Account::create($request->validated());
        $this->audit('account.created', Account::class, (int) $account->id);
        return $this->success(['account' => $account], '', 201);
    }

    public function updateAccount(Request $request, Account $account)
    {
        $this->authorize('update', $account);
        $data = $request->validate([
            'account_name' => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
        ]);
        $account->update($data);
        $this->audit('account.updated', Account::class, (int) $account->id);
        return $this->success(['account' => $account]);
    }

    public function destroyAccount(Account $account)
    {
        $this->authorize('delete', $account);
        if ($account->children()->exists() || $account->lines()->exists()) {
            return $this->error(__('pos.account_has_dependencies'), 422);
        }
        $account->delete();
        $this->audit('account.deleted', Account::class, (int) $account->id);
        return $this->success();
    }

    public function allJournalEntries(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);
        $query = JournalEntry::with('lines.account', 'creator')->orderByDesc('entry_date');
        if ($request->filled('start_date')) $query->where('entry_date', '>=', $request->start_date);
        if ($request->filled('end_date'))   $query->where('entry_date', '<=', $request->end_date);
        return $this->success(['entries' => $query->paginate(20)]);
    }

    public function storeJournalEntry(StoreJournalEntryRequest $request)
    {
        $this->authorize('create_journal_entry');
        $data        = $request->validated();
        $totalDebit  = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.001) {
            return $this->error(__('pos.journal_entry_not_balanced', [
                'debit'  => $totalDebit,
                'credit' => $totalCredit,
            ]));
        }

        try {
            $entry = $this->accountingService->createJournalEntry($data);
            $this->audit('journal.created', JournalEntry::class, (int) $entry->id);
            return $this->success(['entry' => $entry], '', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
