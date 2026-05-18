<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use App\Models\TaxCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaxCategoryController extends Controller
{
    public function all(): JsonResponse
    {
        return response()->json(TaxCategory::orderBy('rate')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'    => 'required|string|max:100',
            'name_en'    => 'required|string|max:100',
            'code'       => 'required|string|max:20|unique:tax_categories,code',
            'rate'       => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            TaxCategory::where('is_default', true)->update(['is_default' => false]);
        }

        $category = TaxCategory::create($data);

        return response()->json($category, 201);
    }

    public function update(Request $request, TaxCategory $taxCategory): JsonResponse
    {
        $data = $request->validate([
            'name_ar'    => 'sometimes|string|max:100',
            'name_en'    => 'sometimes|string|max:100',
            'code'       => ['sometimes', 'string', 'max:20', Rule::unique('tax_categories', 'code')->ignore($taxCategory->id)],
            'rate'       => 'sometimes|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            TaxCategory::where('is_default', true)->where('id', '!=', $taxCategory->id)->update(['is_default' => false]);
        }

        $taxCategory->update($data);

        return response()->json($taxCategory);
    }

    public function destroy(TaxCategory $taxCategory): JsonResponse
    {
        if ($taxCategory->is_default) {
            return response()->json(['message' => __('tax.cannot_delete_default')], 422);
        }

        $taxCategory->products()->update(['tax_category_id' => null]);
        $taxCategory->delete();

        return response()->json(null, 204);
    }

    /**
     * Tax compliance report: revenue and tax collected per category within a date range.
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $rows = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.date', [$request->from, $request->to])
            ->select(
                'invoice_items.tax_rate',
                DB::raw('SUM(invoice_items.subtotal)   AS taxable_amount'),
                DB::raw('SUM(invoice_items.tax_amount) AS tax_collected'),
                DB::raw('COUNT(DISTINCT invoices.id)   AS invoice_count'),
            )
            ->groupBy('invoice_items.tax_rate')
            ->orderByDesc('tax_collected')
            ->get();

        return response()->json([
            'from'    => $request->from,
            'to'      => $request->to,
            'by_rate' => $rows->map(fn($r) => [
                'tax_rate'      => (float) $r->tax_rate,
                'taxable_amount' => round((float) $r->taxable_amount, 2),
                'tax_collected' => round((float) $r->tax_collected, 2),
                'invoice_count' => (int) $r->invoice_count,
            ])->values(),
            'totals'  => [
                'taxable_amount' => round($rows->sum('taxable_amount'), 2),
                'tax_collected'  => round($rows->sum('tax_collected'), 2),
            ],
        ]);
    }
}
