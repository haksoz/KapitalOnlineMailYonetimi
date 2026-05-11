<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingBillingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'status' => $this->status,
            'is_deleted' => $this->is_deleted,
            'expected_alis_tl' => $this->expected_alis_tl,
            'expected_satis_tl' => $this->expected_satis_tl,
            'exchange_rate_used' => $this->exchange_rate_used,
            'amounts_updated_at' => $this->amounts_updated_at?->format('Y-m-d H:i:s'),
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'supplier_invoice_date' => $this->supplier_invoice_date?->format('Y-m-d'),
            'actual_alis_tl' => $this->actual_alis_tl,
            'supplier_invoice_total_tl' => $this->supplier_invoice_total_tl,
            'supplier_invoice_total_diff_tl' => $this->supplier_invoice_total_diff_tl,
            'actual_satis_tl' => $this->actual_satis_tl,
            'fee_difference_tl' => $this->fee_difference_tl,
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'sales_invoice_line' => new SalesInvoiceLineResource($this->whenLoaded('salesInvoiceLine')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
