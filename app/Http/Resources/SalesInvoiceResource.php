<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_cari_id' => $this->customer_cari_id,
            'our_invoice_number' => $this->our_invoice_number,
            'our_invoice_date' => $this->our_invoice_date?->format('Y-m-d'),
            'order_number' => $this->order_number,
            'total_amount_tl' => $this->total_amount_tl,
            'invoice_total_net_tl' => $this->invoice_total_net_tl,
            'invoice_total_diff_tl' => $this->invoice_total_diff_tl,
            'invoice_total_diff_reason' => $this->invoice_total_diff_reason,
            'notes' => $this->notes,
            'customer_cari' => new CariResource($this->whenLoaded('customerCari')),
            'lines' => SalesInvoiceLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
