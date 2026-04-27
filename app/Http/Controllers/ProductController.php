<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        return view('warehouse.index');
    }

    public function all(Request $request)
    {
        $query = Product::orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('barcode', $request->search);
            });
        }

        $products = $query->get();

        return response()->json([
            'products' => $products->map(fn($p) => array_merge($p->toArray(), ['low_stock' => $p->low_stock])),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data    = $request->validated();
        $product = Product::create($data);

        if ($product->quantity > 0) {
            $this->stockService->addStock($product, $product->quantity, __('pos.new_product_added'));
        }

        return response()->json(['success' => true, 'product' => $product], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function destroy(Product $product)
    {
        if ($product->invoiceItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('pos.product_has_sales'),
            ], 422);
        }

        $product->delete();

        return response()->json(['success' => true]);
    }

    public function addStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:255',
        ]);

        $this->stockService->addStock(
            $product,
            $data['quantity'],
            $data['reason'] ?? __('pos.manual_stock_add')
        );

        return response()->json(['success' => true, 'new_quantity' => $product->fresh()->quantity]);
    }
}
