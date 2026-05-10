<?php
namespace App\Repositories;

use App\Contracts\Repositories\AccountRepositoryInterface;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class AccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    public function __construct()
    {
        $this->model = new Account();
    }

    public function allWithTree(): Collection
    {
        return Account::with('children', 'parent')->orderBy('account_code')->get();
    }

    public function findOrFail(int $id): Account
    {
        return Account::findOrFail($id);
    }

    public function create(array $data): Account
    {
        return Account::create($data);
    }

    public function update(Account|\Illuminate\Database\Eloquent\Model $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    public function delete(Account|\Illuminate\Database\Eloquent\Model $account): void
    {
        $account->delete();
    }

    public function hasChildren(Account $account): bool
    {
        return $account->children()->exists();
    }

    public function hasLines(Account $account): bool
    {
        return $account->lines()->exists();
    }

    public function rootsByType(string $type): Collection
    {
        return Account::where('account_type', $type)->with('children')->whereNull('parent_id')->get();
    }

    public function totalsByType(string $type, string $start, string $end): array
    {
        $col = in_array($type, ['asset', 'expense']) ? 'debit' : 'credit';

        return Account::where('account_type', $type)
            ->whereNotNull('parent_id')
            ->withSum([
                'lines as total' => fn($q) => $q->whereHas(
                    'entry',
                    fn($q2) => $q2->whereBetween('entry_date', [$start, $end])
                )
            ], $col)
            ->get()
            ->toArray();
    }

    public function updateBalance(Account $account, float $debit, float $credit): void
    {
        if (in_array($account->account_type, ['asset', 'expense'])) {
            $account->increment('balance', $debit - $credit);
        } else {
            $account->increment('balance', $credit - $debit);
        }
    }
}
