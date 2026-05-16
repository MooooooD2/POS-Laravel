<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseService
{
    public function all(array $filters): LengthAwarePaginator
    {
        return Expense::with('category')
            ->when($filters['category_id'] ?? null, fn($q) => $q->where('category_id', $filters['category_id']))
            ->when($filters['payment_method'] ?? null, fn($q) => $q->where('payment_method', $filters['payment_method']))
            ->when($filters['date_from'] ?? null, fn($q) => $q->whereDate('expense_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] ?? null, fn($q) => $q->whereDate('expense_date', '<=', $filters['date_to']))
            ->latest('expense_date')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $number = SequenceService::next('expense', 'EXP');

            $expense = Expense::create([
                'expense_number' => $number,
                'category_id'    => $data['category_id'] ?? null,
                'title'          => $data['title'],
                'amount'         => round((float) $data['amount'], 2),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference'      => $data['reference'] ?? null,
                'expense_date'   => $data['expense_date'],
                'notes'          => $data['notes'] ?? null,
                'created_by'     => Auth::id(),
                'created_by_name'=> Auth::user()->full_name,
            ]);

            Log::channel('audit')->info('expense.created', [
                'expense_number' => $number,
                'title'          => $expense->title,
                'amount'         => $expense->amount,
                'payment_method' => $expense->payment_method,
                'user_id'        => Auth::id(),
                'timestamp'      => now()->toIso8601String(),
            ]);

            return $expense->load('category');
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $expense->update([
                'category_id'    => $data['category_id'] ?? $expense->category_id,
                'title'          => $data['title'] ?? $expense->title,
                'amount'         => isset($data['amount']) ? round((float) $data['amount'], 2) : $expense->amount,
                'payment_method' => $data['payment_method'] ?? $expense->payment_method,
                'reference'      => $data['reference'] ?? $expense->reference,
                'expense_date'   => $data['expense_date'] ?? $expense->expense_date,
                'notes'          => $data['notes'] ?? $expense->notes,
            ]);

            Log::channel('audit')->info('expense.updated', [
                'expense_number' => $expense->expense_number,
                'user_id'        => Auth::id(),
                'timestamp'      => now()->toIso8601String(),
            ]);

            return $expense->fresh('category');
        });
    }

    public function delete(Expense $expense): void
    {
        Log::channel('audit')->info('expense.deleted', [
            'expense_number' => $expense->expense_number,
            'amount'         => $expense->amount,
            'user_id'        => Auth::id(),
            'timestamp'      => now()->toIso8601String(),
        ]);
        $expense->delete();
    }

    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return ExpenseCategory::where('is_active', true)->orderBy('name')->get();
    }

    public function summary(string $dateFrom, string $dateTo): array
    {
        $rows = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get();

        $grandTotal = $rows->sum('total');

        return [
            'by_category' => $rows->map(fn($r) => [
                'category' => $r->category?->name ?? __('pos.uncategorized'),
                'total'    => round((float) $r->total, 2),
                'count'    => (int) $r->count,
            ]),
            'grand_total' => round((float) $grandTotal, 2),
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
        ];
    }
}
