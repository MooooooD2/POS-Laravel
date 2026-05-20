<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\WasteRecord;
use App\Services\StockService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WasteController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private StockService $stockService) {}

    public function index()
    {
        return view('warehouse.waste');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'batch_id'     => 'nullable|exists:product_batches,id',
            'quantity'     => 'required|numeric|min:0.001',
            'reason'       => 'required|in:expired,damaged,theft,other',
            'notes'        => 'nullable|string|max:500',
        ]);

        $data['notes'] = isset($data['notes']) ? strip_tags($data['notes']) : null;

        $record = DB::transaction(function () use ($data) {
            $product  = Product::lockForUpdate()->findOrFail($data['product_id']);
            $unitCost = $product->avg_cost > 0 ? $product->avg_cost : $product->cost_price;
            $qty      = (float) $data['quantity'];

            if ($product->quantity < $qty) {
                throw new \Exception(__('pos.insufficient_stock', ['name' => $product->name]));
            }

            $waste = WasteRecord::create([
                'product_id'   => $product->id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'batch_id'     => $data['batch_id'] ?? null,
                'quantity'     => $qty,
                'unit_cost'    => $unitCost,
                'total_value'  => round($unitCost * $qty, 2),
                'reason'       => $data['reason'],
                'notes'        => $data['notes'],
                'recorded_by'  => auth()->id(),
            ]);

            $this->stockService->deductLockedStock(
                $product,
                (int) ceil($qty),
                'waste',
                __('pos.waste_reason_' . $data['reason']),
                $waste->id,
                'waste',
                $data['warehouse_id'] ?? null
            );

            return $waste;
        });

        $this->audit('waste.recorded', 'WasteRecord', $record->id, [
            'product_id'  => $record->product_id,
            'quantity'    => $record->quantity,
            'total_value' => $record->total_value,
            'reason'      => $record->reason,
        ]);

        return $this->success([
            'record'    => $record->load('product:id,name'),
            'message'   => __('pos.waste_recorded'),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $query = WasteRecord::with(['product:id,name', 'recorder:id,full_name'])
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date,   fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->success($query);
    }
}
