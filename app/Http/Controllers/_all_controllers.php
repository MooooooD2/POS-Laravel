<?php

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/PurchaseOrderController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $poService)
    {
    }

    public function index()
    {
        return view('purchase-orders.index');
    }

    public function all(Request $request)
    {
        $query = PurchaseOrder::with('items')->orderByDesc('id');
        if ($request->supplier_id)
            $query->where('supplier_id', $request->supplier_id);
        if ($request->status)
            $query->where('status', $request->status);
        return response()->json(['purchase_orders' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $po = $this->poService->createPurchaseOrder($data);
            return response()->json(['success' => true, 'purchase_order' => $po]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|integer|min:0',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $po = $this->poService->receivePurchaseOrder($purchaseOrder, $data['items']);
            return response()->json(['success' => true, 'purchase_order' => $po]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/SupplierPaymentController.php
// ---------------------------------------------------------------
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

            // Update supplier account - تحديث حساب المورد
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

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/SupplierAccountController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierAccount;

class SupplierAccountController extends Controller
{
    public function index()
    {
        return view('supplier-accounts.index');
    }

    public function show(Supplier $supplier)
    {
        $entries = SupplierAccount::where('supplier_id', $supplier->id)
            ->orderBy('created_at')->get();

        $totalDebt = $entries->sum('debit');
        $totalPayment = $entries->sum('credit');
        $balance = $totalDebt - $totalPayment;

        return response()->json([
            'supplier' => $supplier,
            'entries' => $entries,
            'total_debt' => $totalDebt,
            'total_payment' => $totalPayment,
            'balance' => $balance,
        ]);
    }
}

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/AccountingController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function index()
    {
        return view('accounting.index');
    }

    public function allAccounts()
    {
        $accounts = Account::with('children', 'parent')->orderBy('account_code')->get();
        return response()->json(['accounts' => $accounts]);
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'account_code' => 'required|string|unique:accounts,account_code',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);
        $account = Account::create($data);
        return response()->json(['success' => true, 'account' => $account]);
    }

    public function updateAccount(Request $request, Account $account)
    {
        $data = $request->validate([
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $account->update($data);
        return response()->json(['success' => true, 'account' => $account]);
    }

    public function destroyAccount(Account $account)
    {
        if ($account->children()->exists() || $account->lines()->exists()) {
            return response()->json(['success' => false, 'message' => __('pos.account_has_dependencies')], 422);
        }
        $account->delete();
        return response()->json(['success' => true]);
    }

    public function allJournalEntries(Request $request)
    {
        $query = JournalEntry::with('lines.account', 'creator')->orderByDesc('entry_date');
        if ($request->start_date)
            $query->where('entry_date', '>=', $request->start_date);
        if ($request->end_date)
            $query->where('entry_date', '<=', $request->end_date);
        return response()->json(['entries' => $query->paginate(20)]);
    }

    public function storeJournalEntry(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        try {
            $entry = $this->accountingService->createJournalEntry($data);
            return response()->json(['success' => true, 'entry' => $entry]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/ReturnController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(private ReturnService $returnService)
    {
    }

    public function index()
    {
        return view('returns.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'customer_name' => 'nullable|string',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            $return = $this->returnService->processReturn($data);
            return response()->json(['success' => true, 'return' => $return]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/ReportController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function index()
    {
        return view('reports.index');
    }
    public function financialReports()
    {
        return view('financial-reports.index');
    }

    public function salesReport(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'cashier_id' => 'nullable|exists:users,id',
        ]);

        $query = Invoice::with('items')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$data['start_date'], $data['end_date'] . ' 23:59:59']);

        if (!empty($data['payment_method']))
            $query->where('payment_method', $data['payment_method']);
        if (!empty($data['cashier_id']))
            $query->where('cashier_id', $data['cashier_id']);

        $invoices = $query->orderByDesc('created_at')->get();
        $totalRevenue = $invoices->sum('final_total');
        $totalCount = $invoices->count();

        // Sales by payment method - المبيعات حسب طريقة الدفع
        $byPayment = $invoices->groupBy('payment_method')
            ->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('final_total')]);

        // Best selling products - أفضل المنتجات
        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'completed')
            ->whereBetween('invoices.created_at', [$data['start_date'], $data['end_date'] . ' 23:59:59'])
            ->selectRaw('invoice_items.product_name, SUM(invoice_items.quantity) as total_qty, SUM(invoice_items.subtotal) as total_sales')
            ->groupBy('invoice_items.product_id', 'invoice_items.product_name')
            ->orderByDesc('total_sales')->limit(10)->get();

        return response()->json([
            'invoices' => $invoices,
            'total_revenue' => $totalRevenue,
            'total_count' => $totalCount,
            'by_payment' => $byPayment,
            'top_products' => $topProducts,
        ]);
    }

    public function stockReport()
    {
        $products = Product::orderBy('category')->orderBy('name')->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'stock_value' => $p->quantity * $p->cost_price,
                'potential_value' => $p->quantity * $p->price,
                'low_stock' => $p->low_stock,
            ]));

        return response()->json([
            'products' => $products,
            'total_stock_value' => $products->sum('stock_value'),
            'low_stock_count' => $products->where('low_stock', true)->count(),
            'out_of_stock' => $products->where('quantity', 0)->count(),
        ]);
    }

    public function incomeStatement(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return response()->json(
            $this->accountingService->incomeStatement($data['start_date'], $data['end_date'])
        );
    }

    public function balanceSheet()
    {
        return response()->json($this->accountingService->balanceSheet());
    }

    public function accountStatement(Request $request, Account $account)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $lines = $account->lines()
            ->with('entry')
            ->whereHas('entry', fn($q) => $q->whereBetween('entry_date', [$data['start_date'], $data['end_date']]))
            ->get();

        return response()->json([
            'account' => $account,
            'lines' => $lines,
            'total_debit' => $lines->sum('debit'),
            'total_credit' => $lines->sum('credit'),
            'net_balance' => $lines->sum('debit') - $lines->sum('credit'),
        ]);
    }
}
