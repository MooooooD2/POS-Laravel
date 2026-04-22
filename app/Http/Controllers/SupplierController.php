<?php
namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
public function index() { return view('suppliers.index'); }

public function all()
{
return response()->json(['suppliers' => Supplier::orderByDesc('id')->get()]);
}

public function store(Request $request)
{
$data = $request->validate([
'name' => 'required|string|max:255',
'phone' => 'nullable|string|max:20',
'address' => 'nullable|string',
'email' => 'nullable|email',
]);
$supplier = Supplier::create($data);
return response()->json(['success' => true, 'supplier' => $supplier]);
}

public function update(Request $request, Supplier $supplier)
{
$data = $request->validate([
'name' => 'required|string|max:255',
'phone' => 'nullable|string|max:20',
'address' => 'nullable|string',
'email' => 'nullable|email',
]);
$supplier->update($data);
return response()->json(['success' => true, 'supplier' => $supplier]);
}

public function destroy(Supplier $supplier)
{
$supplier->delete();
return response()->json(['success' => true]);
}
}