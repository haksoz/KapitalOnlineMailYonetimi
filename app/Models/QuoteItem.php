<?php

namespace App\Models;

use App\Services\QuoteMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'product_name',
        'stock_code',
        'sort_order',
        'source_quote_item_id',
        'work_task_id', 'renewal_context', 'response_status', 'response_note', 'responded_at', 'responded_by',
        'quantity',
        'taahhut_tipi',
        'birim_alis',
        'birim_satis',
    ];

    protected function casts(): array
    {
        return [
            'work_task_id' => 'integer', 'renewal_context' => 'array', 'responded_at' => 'datetime',
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'birim_alis' => 'decimal:4',
            'birim_satis' => 'decimal:4',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function workTask(): BelongsTo
    {
        return $this->belongsTo(WorkTask::class);
    }

    public function renewalContext(): ?array
    {
        if ($this->renewal_context) {
            return $this->renewal_context;
        }
        $context = $this->quote->renewal_context;

        return isset($context['product_id']) && (int) $context['product_id'] === (int) $this->product_id ? $context : null;
    }

    public function isAccepted(): bool
    {
        return $this->response_status === 'approved'
            || ($this->response_status === 'pending' && $this->quote->status === Quote::STATUS_APPROVED);
    }

    public function subscriptionRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QuoteSubscriptionRecord::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_quote_item_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuoteItemOption::class);
    }

    public function hasQuantity(): bool
    {
        return $this->quantity !== null;
    }

    public function saleTotal(): string
    {
        return QuoteMath::money($this->birim_satis, (int) $this->quantity);
    }

    public function costTotal(): string
    {
        return QuoteMath::money($this->birim_alis, (int) $this->quantity);
    }

    public function profitAmount(): string
    {
        return QuoteMath::sub($this->saleTotal(), $this->costTotal());
    }

    public function profitRate(): ?string
    {
        return QuoteMath::rate($this->profitAmount(), $this->costTotal());
    }

    public function commitmentLabel(): string
    {
        return $this->taahhut_tipi ? Quote::commitmentLabel($this->taahhut_tipi) : '—';
    }
}
