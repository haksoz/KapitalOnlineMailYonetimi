<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    protected $table = 'product_price_history';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function fieldLabel(): string
    {
        return match ($this->field_name) {
            'alis_usd_monthly_commitment' => 'Aylık Taahhütlü Alış Fiyatı (USD)',
            'satis_usd_monthly_commitment' => 'Aylık Taahhütlü Satış Fiyatı (USD)',
            'alis_usd_monthly_no_commitment' => 'Aylık Taahhütsüz Alış Fiyatı (USD)',
            'satis_usd_monthly_no_commitment' => 'Aylık Taahhütsüz Satış Fiyatı (USD)',
            'alis_usd_yearly_commitment' => 'Yıllık Taahhütlü Alış Fiyatı (USD)',
            'satis_usd_yearly_commitment' => 'Yıllık Taahhütlü Satış Fiyatı (USD)',
            default => $this->field_name,
        };
    }
}
