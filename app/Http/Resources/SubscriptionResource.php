<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sozlesme_no' => $this->sozlesme_no,
            'baslangic_tarihi' => $this->baslangic_tarihi?->format('Y-m-d'),
            'bitis_tarihi' => $this->bitis_tarihi?->format('Y-m-d'),
            'planned_cancel_date' => $this->planned_cancel_date?->format('Y-m-d'),
            'taahhut_tipi' => $this->taahhut_tipi,
            'faturalama_periyodu' => $this->faturalama_periyodu,
            'durum' => $this->durum,
            'auto_renew' => $this->auto_renew,
            'quantity' => $this->quantity,
            'usd_birim_satis' => $this->usd_birim_satis,
            'vat_rate' => $this->vat_rate,
            'customer_cari' => new CariResource($this->whenLoaded('customerCari')),
            'provider_cari' => new CariResource($this->whenLoaded('providerCari')),
            'service_provider' => new ServiceProviderResource($this->whenLoaded('serviceProvider')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
