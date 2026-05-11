<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingBillingIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription;

        return [
            'id'           => $this->id,
            'customer'     => [
                'uuid'         => $subscription?->customerCari?->uuid,
                'name'         => $subscription?->customerCari?->name,
                'country_code' => $subscription?->customerCari?->country_code,
                'tax_number'   => $subscription?->customerCari?->tax_number,
            ],
            'subscription' => [
                'uuid'                 => $subscription?->uuid ?? null,
                'internal_contract_no' => $subscription?->sozlesme_no,
                'product_name'         => $subscription?->product?->name,
                'billing_cycle'        => $subscription?->faturalama_periyodu,
                'quantity'             => (int) ($subscription?->quantity ?? 0),
            ],
            'period_start'            => $this->period_start?->format('Y-m-d'),
            'period_end'              => $this->period_end?->format('Y-m-d'),
            'status'                  => $this->status,
            'expected_sales_tl'       => $this->resolveExpectedSatisTl($subscription),
            'actual_sales_tl'         => $this->actual_satis_tl !== null ? (float) $this->actual_satis_tl : null,
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
        ];
    }

    private function resolveExpectedSatisTl($subscription): ?float
    {
        if ($this->expected_satis_tl !== null && $this->expected_satis_tl !== '') {
            return (float) $this->expected_satis_tl;
        }

        if (! $subscription) {
            return null;
        }

        $usdAlis  = $subscription->usd_birim_alis  !== null && $subscription->usd_birim_alis  !== '' ? (float) $subscription->usd_birim_alis  : null;
        $usdSatis = $subscription->usd_birim_satis !== null && $subscription->usd_birim_satis !== '' ? (float) $subscription->usd_birim_satis : null;

        if ($usdAlis === null || $usdAlis <= 0 || $usdSatis === null) {
            return null;
        }

        $rate = $this->exchange_rate_used !== null && $this->exchange_rate_used !== ''
            ? (float) $this->exchange_rate_used
            : (float) ($this->resource->latestExchangeRate ?? 0);

        if ($rate <= 0) {
            return null;
        }

        $qty = (int) $subscription->quantity;
        $alisKdvHaric = $usdAlis * $qty * $rate;

        return round($alisKdvHaric * ($usdSatis / $usdAlis), 2);
    }
}
