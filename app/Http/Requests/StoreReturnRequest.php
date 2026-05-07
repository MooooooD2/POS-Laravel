<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('view_returns');
    }

    public function rules(): array
    {
        return [
            'invoice_id'           => 'required|integer|exists:invoices,id',
            'items'                => 'required|array|min:1|max:200',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1|max:9999',
            'reason'               => 'nullable|string|max:500',
            'customer_name'        => 'nullable|string|max:255',
        ];
    }
}
