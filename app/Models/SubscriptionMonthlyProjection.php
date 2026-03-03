<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionMonthlyProjection extends Model
{
    public const STATUS_PROJECTED = 'projected';
    public const STATUS_INVOICED = 'invoiced';

    protected $table = 'subscription_monthly_projections';

    protected $fillable = [
        'subscription_id',
        'year',
        'month',
        'expected_quantity',
        'expected_unit_cost_usd',
        'expected_total_usd',
        'estimated_exchange_rate',
        'expected_total_try',
        'actual_total_usd',
        'actual_total_try',
        'difference_usd',
        'difference_try',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'expected_quantity' => 'integer',
            'expected_unit_cost_usd' => 'decimal:4',
            'expected_total_usd' => 'decimal:4',
            'estimated_exchange_rate' => 'decimal:6',
            'expected_total_try' => 'decimal:2',
            'actual_total_usd' => 'decimal:4',
            'actual_total_try' => 'decimal:2',
            'difference_usd' => 'decimal:4',
            'difference_try' => 'decimal:2',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
