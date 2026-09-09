<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseSettlement extends Model
{
    protected $table = 'expense_settlements';

    protected $fillable = [
        'customer_cari_id',
        'gider_number',
        'settlement_date',
        'total_amount_tl',
        'notes',
        'is_closed',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'total_amount_tl' => 'decimal:2',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function markAsClosed(): void
    {
        $this->update([
            'is_closed' => true,
            'closed_at' => now(),
        ]);
    }

    public function markAsOpen(): void
    {
        $this->update([
            'is_closed' => false,
            'closed_at' => null,
        ]);
    }

    public function customerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'customer_cari_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseSettlementLine::class, 'expense_settlement_id');
    }

    /**
     * Sonraki Gider No (GDN000001, GDN000002, ...) değerini döndürür.
     */
    public static function getNextGiderNo(): string
    {
        $existing = static::whereNotNull('gider_number')
            ->where('gider_number', 'like', 'GDN%')
            ->pluck('gider_number');

        $maxNum = 0;
        foreach ($existing as $n) {
            if (preg_match('/^GDN(\d{6})$/', $n, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return 'GDN' . str_pad((string) ($maxNum + 1), 6, '0', STR_PAD_LEFT);
    }
}
