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
            'all'       => 'nullable|boolean',
        ]);

        $filters  = $request->only(['search', 'category', 'low_stock', 'per_page']);
        // Cap unbounded fetch: only honour all=true for filtered queries to avoid
        // loading the entire catalogue into memory on large installations.
        $fetchAll = $request->boolean('all') && (
            !empty($filters['search']) || !empty($filters['category'])
        );

        return $this->success(['products' => ProductResource::collection(
            $this->productRepo->all($filters, $fetchAll)
        )]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);
        $data    = $request->validated();
        $product = $this->productRepo->create($data);

        $initial     = (int) ($data['initial_quantity'] ?? 0);
        $warehouseId = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
        if ($initial > 0) {
            $this->stockService->addStock(
                $product, $initial, __('pos.new_product_added'),
                null, 'initial', null, $warehouseId
            );
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

    public function lookupByBarcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string|max:100']);
        $barcode = $request->barcode;
        $product = Product::where('barcode', $barcode)->first();

        return $this->success([
            'found'    => (bool) $product,
            'product'  => $product ? new ProductResource($product) : null,
            // Only query external APIs when the barcode isn't in our system
            'external' => $product ? null : $this->fetchExternalBarcode($barcode),
        ]);
    }

    /**
     * Try to resolve a barcode against public product databases.
     * Primary : UPCitemdb  (EAN-13, UPC-A, UPC-E — general retail, 100 req/day free).
     * Fallback : Open Food Facts (food/drink — unlimited, free).
     * Returns [name, brand] or null on failure/not-found.
     */
    private function fetchExternalBarcode(string $barcode): ?array
    {
        // ── 1. UPCitemdb ────────────────────────────────────────────────────
        try {
            $res  = \Illuminate\Support\Facades\Http::timeout(3)
                ->get('https://api.upcitemdb.com/prod/trial/lookup', ['upc' => $barcode]);
            $item = $res->ok() ? $res->json('items.0') : null;
            if ($item && !empty($item['title'])) {
                return array_filter([
                    'name'  => $item['title'],
                    'brand' => $item['brand'] ?? null,
                ], fn($v) => $v !== null);
            }
        } catch (\Throwable) {}

        // ── 2. Open Food Facts (covers food/drink) ───────────────────────────
        try {
            $res  = \Illuminate\Support\Facades\Http::timeout(3)
                ->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}",
                    ['fields' => 'product_name,brands']);
            $prod = $res->ok() ? $res->json('product') : null;
            if ($prod && !empty($prod['product_name'])) {
                return array_filter([
                    'name'  => $prod['product_name'],
                    'brand' => $prod['brands'] ?? null,
                ], fn($v) => $v !== null);
            }
        } catch (\Throwable) {}

        return null;
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
            $data['reference_type'] ?? 'adjustment',
            null,
            isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null
        );
        $this->audit('stock.added', Product::class, (int) $product->id, ['qty' => $data['quantity']]);
        return $this->success(['new_quantity' => $product->fresh()->quantity]);
    }
}
