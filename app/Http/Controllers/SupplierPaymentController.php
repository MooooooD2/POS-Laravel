<?php

namespace App\Http\Controllers;

use App\Models\SupplierPayment;
use App\Models\SupplierAccount;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        return view('supplier-payments.index');
    }

    public function all(Request $request)
    {
        $query = SupplierPayment::with('supplier')->orderByDesc('id');
        if ($request->supplier_id)
            $query->where('supplier_id', $request->supplier_id);
        return response()->json(['payments' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,transfer,check',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data) {
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad(
                SupplierPayment::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $supplier = Supplier::find($data['supplier_id']);
            $payment = SupplierPayment::create(array_merge($data, [
                'payment_number' => $paymentNumber,
                'supplier_name' => $supplier->name,
                'created_by' => Auth::user()->id,
                'created_by_name' => Auth::user()->full_name,
            ]));

            $lastEntry = SupplierAccount::where('supplier_id', $data['supplier_id'])->latest()->first();
            $lastBalance = $lastEntry ? $lastEntry->balance : 0;

            SupplierAccount::create([
                'supplier_id' => $data['supplier_id'],
                'transaction_type' => 'payment',
                'reference_id' => $payment->id,
                'reference_number' => $paymentNumber,
                'debit' => 0,
                'credit' => $data['amount'],
                'balance' => $lastBalance - $data['amount'],
                'notes' => $data['notes'],
                'created_by' => Auth::user()->id,
            ]);
        });

        return response()->json(['success' => true]);
    }
}
