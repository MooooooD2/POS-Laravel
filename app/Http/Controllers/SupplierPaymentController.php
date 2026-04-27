<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierAccount;
use App\Models\SupplierPayment;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function __construct(private SequenceService $sequenceService) {}

    public function index()
    {
        return view('supplier-payments.index');
    }

    public function all(Request $request)
    {
        $query = SupplierPayment::with('supplier')->orderByDesc('id');

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        return response()->json(['payments' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,transfer,check',
            'payment_date'   => 'required|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data) {
            $paymentNumber = $this->sequenceService->next('supplier_payment', 'PAY');

            /** @var Supplier $supplier */
            $supplier = Supplier::findOrFail($data['supplier_id']);

            $payment = SupplierPayment::create([
                ...$data,
                'payment_number'  => $paymentNumber,
                'supplier_name'   => $supplier->name,
                'created_by'      => Auth::id(),
                'created_by_name' => Auth::user()->full_name,
            ]);

            // Atomic balance calculation — lock the last row to avoid race conditions
            $lastBalance = SupplierAccount::where('supplier_id', $data['supplier_id'])
                ->latest('id')
                ->lockForUpdate()
                ->value('balance') ?? 0;

            SupplierAccount::create([
                'supplier_id'      => $data['supplier_id'],
                'transaction_type' => 'payment',
                'reference_id'     => $payment->id,
                'reference_number' => $paymentNumber,
                'debit'            => 0,
                'credit'           => $data['amount'],
                'balance'          => $lastBalance - $data['amount'],
                'notes'            => $data['notes'] ?? null,
                'created_by'       => Auth::id(),
            ]);
        });

        return response()->json(['success' => true]);
    }
}
