<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPriceHistory extends Model
{
    protected $table = 'subscription_price_history';

    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'field_name',
        'old_value',
        'new_value',
        'changed_by',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'decimal:4',
            'new_value' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function fieldLabel(): string
    {
        return match ($this->field_name) {
            'usd_birim_alis' => 'Birim Alış Fiyatı (USD)',
            'usd_birim_satis' => 'Birim Satış Fiyatı (USD)',
            default => $this->field_name,
        };
    }
}
