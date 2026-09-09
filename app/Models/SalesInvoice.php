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
        'due_date',
        'order_number',
        'total_amount_tl',
        'invoice_total_net_tl',
        'invoice_total_vat_tl',
        'invoice_total_gross_tl',
        'invoice_total_diff_tl',
        'invoice_total_diff_reason',
        'notes',
        'is_paid',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'our_invoice_date' => 'date',
            'due_date' => 'date',
            'total_amount_tl' => 'decimal:2',
            'invoice_total_net_tl' => 'decimal:2',
            'invoice_total_vat_tl' => 'decimal:2',
            'invoice_total_gross_tl' => 'decimal:2',
            'invoice_total_diff_tl' => 'decimal:2',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function markAsPaid(): void
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
        ]);
    }

    public function markAsUnpaid(): void
    {
        $this->update([
            'is_paid' => false,
            'paid_at' => null,
        ]);
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
     * Fatura tarihi + satır aboneliklerindeki en kısa ödeme vadesi.
     * Numara veya tarih yoksa, ya da hiç vade tanımlı değilse null.
     */
    public function computeDueDate(): ?\Carbon\CarbonInterface
    {
        if ($this->our_invoice_date === null || blank($this->our_invoice_number)) {
            return null;
        }

        $this->loadMissing('lines.pendingBilling.subscription');

        $days = $this->lines
            ->map(fn (SalesInvoiceLine $line) => $line->pendingBilling?->subscription?->odeme_vadesi_gun)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->min();

        if ($days === null) {
            return null;
        }

        return $this->our_invoice_date->copy()->addDays((int) $days);
    }

    public function refreshDueDate(): void
    {
        $this->due_date = $this->computeDueDate();
        $this->save();
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

    public function systemVatTotalTl(): float
    {
        $this->loadMissing('lines.pendingBilling.subscription');

        $vat = 0.0;
        foreach ($this->lines as $line) {
            $vatRate = $line->pendingBilling?->subscription?->vat_rate !== null
                ? (float) $line->pendingBilling->subscription->vat_rate
                : 20.0;
            $vat += round((float) $line->line_amount_tl * ($vatRate / 100), 2);
        }

        return round($vat, 2);
    }

    /**
     * Mail ve bildirimlerde kullanılan tutar (her zaman KDV dahil).
     * Kesilen faturanın kayıtlı brütü varsa o; yoksa kayıtlı net + KDV; o da yoksa sistem net + satır KDV.
     */
    public function payableAmountTl(): ?float
    {
        if ($this->invoice_total_gross_tl !== null) {
            return round((float) $this->invoice_total_gross_tl, 2);
        }

        if ($this->invoice_total_net_tl !== null) {
            $vat = $this->invoice_total_vat_tl !== null
                ? (float) $this->invoice_total_vat_tl
                : $this->systemVatTotalTl();

            return round((float) $this->invoice_total_net_tl + $vat, 2);
        }

        if ($this->total_amount_tl === null) {
            return null;
        }

        return round((float) $this->total_amount_tl + $this->systemVatTotalTl(), 2);
    }
}
