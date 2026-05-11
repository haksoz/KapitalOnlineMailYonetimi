<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicedOrderIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription;
        $salesInvoice = $this->salesInvoiceLine?->salesInvoice;
        $salesInvoiceLine = $this->salesInvoiceLine;

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
            'period_start'       => $this->period_start?->format('Y-m-d'),
            'period_end'         => $this->period_end?->format('Y-m-d'),
            'status'             => $this->status,
            'actual_sales_tl'    => $this->actual_satis_tl !== null ? (float) $this->actual_satis_tl : null,
            'sales_invoice'      => $salesInvoice ? [
                'order_number'          => $salesInvoice->order_number,
                'our_invoice_number'   => $salesInvoice->our_invoice_number,
                'our_invoice_date'     => $salesInvoice->our_invoice_date?->format('Y-m-d'),
                'total_amount_tl'      => $salesInvoice->total_amount_tl !== null ? (float) $salesInvoice->total_amount_tl : null,
                'line_amount_tl'       => $salesInvoiceLine?->line_amount_tl !== null ? (float) $salesInvoiceLine->line_amount_tl : null,
                'invoiced_at'          => $salesInvoice->created_at?->toIso8601String(),
            ] : null,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
