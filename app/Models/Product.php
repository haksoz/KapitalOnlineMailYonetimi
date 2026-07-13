<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'service_provider_id',
        'name',
        'stock_code',
        'description',
        'alis_usd_monthly_commitment',
        'satis_usd_monthly_commitment',
        'alis_usd_monthly_no_commitment',
        'satis_usd_monthly_no_commitment',
        'alis_usd_yearly_commitment',
        'satis_usd_yearly_commitment',
    ];

    protected function casts(): array
    {
        return [
            'alis_usd_monthly_commitment' => 'decimal:2',
            'satis_usd_monthly_commitment' => 'decimal:2',
            'alis_usd_monthly_no_commitment' => 'decimal:2',
            'satis_usd_monthly_no_commitment' => 'decimal:2',
            'alis_usd_yearly_commitment' => 'decimal:2',
            'satis_usd_yearly_commitment' => 'decimal:2',
        ];
    }

    public function getProfitPercentageMonthlyCommitmentAttribute(): ?float
    {
        return $this->calculateProfit($this->alis_usd_monthly_commitment, $this->satis_usd_monthly_commitment);
    }

    public function getProfitPercentageMonthlyNoCommitmentAttribute(): ?float
    {
        return $this->calculateProfit($this->alis_usd_monthly_no_commitment, $this->satis_usd_monthly_no_commitment);
    }

    public function getProfitPercentageYearlyCommitmentAttribute(): ?float
    {
        return $this->calculateProfit($this->alis_usd_yearly_commitment, $this->satis_usd_yearly_commitment);
    }

    private function calculateProfit(?float $alis, ?float $satis): ?float
    {
        if ($alis === null || $satis === null) {
            return null;
        }
        if ($alis <= 0) {
            return null;
        }
        return round((($satis - $alis) / $alis) * 100, 2);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'product_id');
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_id')->latest('created_at');
    }
}
