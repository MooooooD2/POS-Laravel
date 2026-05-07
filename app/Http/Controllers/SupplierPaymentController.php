<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Models\SupplierAccount;
use App\Models\SupplierPayment;
use App\Models\Supplier;
use App\Services\SequenceService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    use ApiResponse, AuditLog;

    public function index() { return view('supplier-payments.index'); }

    public function all(Request $request)
    {
        $request->validate(['supplier_id' => 'nullable|integer|exists:suppliers,id']);
        $query = SupplierPayment::with('supplier')->orderByDesc('id');
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->integer('supplier_id'));
        return $this->success(['payments' => $query->paginate(20)]);
    }

    public function store(StoreSupplierPaymentRequest $request)
    {
        $this->authorize('create', SupplierPayment::class);
        $data     = $request->validated();
        $supplier = Supplier::findOrFail($data['supplier_id']);

        DB::transaction(function () use ($data, $supplier) {
            $paymentNumber = SequenceService::next('payment');
            $payment = SupplierPayment::create(array_merge($data, [
                'payment_number'  => $paymentNumber,
                'supplier_name'   => $supplier->name,
                'created_by'      => Auth::id(),
                'created_by_name' => Auth::user()->full_name,
            ]));

            $last        = SupplierAccount::where('supplier_id', $data['supplier_id'])->lockForUpdate()->latest()->first();
            $lastBalance = $last ? $last->balance : 0;

            SupplierAccount::create([
                'supplier_id'      => $data['supplier_id'],
                'transaction_type' => 'payment',
                'reference_id'     => (int) $payment->id,
                'reference_number' => $paymentNumber,
                'debit'            => 0,
                'credit'           => $data['amount'],
                'balance'          => $lastBalance - $data['amount'],
                'notes'            => $data['notes'] ?? null,
                'created_by'       => Auth::id(),
            ]);
        });

        $this->audit('payment.created', SupplierPayment::class, 0, ['supplier' => (string) $supplier->name, 'amount' => $data['amount']]);
        return $this->success(message: __('pos.payment_created'), code: 201);
    }
}
