<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'invoice_id'           => 'required|exists:invoices,id',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.price'        => 'required|numeric|min:0',
            'reason'               => 'nullable|string|max:500',
            'customer_name'        => 'nullable|string|max:255',
        ];
    }
}
