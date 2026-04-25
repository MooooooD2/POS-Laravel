<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['admin', 'warehouse']);
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'quantity'   => 'required|integer|min:0',
            'min_stock'  => 'nullable|integer|min:0',
            'barcode'    => 'nullable|string|unique:products,barcode',
            'category'   => 'nullable|string|max:100',
            'supplier'   => 'nullable|string|max:255',
        ];
    }
}
