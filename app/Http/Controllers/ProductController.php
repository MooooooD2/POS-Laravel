<?php
namespace App\Http\Controllers;

use App\Http\Requests\AddStockRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\StockService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private StockService $stockService) {}

    public function index() { return view('warehouse.index'); }

    public function all(Request $request)
    {
        $request->validate([
            'search'    => 'nullable|string|max:100',
            'category'  => 'nullable|string|max:100',
            'low_stock' => 'nullable|boolean',
            'per_page'  => 'nullable|integer|min:10|max:200',
        ]);

        $query = Product::query()->orderByDesc('id');

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$s}%")->orWhere('barcode', 'like', "%{$s}%")
            );
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
        if ($request->boolean('low_stock')) {
            $query->whereRaw('quantity <= min_stock AND quantity > 0');
        }

        if ($request->boolean('all')) {
            return $this->success(['products' => ProductResource::collection(
                $query->select('id', 'name', 'price', 'barcode', 'quantity', 'min_stock', 'category')->get()
            )]);
        }

        return $this->success(['products' =>
            ProductResource::collection($query->paginate($request->integer('per_page', 50)))
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);
        $data    = $request->validated();
        $product = Product::create($data);

        $initial = (int) ($data['initial_quantity'] ?? 0);
        if ($initial > 0) {
            $this->stockService->addStock($product, $initial, __('pos.new_product_added'), null, 'initial');
        }

        $this->audit('product.created', Product::class, (int) $product->id, ['name' => $product->name]);
        return $this->success(['product' => new ProductResource($product)], '', 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);
        $old = $product->only(['name', 'price', 'cost_price']);
        $product->update($request->validated());
        $this->audit('product.updated', Product::class, (int) $product->id, ['old' => $old, 'new' => $request->validated()]);
        return $this->success(['product' => new ProductResource($product->fresh())]);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $product->delete();
        $this->audit('product.deleted', Product::class, (int) $product->id, ['name' => $product->name]);
        return $this->success(message: __('pos.product_deleted'));
    }

    public function addStock(AddStockRequest $request, Product $product)
    {
        $this->authorize('addStock', $product);
        $data = $request->validated();
        $this->stockService->addStock(
            $product,
            $data['quantity'],
            $data['reason'] ?? __('pos.manual_stock_add'),
            null,
            $data['reference_type'] ?? 'adjustment'
        );
        $this->audit('stock.added', Product::class, (int) $product->id, ['qty' => $data['quantity']]);
        return $this->success(['new_quantity' => $product->fresh()->quantity]);
    }
}
