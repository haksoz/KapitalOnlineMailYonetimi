<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stock_code' => $this->stock_code,
            'description' => $this->description,
            // Aylık Taahhütlü
            'satis_usd_monthly_commitment' => $this->satis_usd_monthly_commitment !== null ? (float) $this->satis_usd_monthly_commitment : null,
            // Aylık Taahhütsüz
            'satis_usd_monthly_no_commitment' => $this->satis_usd_monthly_no_commitment !== null ? (float) $this->satis_usd_monthly_no_commitment : null,
            // Yıllık Taahhütlü
            'satis_usd_yearly_commitment' => $this->satis_usd_yearly_commitment !== null ? (float) $this->satis_usd_yearly_commitment : null,
            'service_provider' => $this->serviceProvider ? [
                'id' => $this->serviceProvider->id,
                'name' => $this->serviceProvider->name,
                'code' => $this->serviceProvider->code,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
