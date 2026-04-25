<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity'     => 'required|integer|min:1|max:99999',
            'items.*.price'        => 'required|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'payment_method'       => 'required|in:cash,card,transfer,wallet',
        ];
    }
}
