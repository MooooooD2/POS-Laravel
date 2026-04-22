<?php
// =============================================================
// SERVICES - طبقة منطق الأعمال
// =============================================================

// ---------------------------------------------------------------
// FILE: app/Services/InvoiceService.php
// ---------------------------------------------------------------
namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Create a new sale invoice - إنشاء فاتورة مبيعات جديدة
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(
                Invoice::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // Calculate totals - حساب الإجماليات
            $total = 0;
            foreach ($data['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            $discount = $data['discount'] ?? 0;
            $finalTotal = $total - $discount;

            // Create invoice - إنشاء الفاتورة
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'total' => $total,
                'discount' => $discount,
                'final_total' => $finalTotal,
                'payment_method' => $data['payment_method'],
                'cashier_id' => Auth::user()->id,
                'cashier_name' => Auth::user()->full_name,
                'status' => 'completed',
            ]);

            // Create items & deduct stock - إنشاء العناصر وخصم المخزون
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || $product->quantity < $item['quantity']) {
                    throw new \Exception(__('pos.insufficient_stock', ['name' => $item['product_name']]));
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);

                $this->stockService->deductStock(
                    $product,
                    $item['quantity'],
                    'sale',
                    __('pos.sale_deduction'),
                    $invoice->id
                );
            }

            return $invoice->load('items');
        });
    }

    /**
     * Search product by barcode or name - البحث عن منتج بالباركود أو الاسم
     */
    public function searchProduct(string $query): ?Product
    {
        return Product::where('barcode', $query)
            ->orWhere('name', 'like', "%{$query}%")
            ->first();
    }

    /**
     * Get invoice with items - الحصول على الفاتورة مع عناصرها
     */
    public function getByNumber(string $number): ?Invoice
    {
        return Invoice::with('items')->where('invoice_number', $number)->first();
    }

    /**
     * Get returnable items for an invoice - الحصول على العناصر القابلة للإرجاع
     */
    public function getReturnableItems(Invoice $invoice): array
    {
        $returnedQuantities = \App\Models\ReturnItem::whereHas(
            'salesReturn',
            fn($q) =>
            $q->where('invoice_id', $invoice->id)->where('status', 'completed')
        )->selectRaw('product_id, SUM(quantity) as total_returned')
            ->groupBy('product_id')
            ->pluck('total_returned', 'product_id');

        $returnableItems = [];
        foreach ($invoice->items as $item) {
            $returned = $returnedQuantities[$item->product_id] ?? 0;
            $remaining = $item->quantity - $returned;
            if ($remaining > 0) {
                $returnableItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'original_qty' => $item->quantity,
                    'returned_qty' => $returned,
                    'returnable_qty' => $remaining,
                    'price' => $item->price,
                ];
            }
        }

        return $returnableItems;
    }
}

// ---------------------------------------------------------------
// FILE: app/Services/StockService.php
// ---------------------------------------------------------------
namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class StockService
{
    /**
     * Add stock to product - إضافة مخزون للمنتج
     */
    public function addStock(Product $product, int $quantity, string $reason, ?int $referenceId = null): void
    {
        $product->increment('quantity', $quantity);
        $this->logMovement($product, $quantity, 'add', $reason, $referenceId);
    }

    /**
     * Deduct stock from product - خصم مخزون من المنتج
     */
    public function deductStock(Product $product, int $quantity, string $type, string $reason, ?int $referenceId = null): void
    {
        $product->decrement('quantity', $quantity);
        $this->logMovement($product, $quantity, $type, $reason, $referenceId);
    }

    /**
     * Log stock movement - تسجيل حركة المخزون
     */
    private function logMovement(Product $product, int $quantity, string $type, string $reason, ?int $referenceId): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'movement_type' => $type,
            'reason' => $reason,
            'reference_id' => $referenceId,
            'employee_id' => Auth::user()->id,
            'employee_name' => Auth::user()?->full_name,
        ]);
    }
}

// ---------------------------------------------------------------
// FILE: app/Services/AccountingService.php
// ---------------------------------------------------------------
namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    /**
     * Create journal entry - إنشاء قيد يومية
     */
    public function createJournalEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            // Validate debit = credit - التحقق من توازن القيد
            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \Exception(__('pos.journal_unbalanced'));
            }

            $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad(
                JournalEntry::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => Auth::user()->id,
            ]);

            foreach ($data['lines'] as $line) {
                JournalEntryLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);

                // Update account balance - تحديث رصيد الحساب
                $account = Account::find($line['account_id']);
                $this->updateAccountBalance($account, $line['debit'] ?? 0, $line['credit'] ?? 0);
            }

            return $entry->load('lines.account');
        });
    }

    private function updateAccountBalance(Account $account, float $debit, float $credit): void
    {
        if (in_array($account->account_type, ['asset', 'expense'])) {
            $account->increment('balance', $debit - $credit);
        } else {
            $account->increment('balance', $credit - $debit);
        }
    }

    /**
     * Generate income statement - إعداد قائمة الدخل
     */
    public function incomeStatement(string $startDate, string $endDate): array
    {
        $revenues = $this->getAccountsTotals('revenue', $startDate, $endDate);
        $expenses = $this->getAccountsTotals('expense', $startDate, $endDate);

        $totalRevenue = collect($revenues)->sum('total');
        $totalExpense = collect($expenses)->sum('total');
        $netIncome = $totalRevenue - $totalExpense;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome');
    }

    /**
     * Generate balance sheet - إعداد الميزانية العمومية
     */
    public function balanceSheet(): array
    {
        $assets = Account::where('account_type', 'asset')->with('children')->whereNull('parent_id')->get();
        $liabilities = Account::where('account_type', 'liability')->with('children')->whereNull('parent_id')->get();
        $equity = Account::where('account_type', 'equity')->with('children')->whereNull('parent_id')->get();

        return compact('assets', 'liabilities', 'equity');
    }

    private function getAccountsTotals(string $type, string $start, string $end): array
    {
        return Account::where('account_type', $type)
            ->whereNotNull('parent_id')
            ->withSum([
                'lines as total' => function ($q) use ($start, $end) {
                    $q->whereHas(
                        'entry',
                        fn($q2) =>
                        $q2->whereBetween('entry_date', [$start, $end])
                    );
                }
            ], in_array($type, ['asset', 'expense']) ? 'debit' : 'credit')
            ->get()
            ->toArray();
    }
}

// ---------------------------------------------------------------
// FILE: app/Services/PurchaseOrderService.php
// ---------------------------------------------------------------
namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Create purchase order - إنشاء أمر شراء
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(
                PurchaseOrder::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $totalAmount = collect($data['items'])->sum(fn($i) => $i['cost_price'] * $i['quantity']);
            $discount = $data['discount'] ?? 0;
            $finalAmount = $totalAmount - $discount;

            $supplier = Supplier::find($data['supplier_id']);

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $data['supplier_id'],
                'supplier_name' => $supplier->name,
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::user()->id,
                'created_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'selling_price' => $item['selling_price'] ?? null,
                    'subtotal' => $item['cost_price'] * $item['quantity'],
                ]);
            }

            // Record in supplier account - تسجيل في حساب المورد
            $this->recordSupplierDebt($supplier->id, $po->id, $poNumber, $finalAmount);

            return $po->load('items');
        });
    }

    /**
     * Receive purchase order - استلام أمر الشراء
     */
    public function receivePurchaseOrder(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $item) {
                $poItem = PurchaseOrderItem::find($item['item_id']);
                if (!$poItem)
                    continue;

                $receivedQty = min($item['received_quantity'], $poItem->quantity - $poItem->received_quantity);
                if ($receivedQty <= 0)
                    continue;

                $poItem->increment('received_quantity', $receivedQty);

                $product = $poItem->product;
                if ($product) {
                    // Update cost price if changed - تحديث سعر التكلفة إذا تغير
                    if (isset($item['cost_price'])) {
                        $product->update(['cost_price' => $item['cost_price']]);
                    }
                    if (isset($item['selling_price'])) {
                        $product->update(['price' => $item['selling_price']]);
                    }

                    $this->stockService->addStock(
                        $product,
                        $receivedQty,
                        __('pos.purchase_receipt', ['po' => $po->po_number]),
                        $po->id
                    );
                }
            }

            // Update PO status - تحديث حالة أمر الشراء
            $po->refresh();
            $allReceived = $po->items->every(fn($i) => $i->received_quantity >= $i->quantity);
            $anyReceived = $po->items->some(fn($i) => $i->received_quantity > 0);

            $po->update([
                'status' => $allReceived ? 'received' : ($anyReceived ? 'partial' : 'pending'),
                'received_date' => $allReceived ? now() : null,
            ]);

            return $po->load('items');
        });
    }

    private function recordSupplierDebt(int $supplierId, int $poId, string $poNumber, float $amount): void
    {
        $lastEntry = SupplierAccount::where('supplier_id', $supplierId)->latest()->first();
        $lastBalance = $lastEntry ? $lastEntry->balance : 0;

        SupplierAccount::create([
            'supplier_id' => $supplierId,
            'transaction_type' => 'purchase_order',
            'reference_id' => $poId,
            'reference_number' => $poNumber,
            'debit' => $amount,
            'credit' => 0,
            'balance' => $lastBalance + $amount,
            'notes' => __('pos.po_debt_note', ['po' => $poNumber]),
            'created_by' => Auth::user()->id,
        ]);
    }
}

// ---------------------------------------------------------------
// FILE: app/Services/ReturnService.php
// ---------------------------------------------------------------
namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Models\ReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * Process sales return - معالجة مرتجع المبيعات
     */
    public function processReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::with('items')->findOrFail($data['invoice_id']);

            $returnNumber = 'RET-' . date('Ymd') . '-' . str_pad(
                SalesReturn::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $totalAmount = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

            $return = SalesReturn::create([
                'return_number' => $returnNumber,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $data['customer_name'] ?? null,
                'total_amount' => $totalAmount,
                'reason' => $data['reason'] ?? null,
                'status' => 'completed',
                'return_date' => now()->toDateString(),
                'processed_by' => Auth::user()->id,
                'processed_by_name' => Auth::user()->full_name,
            ]);

            foreach ($data['items'] as $item) {
                ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);

                $product = \App\Models\Product::find($item['product_id']);
                if ($product) {
                    $this->stockService->addStock(
                        $product,
                        $item['quantity'],
                        __('pos.return_note', ['ret' => $returnNumber]),
                        $return->id
                    );
                }
            }

            return $return->load('items');
        });
    }
}
