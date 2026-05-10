<?php
namespace App\Http\Controllers;

use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(
        private PurchaseOrderService            $poService,
        private PurchaseOrderRepositoryInterface $poRepo,
    ) {}

    public function index() { return view('purchase-orders.index'); }

    public function all(Request $request)
    {
        $request->validate([
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'status'      => 'nullable|in:pending,partial,received,cancelled',
        ]);

        return $this->success(['purchase_orders' => $this->poRepo->paginate(
            $request->only(['supplier_id', 'status'])
        )]);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $this->authorize('create', PurchaseOrder::class);
        try {
            $po = $this->poService->createPurchaseOrder($request->validated());
            $this->audit('po.created', PurchaseOrder::class, (int) $po->id, ['po_number' => $po->po_number]);
            return $this->success(['purchase_order' => $po], '', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('receive', $purchaseOrder);
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return $this->error(__('pos.po_already_closed'), 422);
        }
        try {
            $po = $this->poService->receivePurchaseOrder($purchaseOrder, $request->validated()['items']);
            $this->audit('po.received', PurchaseOrder::class, (int) $po->id);
            return $this->success(['purchase_order' => $po]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
