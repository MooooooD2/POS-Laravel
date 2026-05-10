<?php
namespace App\Http\Controllers;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService           $dashboardService,
        private ProductRepositoryInterface $productRepo,
    ) {}

    public function index()
    {
        return view('dashboard.index');
    }

    public function lowStock()
    {
        $outOfStock = $this->productRepo->outOfStock();
        $lowStock   = $this->productRepo->lowStock();

        return response()->json([
            'total_alerts' => $outOfStock->count() + $lowStock->count(),
            'out_of_stock' => $outOfStock,
            'low_stock'    => $lowStock,
        ]);
    }

    public function data()
    {
        return response()->json($this->dashboardService->getData());
    }
}
