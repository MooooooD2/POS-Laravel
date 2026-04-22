<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private AccountingService $accountingService) {}

    public function index() { return view('accounting.index'); }

    public function allAccounts()
    {
        $accounts = Account::with('children', 'parent')->orderBy('account_code')->get();
        return response()->json(['accounts' => $accounts]);
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'account_code' => 'required|string|unique:accounts,account_code',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id'    => 'nullable|exists:accounts,id',
            'description'  => 'nullable|string',
        ]);
        $account = Account::create($data);
        return response()->json(['success' => true, 'account' => $account]);
    }

    public function updateAccount(Request $request, Account $account)
    {
        $data = $request->validate([
            'account_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
        ]);
        $account->update($data);
        return response()->json(['success' => true, 'account' => $account]);
    }

    public function destroyAccount(Account $account)
    {
        if ($account->children()->exists() || $account->lines()->exists()) {
            return response()->json(['success' => false, 'message' => __('pos.account_has_dependencies')], 422);
        }
        $account->delete();
        return response()->json(['success' => true]);
    }

    public function allJournalEntries(Request $request)
    {
        $query = JournalEntry::with('lines.account', 'creator')->orderByDesc('entry_date');
        if ($request->start_date) $query->where('entry_date', '>=', $request->start_date);
        if ($request->end_date)   $query->where('entry_date', '<=', $request->end_date);
        return response()->json(['entries' => $query->paginate(20)]);
    }

    public function storeJournalEntry(Request $request)
    {
        $data = $request->validate([
            'entry_date'     => 'required|date',
            'description'    => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id'   => 'nullable|integer',
            'lines'          => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit'      => 'nullable|numeric|min:0',
            'lines.*.credit'     => 'nullable|numeric|min:0',
            'lines.*.description'=> 'nullable|string',
        ]);

        try {
            $entry = $this->accountingService->createJournalEntry($data);
            return response()->json(['success' => true, 'entry' => $entry]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
