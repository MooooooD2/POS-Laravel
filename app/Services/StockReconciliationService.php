<?php
namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * #21 نظام مراجعة المخزون — اكتشاف الاختلافات بين الرصيد الفعلي والمسجّل
 */
class StockReconciliationService
{
    public function __construct(private StockService $stockService) {}

    /**
     * مقارنة الكميات الفعلية بما هو مسجّل في النظام
     */
    public function reconcile(array $physicalCounts): array
    {
        $discrepancies = [];
        $reconciled    = [];

        foreach ($physicalCounts as $item) {
            $product  = Product::findOrFail($item['product_id']);
            $system   = $product->quantity;
            $physical = (int) $item['physical_count'];
            $diff     = $physical - $system;

            $discrepancies[] = [
                'product_id'     => $product->id,
                'product_name'   => $product->name,
                'system_qty'     => $system,
                'physical_qty'   => $physical,
                'difference'     => $diff,
                'status'         => $diff === 0 ? 'match' : ($diff > 0 ? 'surplus' : 'shortage'),
            ];

            if ($diff !== 0 && ($item['auto_adjust'] ?? false)) {
                $this->stockService->adjustStock(
                    $product,
                    $physical,
                    $item['reason'] ?? 'جرد دوري — تعديل تلقائي'
                );
                $reconciled[] = $product->id;
            }
        }

        return [
            'discrepancies'   => $discrepancies,
            'reconciled_ids'  => $reconciled,
            'total_checked'   => count($physicalCounts),
            'total_matched'   => collect($discrepancies)->where('status', 'match')->count(),
            'total_discrepant'=> collect($discrepancies)->where('status', '!=', 'match')->count(),
            'reconciled_at'   => now()->toDateTimeString(),
            'performed_by'    => Auth::user()?->full_name,
        ];
    }

    /**
     * تقرير حركات منتج بعينه للتدقيق
     */
    public function productAuditTrail(int $productId, string $from, string $to): array
    {
        $movements = StockMovement::where('product_id', $productId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        $opening = StockMovement::where('product_id', $productId)
            ->where('created_at', '<', $from)
            ->latest()
            ->value('balance_after') ?? 0;

        return [
            'product'        => Product::findOrFail($productId)->only(['id','name','quantity']),
            'period'         => ['from' => $from, 'to' => $to],
            'opening_balance'=> $opening,
            'closing_balance'=> $movements->last()?->balance_after ?? $opening,
            'movements'      => $movements,
            'total_in'       => $movements->whereIn('movement_type', ['add','return','purchase','initial','adjustment_add'])->sum('quantity'),
            'total_out'      => $movements->whereIn('movement_type', ['sale','adjustment_remove'])->sum('quantity'),
        ];
    }
}
