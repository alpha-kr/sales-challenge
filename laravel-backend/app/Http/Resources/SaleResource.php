<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ClientResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'daily_sequence' => $this->daily_sequence,
            'total' => $this->total,
            'created_at' => $this->created_at,
            'details' => SaleDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
