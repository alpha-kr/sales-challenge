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
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
        ];
    }
}
