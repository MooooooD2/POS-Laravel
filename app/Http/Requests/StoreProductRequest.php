<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->can('add_product'); }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => strip_tags($this->name)]);
        }
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0|max:9999999',
            'cost_price'       => 'nullable|numeric|min:0|max:9999999',
            'min_stock'        => 'nullable|integer|min:0|max:999999',
            'barcode'          => 'nullable|string|max:100|unique:products,barcode',
            'category'         => 'nullable|string|max:100',
            'supplier'         => 'nullable|string|max:255',
            // الكمية الأولية تُعالَج عبر StockService مع log
            'initial_quantity' => 'nullable|integer|min:0|max:999999',
        ];
    }
}
