<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierAccount;

class SupplierAccountController extends Controller
{
    public function index() { return view('supplier-accounts.index'); }

    public function show(Supplier $supplier)
    {
        $entries = SupplierAccount::where('supplier_id', $supplier->id)
            ->orderBy('created_at')->get();

        $totalDebt    = $entries->sum('debit');
        $totalPayment = $entries->sum('credit');
        $balance      = $totalDebt - $totalPayment;

        return response()->json([
            'supplier'      => $supplier,
            'entries'       => $entries,
            'total_debt'    => $totalDebt,
            'total_payment' => $totalPayment,
            'balance'       => $balance,
        ]);
    }
}
