<?php
namespace App\Http\Controllers;

use App\Contracts\Repositories\ProductRepositoryInterface;
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

    public function __construct(
        private StockService               $stockService,
        private ProductRepositoryInterface $productRepo,
    ) {}

    public function index() { return view('warehouse.index'); }

    public function all(Request $request)
    {
        $request->validate([
            'search'    => 'nullable|string|max:100',
            'category'  => 'nullable|string|max:100',
            'low_stock' => 'nullable|boolean',
            'per_page'  => 'nullable|integer|min:10|max:200',
        ]);

        $filters  = $request->only(['search', 'category', 'low_stock', 'per_page']);
        $fetchAll = $request->boolean('all');

        return $this->success(['products' => ProductResource::collection(
            $this->productRepo->all($filters, $fetchAll)
        )]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);
        $data    = $request->validated();
        $product = $this->productRepo->create($data);

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
        $old     = $product->only(['name', 'price', 'cost_price']);
        $updated = $this->productRepo->update($product, $request->validated());
        $this->audit('product.updated', Product::class, (int) $updated->id, ['old' => $old, 'new' => $request->validated()]);
        return $this->success(['product' => new ProductResource($updated->fresh())]);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $this->productRepo->delete($product);
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
