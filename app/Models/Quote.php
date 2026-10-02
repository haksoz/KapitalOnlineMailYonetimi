<?php

namespace App\Models;

use App\Services\QuoteMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Quote extends Model
{
    public const TYPE_OPTIONAL = 'optional';

    public const TYPE_FIRM = 'firm';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const COMMITMENTS = [
        Subscription::TAAHHUT_MONTHLY_COMMITMENT,
        Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT,
        Subscription::TAAHHUT_ANNUAL_COMMITMENT,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_CONVERTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'uuid',
        'quote_number',
        'customer_cari_id',
        'type',
        'status',
        'currency',
        'vat_rate',
        'valid_until',
        'notes',
        'internal_notes',
        'source_quote_id',
        'created_by',
        'sent_at',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'vat_rate' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            if (empty($quote->uuid)) {
                $quote->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (Quote $quote): void {
            $itemIds = $quote->items()->pluck('id');
            if ($itemIds->isEmpty()) {
                return;
            }

            QuoteItemOption::query()->whereIn('quote_item_id', $itemIds)->delete();
            QuoteItem::query()->whereIn('id', $itemIds)->delete();
        });
    }

    public function customerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'customer_cari_id');
    }

    public function sourceQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_quote_id');
    }

    public function derivedQuote(): HasOne
    {
        return $this->hasOne(self::class, 'source_quote_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isOptional(): bool
    {
        return $this->type === self::TYPE_OPTIONAL;
    }

    public function isFirm(): bool
    {
        return $this->type === self::TYPE_FIRM;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canRevise(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canConvert(): bool
    {
        if (! $this->isOptional() || ! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT], true)) {
            return false;
        }

        if ($this->relationLoaded('derivedQuote')) {
            return $this->derivedQuote === null;
        }

        return $this->derivedQuote()->doesntExist();
    }

    public function currencyLabel(): string
    {
        return $this->currency === Subscription::CURRENCY_TRY ? 'TL' : 'USD';
    }

    public function formatMoney(?string $amount, int $scale = 2): string
    {
        return QuoteMath::display($amount, $scale).' '.$this->currencyLabel();
    }

    /**
     * @return array{net: string, cost: string, vat: string, gross: string, profit: string, profit_rate: ?string}
     */
    public function firmSummary(): array
    {
        $net = '0.00';
        $cost = '0.00';
        $vat = '0.00';

        foreach ($this->items as $item) {
            $lineNet = $item->saleTotal();
            $lineCost = $item->costTotal();
            $net = QuoteMath::add($net, $lineNet);
            $cost = QuoteMath::add($cost, $lineCost);
            $vat = QuoteMath::add($vat, QuoteMath::vat($lineNet, $this->vat_rate ?? '0'));
        }

        $profit = QuoteMath::sub($net, $cost);

        return [
            'net' => $net,
            'cost' => $cost,
            'vat' => $vat,
            'gross' => QuoteMath::add($net, $vat),
            'profit' => $profit,
            'profit_rate' => QuoteMath::rate($profit, $cost),
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_OPTIONAL => 'Birim Fiyat Teklifi',
            self::TYPE_FIRM => 'Kesin Teklif',
            default => $type,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'Taslak',
            self::STATUS_SENT => 'Gönderildi',
            self::STATUS_CONVERTED => 'Kesin teklife dönüştürüldü',
            self::STATUS_APPROVED => 'Onaylandı',
            self::STATUS_REJECTED => 'Reddedildi',
            self::STATUS_EXPIRED => 'Süresi doldu',
            self::STATUS_CANCELLED => 'İptal',
            default => $status,
        };
    }

    public static function commitmentLabel(string $tip): string
    {
        return match ($tip) {
            Subscription::TAAHHUT_MONTHLY_COMMITMENT => 'Aylık Taahhütlü',
            Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT => 'Aylık Taahhütsüz',
            Subscription::TAAHHUT_ANNUAL_COMMITMENT => 'Yıllık Taahhütlü',
            default => $tip,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function commitmentLabels(): array
    {
        $labels = [];
        foreach (self::COMMITMENTS as $tip) {
            $labels[$tip] = self::commitmentLabel($tip);
        }

        return $labels;
    }
}
