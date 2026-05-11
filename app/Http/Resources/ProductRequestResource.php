<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cari_id' => $this->cari_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'description' => $this->description,
            'status' => $this->status,
            'notes' => $this->notes,
            'cari' => new CariResource($this->whenLoaded('cari')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
