<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    public function index() { return view('returns.index'); }

    public function store(StoreReturnRequest $request)
    {
        $data = $request->validated();

        try {
            $return = $this->returnService->processReturn($data);
            return response()->json(['success' => true, 'return' => $return]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
