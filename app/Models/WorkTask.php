<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkTask extends Model
{
    public const TYPE_RENEWAL = 'subscription_renewal';

    public const TYPE_QUOTE = 'quote_follow_up';

    public const STATUSES = [
        'cost_pending' => 'Yeni dönem fiyat kontrolü bekleniyor',
        'pricing' => 'Teklif oluşturulması bekleniyor',
        'notification_pending' => 'Müşteriye gönderim bekleniyor',
        'response_pending' => 'Müşteri yanıtı bekleniyor',
        'renewal_pending' => 'Abonelik işlemi bekleniyor',
        'completed' => 'Tamamlandı',
        'cancelled' => 'Yenilenmeyecek / İptal',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_to' => 'integer', 'cost_checked_by' => 'integer', 'customer_cari_id' => 'integer',
            'snapshot' => 'array', 'verified_cost' => 'decimal:4', 'proposed_sale' => 'decimal:4',
            'due_date' => 'date', 'next_follow_up' => 'date', 'cost_checked_at' => 'datetime',
            'notified_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function customerCari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'customer_cari_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function costChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cost_checked_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkTaskActivity::class)->latest('id');
    }

    public function quotes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_work_task')->latest('quotes.id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['completed', 'cancelled'], true);
    }

    public function hasBlockingQuote(?int $exceptQuoteId = null): bool
    {
        return $this->quotes()
            ->when($exceptQuoteId, fn ($q) => $q->where('quotes.id', '!=', $exceptQuoteId))
            ->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT, Quote::STATUS_APPROVED, Quote::STATUS_PARTIALLY_APPROVED])
            ->whereHas('items', fn ($q) => $q->where('work_task_id', $this->id)->where('response_status', '!=', 'rejected'))
            ->exists();
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function purposeLabel(): string
    {
        return ($this->snapshot['auto_renew'] ?? false) ? 'Otomatik yenileme fiyat bildirimi' : 'Abonelik yenileme teklifi';
    }

    public function record(string $description, ?int $userId = null, array $changes = []): void
    {
        $this->activities()->create(['user_id' => $userId, 'description' => $description, 'changes' => $changes ?: null]);
    }

    public function recordQuoteDelivery(Quote $quote): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($quote) {
            $task = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $task->isOpen() || $task->hasBlockingQuote($quote->id)) {
                return;
            }
            $today = now('Europe/Istanbul')->startOfDay();
            $followUp = $today->copy()->addDays(3)->min($task->due_date)->max($today);
            $values = ['notified_at' => now()];
            if (! $quote->items()->where('work_task_id', $task->id)->where('response_status', '!=', 'pending')->exists()) {
                $values += ['status' => ($task->snapshot['auto_renew'] ?? false) ? 'renewal_pending' : 'response_pending', 'next_follow_up' => $followUp->toDateString()];
            }
            $task->update($values);
            $task->record($quote->quote_number.' müşteriye e-posta ile iletildi.', $quote->created_by);
        });
    }
}
