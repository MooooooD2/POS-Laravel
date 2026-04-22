<?php

namespace App\Http\Controllers;

use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    public function index() { return view('returns.index'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id'    => 'required|exists:invoices,id',
            'customer_name' => 'nullable|string',
            'reason'        => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.price'        => 'required|numeric|min:0',
        ]);

        try {
            $return = $this->returnService->processReturn($data);
            return response()->json(['success' => true, 'return' => $return]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
