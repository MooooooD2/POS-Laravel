<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    public function posPage()
    {
        return view('pos.index');
    }

    public function searchProduct(Request $request)
    {
        $product = $this->invoiceService->searchProduct($request->query('query', ''));
        if (!$product) {
            return response()->json(['success' => false, 'message' => __('pos.product_not_found')]);
        }
        return response()->json(['success' => true, 'product' => $product]);
    }

    public function createInvoice(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer',
        ]);

        try {
            $invoice = $this->invoiceService->createInvoice($data);
            return response()->json(['success' => true, 'invoice' => $invoice]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getByNumber(Request $request)
    {
        $invoice = $this->invoiceService->getByNumber($request->query('number', ''));
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => __('pos.invoice_not_found')]);
        }
        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    public function returnableItems(Invoice $invoice)
    {
        return response()->json(['items' => $this->invoiceService->getReturnableItems($invoice)]);
    }
}