<?php
namespace App\Services;

use App\Contracts\Repositories\ReportRepositoryInterface;
use App\Models\Account;

class ReportService
{
    public function __construct(
        private ReportRepositoryInterface $reportRepo,
        private AccountingService         $accountingService,
    ) {}

    public function salesReport(array $filters): array
    {
        $start = $filters['start_date'];
        $end   = $filters['end_date'] . ' 23:59:59';
        return $this->reportRepo->salesReport($start, $end, $filters);
    }

    public function returnsReport(array $filters): array
    {
        return $this->reportRepo->returnsReport(
            $filters['start_date'],
            $filters['end_date'],
            $filters['status'] ?? null
        );
    }

    public function stockReport(): array
    {
        return $this->reportRepo->stockReport();
    }

    public function incomeStatement(string $start, string $end): array
    {
        return $this->accountingService->incomeStatement($start, $end);
    }

    public function balanceSheet(): array
    {
        return $this->accountingService->balanceSheet();
    }

    public function accountStatement(Account $account, string $start, string $end): array
    {
        return $this->reportRepo->accountStatement($account, $start, $end);
    }

    public function profitByProduct(array $filters): array
    {
        return $this->reportRepo->profitByProduct(
            $filters['start_date'],
            $filters['end_date'],
            $filters['category'] ?? null
        );
    }

    public function profitDaily(array $filters): array
    {
        return $this->reportRepo->profitDaily($filters['start_date'], $filters['end_date']);
    }
}
