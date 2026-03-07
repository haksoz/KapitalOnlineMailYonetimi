<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'sales_invoice_id',
        'pending_billing_id',
        'line_amount_tl',
    ];

    protected function casts(): array
    {
        return [
            'line_amount_tl' => 'decimal:2',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function pendingBilling(): BelongsTo
    {
        return $this->belongsTo(PendingBilling::class);
    }
}
