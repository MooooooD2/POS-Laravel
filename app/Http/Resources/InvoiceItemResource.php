<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'product_name'      => $this->product_name,
            'quantity'          => $this->quantity,
            'price'             => $this->price,
            'subtotal'          => $this->subtotal,
            'unit_abbreviation' => $this->whenLoaded('product', fn() => $this->product?->unit?->abbreviation ?? $this->product?->unit?->name),
        ];
    }
}
