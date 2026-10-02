<?php

namespace App\Models;

use App\Services\QuoteMath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItemOption extends Model
{
    protected $fillable = [
        'quote_item_id',
        'taahhut_tipi',
        'birim_alis',
        'birim_satis',
    ];

    protected function casts(): array
    {
        return [
            'birim_alis' => 'decimal:4',
            'birim_satis' => 'decimal:4',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class, 'quote_item_id');
    }

    public function saleTotal(): string
    {
        return QuoteMath::money($this->birim_satis, (int) $this->item->quantity);
    }

    public function costTotal(): string
    {
        return QuoteMath::money($this->birim_alis, (int) $this->item->quantity);
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
        return Quote::commitmentLabel($this->taahhut_tipi);
    }
}
