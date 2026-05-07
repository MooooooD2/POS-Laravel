<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnRequest;
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
        try {
            $return = $this->returnService->processReturn($request->validated());
            $this->audit('return.created', \App\Models\SalesReturn::class, $return->id, ['total' => $return->total_amount]);
            return $this->success(['return' => $return], '', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
