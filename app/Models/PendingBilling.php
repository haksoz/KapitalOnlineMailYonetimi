<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PendingBilling extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_INVOICED = 'invoiced';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_POSTPONED = 'postponed';

    protected $table = 'pending_billings';

    protected $fillable = [
        'subscription_id',
        'period_start',
        'period_end',
        'status',
        'is_deleted',
        'expected_alis_tl',
        'expected_satis_tl',
        'exchange_rate_used',
        'amounts_updated_at',
        'supplier_invoice_number',
        'supplier_invoice_date',
        'actual_alis_tl',
        'supplier_invoice_total_tl',
        'supplier_invoice_total_diff_tl',
        'actual_satis_tl',
        'fee_difference_tl',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'is_deleted' => 'boolean',
            'expected_alis_tl' => 'decimal:2',
            'expected_satis_tl' => 'decimal:2',
            'exchange_rate_used' => 'decimal:6',
            'amounts_updated_at' => 'datetime',
            'supplier_invoice_date' => 'date',
            'actual_alis_tl' => 'decimal:2',
            'supplier_invoice_total_tl' => 'decimal:2',
            'supplier_invoice_total_diff_tl' => 'decimal:2',
            'actual_satis_tl' => 'decimal:2',
            'fee_difference_tl' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('not_deleted', function (Builder $builder): void {
            $builder->where('is_deleted', false);
        });
    }

    public function scopeWithDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_deleted');
    }

    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_deleted')->where('is_deleted', true);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function salesInvoiceLine(): HasOne
    {
        return $this->hasOne(SalesInvoiceLine::class, 'pending_billing_id');
    }
}
