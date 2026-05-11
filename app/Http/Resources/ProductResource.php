<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stock_code' => $this->stock_code,
            'description' => $this->description,
            'satis_usd' => $this->satis_usd !== null ? (float) $this->satis_usd : null,
            'service_provider' => new ServiceProviderResource($this->whenLoaded('serviceProvider')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
