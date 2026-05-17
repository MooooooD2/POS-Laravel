<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ProductResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\SettingService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(
        private InvoiceService $invoiceService,
        private SettingService $settingService,
    ) {}

    public function posPage()
    {
        $settings = $this->settingService->getPosSettings();
        $waEnabled = (bool) (config('whatsapp.enabled') && config('whatsapp.phone_number_id'));
        return view('pos.index', compact('settings', 'waEnabled'));
    }

    public function searchProduct(Request $request)
    {
        $request->validate(['query' => 'required|string|min:1|max:100', 'exact' => 'nullable|boolean']);
        $result = $this->invoiceService->searchProduct(
            $request->string('query')->toString(),
            $request->boolean('exact')
        );

        if ($result === null) {
            return $this->error(__('pos.product_not_found'), 404);
        }

        if ($result instanceof \App\Models\Product) {
            return $this->success(['single' => true, 'product' => new ProductResource($result)]);
        }

        if ($result->isEmpty()) {
            return $this->error(__('pos.product_not_found'), 404);
        }

        if ($result->count() === 1) {
            return $this->success(['single' => true, 'product' => new ProductResource($result->first())]);
        }

        return $this->success(['single' => false, 'products' => ProductResource::collection($result->values())]);
    }

    public function createInvoice(StoreInvoiceRequest $request)
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());
            $this->audit('invoice.created', Invoice::class, $invoice->id, ['total' => $invoice->final_total]);
            return $this->success(['invoice' => new InvoiceResource($invoice)], '', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('invoice.create_db_error', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return $this->error(__('pos.invoice_creation_failed'), 500);
        } catch (\Exception $e) {
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
