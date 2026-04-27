<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('suppliers.index');
    }

    public function all()
    {
        return response()->json(['suppliers' => Supplier::orderByDesc('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'email'   => 'nullable|email|max:255',
        ]);

        $supplier = Supplier::create($data);

        return response()->json(['success' => true, 'supplier' => $supplier], 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'email'   => 'nullable|email|max:255',
        ]);

        $supplier->update($data);

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('pos.supplier_has_orders'),
            ], 422);
        }

        $supplier->delete();

        return response()->json(['success' => true]);
    }
}
