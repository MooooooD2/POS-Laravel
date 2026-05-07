<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\InvoiceService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private InvoiceService $invoiceService) {}

    public function posPage()
    {
        $raw = Setting::getGroup('pos')
             + Setting::getGroup('tax')
             + Setting::getGroup('general')
             + Setting::getGroup('invoice');

        // getGroup() returns sub-arrays ['value', 'type', ...].
        // Cast each entry to its proper scalar so the view can echo it directly.
        $settings = array_map(fn($s) => match ($s['type'] ?? 'string') {
            'boolean' => (bool) $s['value'],
            'number'  => (float) $s['value'],
            'json'    => json_decode($s['value'], true),
            default   => $s['value'],
        }, $raw);

        // Guarantee every key the view references exists, even on a fresh DB
        // that hasn't been seeded yet.
        $settings += [
            'currency_symbol'  => 'ج.م',
            'store_name'       => '',
            'store_address'    => '',
            'store_phone'      => '',
            'tax_enabled'      => false,
            'tax_rate'         => 0.0,
            'tax_name_ar'      => 'ضريبة',
            'tax_name_en'      => 'VAT',
            'tax_inclusive'    => false,
            'invoice_footer'   => '',
            'auto_print'       => false,
            'pos_sound'        => true,
            'default_payment'  => 'cash',
        ];

        return view('pos.index', compact('settings'));
    }

    // #13 بحث آمن — بدون SQL injection
    public function searchProduct(Request $request)
    {
        $request->validate(['query' => 'required|string|min:1|max:100', 'exact' => 'nullable|boolean']);
        $result = $this->invoiceService->searchProduct(
            $request->string('query')->toString(),
            $request->boolean('exact')
        );

        // null → exact barcode scan found nothing
        if ($result === null) {
            return $this->error(__('pos.product_not_found'), 404);
        }

        // Single Product model → exact barcode scan hit
        if ($result instanceof \App\Models\Product) {
            return $this->success(['single' => true, 'product' => $result]);
        }

        // Collection
        if ($result->isEmpty()) {
            return $this->error(__('pos.product_not_found'), 404);
        }

        if ($result->count() === 1) {
            return $this->success(['single' => true, 'product' => $result->first()]);
        }

        return $this->success(['single' => false, 'products' => $result->values()]);
    }

    public function createInvoice(StoreInvoiceRequest $request)
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());
            $this->audit('invoice.created', Invoice::class, $invoice->id, ['total' => $invoice->final_total]);
            return $this->success(['invoice' => new InvoiceResource($invoice)], '', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // DB errors must never reach the client — log internally, return generic message
            Log::error('invoice.create_db_error', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return $this->error(__('pos.invoice_creation_failed'), 500);
        } catch (\Exception $e) {
            // Business-logic exceptions (insufficient stock, product not found) carry
            // safe, localized messages from the service layer — pass them through.
            return $this->error($e->getMessage(), 422);
        }
    }

    public function getByNumber(Request $request)
    {
        $request->validate(['number' => 'required|string|max:50']);
        $invoice = $this->invoiceService->getByNumber($request->string('number')->toString());
        if (!$invoice) return $this->error(__('pos.invoice_not_found'), 404);
        return $this->success(['invoice' => new InvoiceResource($invoice)]);
    }

    public function returnableItems(Invoice $invoice)
    {
        if ($invoice->status !== 'completed') return $this->error(__('pos.invoice_not_completed'), 422);
        return $this->success(['items' => $this->invoiceService->getReturnableItems($invoice)]);
    }
}
