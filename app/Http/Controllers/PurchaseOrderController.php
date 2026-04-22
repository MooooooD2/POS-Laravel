<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $poService) {}

    public function index() { return view('purchase-orders.index'); }

    public function all(Request $request)
    {
        $query = PurchaseOrder::with('items')->orderByDesc('id');
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->status)      $query->where('status', $request->status);
        return response()->json(['purchase_orders' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'order_date'     => 'required|date',
            'expected_date'  => 'nullable|date',
            'discount'       => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id'   => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.cost_price'   => 'required|numeric|min:0',
            'items.*.selling_price'=> 'nullable|numeric|min:0',
        ]);

        try {
            $po = $this->poService->createPurchaseOrder($data);
            return response()->json(['success' => true, 'purchase_order' => $po]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'items'                          => 'required|array',
            'items.*.item_id'                => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity'      => 'required|integer|min:0',
            'items.*.cost_price'             => 'nullable|numeric|min:0',
            'items.*.selling_price'          => 'nullable|numeric|min:0',
        ]);

        try {
            $po = $this->poService->receivePurchaseOrder($purchaseOrder, $data['items']);
            return response()->json(['success' => true, 'purchase_order' => $po]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
