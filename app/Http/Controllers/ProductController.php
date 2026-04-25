<?php

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/ProductController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    public function index()
    {
        return view('warehouse.index');
    }

    public function all()
    {
        $products = Product::orderByDesc('id')->get();
        return response()->json([
            'products' => $products->map(fn($p) => array_merge($p->toArray(), ['low_stock' => $p->low_stock]))
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = Product::create($data);

        if ($product->quantity > 0) {
            $this->stockService->addStock($product, $product->quantity, __('pos.new_product_added'));
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        $product->update($data);
        return response()->json(['success' => true, 'product' => $product]);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => true]);
    }

    public function addStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        $this->stockService->addStock($product, $data['quantity'], $data['reason'] ?? __('pos.manual_stock_add'));
        return response()->json(['success' => true, 'new_quantity' => $product->fresh()->quantity]);
    }
}
