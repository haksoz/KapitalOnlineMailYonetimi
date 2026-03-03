<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'cari_id',
        'fatura_no',
        'fatura_tarihi',
        'donem_ay',
        'donem_yil',
        'para_birimi',
        'xml_path',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupplierInvoice $invoice): void {
            // Sadece oluşturma anında: kullanıcı dönem vermemişse fatura_tarihi'nden doldur
            $periodNotSet = ($invoice->donem_ay === null || $invoice->donem_ay === 0 || $invoice->donem_ay === '')
                && ($invoice->donem_yil === null || $invoice->donem_yil === 0 || $invoice->donem_yil === '');
            if ($periodNotSet && $invoice->fatura_tarihi) {
                $date = $invoice->fatura_tarihi instanceof Carbon
                    ? $invoice->fatura_tarihi
                    : Carbon::parse($invoice->fatura_tarihi);
                $invoice->donem_ay = (int) $date->month;
                $invoice->donem_yil = (int) $date->year;
            }
        });
        // Güncellemede donem_ay / donem_yil otomatik değiştirilmez; kullanıcı değeri korunur.
    }

    protected function casts(): array
    {
        return [
            'fatura_tarihi' => 'date',
        ];
    }

    public function cari(): BelongsTo
    {
        return $this->belongsTo(Cari::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'supplier_invoice_id');
    }
}
