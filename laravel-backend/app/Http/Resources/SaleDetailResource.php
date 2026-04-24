<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'service_id' => $this->service_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'service_name' => $this->whenLoaded('service', fn () => $this->service?->name),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) ($this->unit_price * $this->quantity),
        ];
    }
}
