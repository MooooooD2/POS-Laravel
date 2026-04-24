<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    public function posPage()
    {
        $settings = [
            'tax_enabled' => Setting::get('tax_enabled', false),
            'tax_rate' => Setting::get('tax_rate', 0),
            'tax_inclusive' => Setting::get('tax_inclusive', false),
            'tax_name_ar' => Setting::get('tax_name_ar', 'ضريبة القيمة المضافة'),
            'tax_name_en' => Setting::get('tax_name_en', 'VAT'),
            'pos_sound' => Setting::get('pos_sound', true),
            'invoice_footer' => Setting::get('invoice_footer', ''),
            'store_name' => Setting::get('store_name', ''),
            'store_address' => Setting::get('store_address', ''),
            'store_phone' => Setting::get('store_phone', ''),
            'default_payment' => Setting::get('default_payment', 'cash'),
            'auto_print' => Setting::get('auto_print', false),
        ];
        return view('pos.index', compact('settings'));
    }

    public function searchProduct(Request $request)
    {
        $query = $request->query('query', '');
        $exactBarcode = (bool) $request->query('exact', false);

        if (!$query) {
            return response()->json(['success' => false, 'message' => __('pos.product_not_found')]);
        }

        $result = $this->invoiceService->searchProduct($query, $exactBarcode);

        if ($result && !($result instanceof \Illuminate\Support\Collection)) {
            return response()->json(['success' => true, 'product' => $result, 'single' => true]);
        }

        if ($result && $result->isNotEmpty()) {
            if ($result->count() === 1) {
                return response()->json(['success' => true, 'product' => $result->first(), 'single' => true]);
            }
            return response()->json(['success' => true, 'products' => $result, 'single' => false]);
        }

        return response()->json(['success' => false, 'message' => __('pos.product_not_found')]);
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
            'payment_method' => 'required|in:cash,card,transfer,wallet',
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