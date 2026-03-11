<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $table = 'sales_invoices';

    protected $fillable = [
        'customer_cari_id',
        'our_invoice_number',
        'our_invoice_date',
        'order_number',
        'total_amount_tl',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'our_invoice_date' => 'date',
            'total_amount_tl' => 'decimal:2',
        ];
    }

    public function customerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'customer_cari_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id');
    }

    /**
     * Sonraki Fatura Takip No (FTN000001, FTN000002, ...) değerini döndürür.
     */
    public static function getNextFaturaTakipNo(): string
    {
        $existing = static::whereNotNull('order_number')
            ->where('order_number', 'like', 'FTN%')
            ->pluck('order_number');

        $maxNum = 0;
        foreach ($existing as $n) {
            if (preg_match('/^FTN(\d{6})$/', $n, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return 'FTN' . str_pad((string) ($maxNum + 1), 6, '0', STR_PAD_LEFT);
    }
}
