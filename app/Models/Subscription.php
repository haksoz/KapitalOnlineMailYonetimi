<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'customer_cari_id',
        'provider_cari_id',
        'service_provider_id',
        'product_id',
        'quantity',
        'sozlesme_no',
        'baslangic_tarihi',
        'bitis_tarihi',
        'planned_cancel_date',
        'taahhut_tipi',
        'faturalama_periyodu',
        'durum',
        'auto_renew',
        'usd_birim_alis',
        'usd_birim_satis',
        'vat_rate',
    ];

    protected function casts(): array
    {
        return [
            'baslangic_tarihi' => 'date',
            'bitis_tarihi' => 'date',
            'planned_cancel_date' => 'date',
            'auto_renew' => 'boolean',
            'vat_rate' => 'decimal:2',
        ];
    }

    /**
     * USD birim alış/satış: SQLite/PDO float sapmasını önlemek için decimal:4 cast yerine
     * Brick\Math ile 4 haneye sabitlenir (ör. 6 → 6.0000, 5.9996 artefact düzeltilir).
     */
    protected function usdBirimAlis(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => self::normalizeUsd4Raw($value),
            set: fn ($value) => ['usd_birim_alis' => self::normalizeUsd4Input($value)],
        );
    }

    protected function usdBirimSatis(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => self::normalizeUsd4Raw($value),
            set: fn ($value) => ['usd_birim_satis' => self::normalizeUsd4Input($value)],
        );
    }

    protected static function normalizeUsd4Input(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) BigDecimal::of(trim((string) $value))->toScale(4, RoundingMode::HALF_UP);
    }

    protected static function normalizeUsd4Raw(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_string($raw)) {
            return (string) BigDecimal::of($raw)->toScale(4, RoundingMode::HALF_UP);
        }

        if (is_int($raw)) {
            return (string) BigDecimal::of((string) $raw)->toScale(4, RoundingMode::HALF_UP);
        }

        if (is_float($raw)) {
            return (string) BigDecimal::of(sprintf('%.12F', $raw))->toScale(4, RoundingMode::HALF_UP);
        }

        return (string) BigDecimal::of((string) $raw)->toScale(4, RoundingMode::HALF_UP);
    }

    public function customerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'customer_cari_id');
    }

    public function providerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'provider_cari_id');
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function monthlyProjections(): HasMany
    {
        return $this->hasMany(SubscriptionMonthlyProjection::class);
    }

    public function quantityChanges(): HasMany
    {
        return $this->hasMany(SubscriptionQuantityChange::class)->orderByDesc('effective_date')->orderByDesc('created_at');
    }

    public function pendingBillings(): HasMany
    {
        return $this->hasMany(PendingBilling::class)->orderByDesc('period_start');
    }
}
