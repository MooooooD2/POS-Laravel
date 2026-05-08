<?php
namespace App\Http\Controllers;

use App\Http\Requests\StockReconciliationRequest;
use App\Models\Product;
use App\Services\StockReconciliationService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;

class StockReconciliationController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private StockReconciliationService $service) {}

    public function index() { return view('warehouse.reconciliation'); }

    /** #21 تنفيذ الجرد */
    public function reconcile(StockReconciliationRequest $request)
    {
        $result = $this->service->reconcile($request->validated()['items']);
        $this->audit('stock.reconciliation', 'Product', 0, [
            'total_checked'    => $result['total_checked'],
            'total_discrepant' => $result['total_discrepant'],
        ]);
        return $this->success($result);
    }

    /**
     * #21 تقرير حركات منتج للتدقيق
     * FIX-4: التحقق من وجود المنتج ومن صلاحية الوصول إليه
     */
    public function auditTrail(Request $request, int $productId)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        // FIX-4: التحقق من وجود المنتج قبل إرجاع بياناته
        // findOrFail يُرجع 404 تلقائياً إذا لم يكن المنتج موجوداً
        $product = Product::findOrFail($productId);

        return $this->success(
            $this->service->productAuditTrail($product->id, $request->from, $request->to)
        );
    }
}
