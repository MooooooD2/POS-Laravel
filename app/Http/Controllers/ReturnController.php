<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnRequest;
use App\Models\SalesReturn;
use App\Services\ReturnService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;

class ReturnController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private ReturnService $returnService) {}

    public function index() { return view('returns.index'); }

    public function store(StoreReturnRequest $request)
    {
        // FIX-3: إضافة authorization check داخل الـ controller
        // (إضافة طبقة ثانية فوق الـ route middleware)
        $this->authorize('create', SalesReturn::class);

        try {
            $return = $this->returnService->processReturn($request->validated());
            $this->audit('return.created', SalesReturn::class, $return->id, [
                'total'          => $return->total_amount,
                'invoice_id'     => $return->invoice_id,
                'invoice_number' => $return->invoice_number,
            ]);
            return $this->success(['return' => $return], '', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
