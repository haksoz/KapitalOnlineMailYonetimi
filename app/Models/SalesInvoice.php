<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $table = 'sales_invoices';

    protected $fillable = [
        'customer_cari_id',
        'our_invoice_number',
        'our_invoice_date',
        'total_amount_tl',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'our_invoice_date' => 'date',
            'total_amount_tl' => 'decimal:2',
        ];
    }

    public function customerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'customer_cari_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id');
    }
}
