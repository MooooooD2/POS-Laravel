<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ApiResponse, AuditLog;

    public function index() { return view('suppliers.index'); }

    public function all(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:100', 'per_page' => 'nullable|integer|min:5|max:100']);
        $query = Supplier::orderByDesc('id');
        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
        }
        if ($request->boolean('all')) {
            return $this->success(['suppliers' => $query->select('id', 'name')->get()]);
        }
        return $this->success(['suppliers' => SupplierResource::collection($query->paginate($request->integer('per_page', 20)))]);
    }

    public function store(StoreSupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);
        $supplier = Supplier::create($request->validated());
        $this->audit('supplier.created', Supplier::class, (int) $supplier->id, ['name' => $supplier->name]);
        return $this->success(['supplier' => new SupplierResource($supplier)], '', 201);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);
        $supplier->update($request->validated());
        $this->audit('supplier.updated', Supplier::class, (int) $supplier->id);
        return $this->success(['supplier' => new SupplierResource($supplier->fresh())]);
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);
        if ($supplier->purchaseOrders()->whereIn('status', ['pending', 'partial'])->exists()) {
            return $this->error(__('pos.supplier_has_active_orders'), 422);
        }
        $supplier->delete();
        $this->audit('supplier.deleted', Supplier::class, (int) $supplier->id, ['name' => $supplier->name]);
        return $this->success();
    }
}
