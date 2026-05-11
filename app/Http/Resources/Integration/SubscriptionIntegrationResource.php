<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'     => $this->uuid ?? null,
            'customer' => [
                'uuid'         => $this->customerCari?->uuid,
                'name'         => $this->customerCari?->name,
                'country_code' => $this->customerCari?->country_code,
                'tax_number'   => $this->customerCari?->tax_number,
            ],
            'provider' => [
                'uuid'         => $this->providerCari?->uuid,
                'name'         => $this->providerCari?->name,
                'country_code' => $this->providerCari?->country_code,
                'tax_number'   => $this->providerCari?->tax_number,
            ],
            'internal_contract_no' => $this->sozlesme_no,
            'provider_contract_no' => $this->provider_contract_no ?? null,
            'service_category'     => 'mail',
            'product_name'         => $this->product?->name,
            'quantity'             => (int) $this->quantity,
            'billing_cycle'        => $this->faturalama_periyodu,
            'start_date'           => $this->baslangic_tarihi?->format('Y-m-d'),
            'end_date'             => $this->bitis_tarihi?->format('Y-m-d'),
            'status'               => $this->durum,
            'auto_renew'           => (bool) $this->auto_renew,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
