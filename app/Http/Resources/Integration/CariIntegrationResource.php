<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CariIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'country_code' => $this->country_code,
            'tax_number'   => $this->tax_number,
            'type'         => $this->cari_type,
            'status'       => 'active',
        ];
    }
}
