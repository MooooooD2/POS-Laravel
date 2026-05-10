<?php
namespace App\Contracts\Repositories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

interface AccountRepositoryInterface
{
    public function allWithTree(): Collection;
    public function findOrFail(int $id): Account;
    public function create(array $data): Account;
    public function update(Account $account, array $data): Account;
    public function delete(Account $account): void;
    public function hasChildren(Account $account): bool;
    public function hasLines(Account $account): bool;
    public function rootsByType(string $type): Collection;
    public function totalsByType(string $type, string $start, string $end): array;
    public function updateBalance(Account $account, float $debit, float $credit): void;
}
