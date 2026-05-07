<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('view_pos');
    }

    public function rules(): array
    {
        return [
            'items'                => 'required|array|min:1|max:200',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1|max:9999',
            // السعر لا يأتي من المستخدم — يُحسب في السيرفر من قاعدة البيانات
            'discount'             => 'nullable|numeric|min:0',
            'payment_method'       => 'required|in:cash,card,transfer,wallet',
            'notes'                => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'يجب إضافة منتج واحد على الأقل.',
            'items.*.product_id.exists' => 'المنتج غير موجود.',
            'items.*.quantity.min'      => 'الكمية يجب أن تكون أكبر من صفر.',
            'payment_method.in'         => 'طريقة الدفع غير صالحة.',
        ];
    }
}
