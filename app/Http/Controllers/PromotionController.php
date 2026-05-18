<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Services\PromotionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use ApiResponse;

    public function __construct(private PromotionService $promotionService) {}

    public function index(Request $request): JsonResponse
    {
        $promotions = Promotion::query()
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when(!$request->boolean('with_inactive'), fn($q) => $q->where('is_active', true))
            ->with('product:id,name')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return $this->success($promotions->toArray());
    }

    public function active(): JsonResponse
    {
        $today = now()->toDateString();

        $promotions = Promotion::where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today))
            ->with('product:id,name')
            ->get();

        return $this->success(['promotions' => $promotions]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePromotion($request);
        $promo = Promotion::create($data);
        return $this->success(['promotion' => $promo], '', 201);
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $data = $this->validatePromotion($request, $promotion->id);
        $promotion->update($data);
        return $this->success(['promotion' => $promotion->fresh()]);
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        $promotion->delete();
        return $this->success([], __('pos.promotion_deleted'));
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'items'   => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.subtotal'   => 'required|numeric|min:0',
        ]);

        $productIds = collect($request->items)->pluck('product_id')->unique()->toArray();
        $products   = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        $items = collect($request->items)->map(fn($item) => array_merge($item, [
            'product' => $products->get($item['product_id']),
        ]))->toArray();

        $orderTotal = collect($items)->sum('subtotal');
        $result     = $this->promotionService->apply($items, $orderTotal);

        return $this->success($result);
    }

    private function validatePromotion(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'             => 'required|string|max:150',
            'description'      => 'nullable|string|max:500',
            'type'             => 'required|in:percentage,fixed,buy_x_get_y',
            'value'            => 'required_unless:type,buy_x_get_y|numeric|min:0',
            'buy_qty'          => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'get_qty'          => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'product_id'       => 'nullable|exists:products,id',
            'product_category' => 'nullable|string|max:100',
            'min_order_amount' => 'nullable|numeric|min:0',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after_or_equal:starts_at',
            'is_active'        => 'boolean',
        ]);
    }
}
