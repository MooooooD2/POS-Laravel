<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('edit_product');
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        return [
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0|max:9999999',
            'cost_price' => 'nullable|numeric|min:0|max:9999999',
            'min_stock'  => 'nullable|integer|min:0|max:999999',
            'barcode'    => 'nullable|string|max:100|unique:products,barcode,' . $productId,
            'category'   => 'nullable|string|max:100',
            'supplier'   => 'nullable|string|max:255',
        ];
    }
}
