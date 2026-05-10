<?php
namespace App\Contracts\Repositories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

interface ReportRepositoryInterface
{
    public function salesReport(string $start, string $end, array $filters): array;
    public function returnsReport(string $start, string $end, ?string $status): array;
    public function stockReport(): array;
    public function profitByProduct(string $start, string $end, ?string $category): array;
    public function profitDaily(string $start, string $end): array;
    public function accountStatement(Account $account, string $start, string $end): array;
}
