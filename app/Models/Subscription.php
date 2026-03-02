<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    public const DURUM_ACTIVE = 'active';
    public const DURUM_CANCELLED = 'cancelled';
    public const DURUM_PENDING = 'pending';

    public const TAAHHUT_MONTHLY_COMMITMENT = 'monthly_commitment';
    public const TAAHHUT_MONTHLY_NO_COMMITMENT = 'monthly_no_commitment';
    public const TAAHHUT_ANNUAL_COMMITMENT = 'annual_commitment';

    public const FATURALAMA_MONTHLY = 'monthly';
    public const FATURALAMA_YEARLY = 'yearly';

    protected $fillable = [
        'company_id',
        'service_provider_id',
        'product_id',
        'supplier_id',
        'sozlesme_no',
        'baslangic_tarihi',
        'bitis_tarihi',
        'taahhut_tipi',
        'faturalama_periyodu',
        'durum',
    ];

    protected function casts(): array
    {
        return [
            'baslangic_tarihi' => 'date',
            'bitis_tarihi' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
