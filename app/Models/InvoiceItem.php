<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'supplier_invoice_id',
        'subscription_id',
        'adet',
        'usd_birim_maliyet',
        'usd_toplam',
        'kur',
        'tl_toplam',
        'birim_satis_fiyati',
        'tl_satis_toplam',
        'kar_orani',
        'odeme_tipi',
        'fark_tutari',
        'satis_fatura_no',
        'satis_durumu',
    ];

    protected function casts(): array
    {
        return [
            'usd_birim_maliyet' => 'decimal:4',
            'usd_toplam' => 'decimal:4',
            'kur' => 'decimal:4',
            'tl_toplam' => 'decimal:2',
            'birim_satis_fiyati' => 'decimal:2',
            'tl_satis_toplam' => 'decimal:2',
            'kar_orani' => 'decimal:2',
            'fark_tutari' => 'decimal:2',
        ];
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
