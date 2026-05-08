<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('view_pos');
    }

    public function rules(): array
    {
        $maxDiscountPercent = (float) Setting::get('max_discount_percent',
            env('MAX_DISCOUNT_PERCENT', 20)
        );

        return [
            'items'                => 'required|array|min:1|max:200',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1|max:9999',
            'discount'             => [
                'nullable', 'numeric', 'min:0',
                function ($attribute, $value, $fail) {
                    if ($value > 9999999) $fail('قيمة الخصم غير منطقية.');
                }
            ],
            'payment_method'       => 'required|in:cash,card,transfer,wallet',
            'notes'                => 'nullable|string|max:500',
            // المبلغ المستلم من الزبون (كاش فقط) — اختياري
            'cash_received'        => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'يجب إضافة منتج واحد على الأقل.',
            'items.*.product_id.exists' => 'المنتج غير موجود.',
            'items.*.quantity.min'      => 'الكمية يجب أن تكون أكبر من صفر.',
            'payment_method.in'         => 'طريقة الدفع غير صالحة.',
            'cash_received.min'         => 'المبلغ المستلم لا يمكن أن يكون سالباً.',
        ];
    }
}
