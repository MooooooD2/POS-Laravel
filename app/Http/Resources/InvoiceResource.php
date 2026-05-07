<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'total'          => $this->total,
            'discount'       => $this->discount,
            'tax_rate'       => $this->tax_rate,
            'tax_amount'     => $this->tax_amount,
            'final_total'    => $this->final_total,
            'payment_method' => $this->payment_method,
            'cashier_name'   => $this->cashier_name,
            'status'         => $this->status,
            'created_at'     => $this->created_at->toDateTimeString(),
            'items'          => InvoiceItemResource::collection($this->whenLoaded('items')),
            // لا يظهر cashier_id أو أي بيانات داخلية
        ];
    }
}
