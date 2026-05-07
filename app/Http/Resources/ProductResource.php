<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'price'      => $this->price,
            'cost_price' => $this->when(auth()->user()?->can('view_accounting'), $this->cost_price),
            'quantity'   => $this->quantity,
            'min_stock'  => $this->min_stock,
            'barcode'    => $this->barcode,
            'category'   => $this->category,
            'supplier'   => $this->supplier,
            'low_stock'  => $this->low_stock,
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
