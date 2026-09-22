<?php

namespace App\Models;

use App\Automation\EventType;
use App\Automation\JobStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationJob extends Model
{
    protected $fillable = [
        'automation_rule_id',
        'automation_rule_action_id',
        'event_type',
        'cari_id',
        'subject_type',
        'subject_id',
        'occurrence_key',
        'status',
        'scheduled_at',
        'executed_at',
        'context',
        'to_email',
        'error_message',
        'attempt_count',
        'parent_job_id',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'status' => JobStatus::class,
            'scheduled_at' => 'datetime',
            'executed_at' => 'datetime',
            'context' => 'array',
            'attempt_count' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(AutomationRuleAction::class, 'automation_rule_action_id');
    }

    public function cari(): BelongsTo
    {
        return $this->belongsTo(Cari::class, 'cari_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_job_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationJobRun::class, 'automation_job_id');
    }

    public function scopeDue(Builder $query): void
    {
        $query->where('status', JobStatus::Pending)
            ->where('scheduled_at', '<=', now());
    }

    public function subjectLabel(): string
    {
        $subject = $this->relationLoaded('subject') ? $this->subject : null;
        if ($subject instanceof SalesInvoice) {
            return (string) ($subject->our_invoice_number ?: 'Fatura #'.$subject->id);
        }
        if ($subject instanceof Subscription) {
            return (string) ($subject->sozlesme_no ?: 'Abonelik #'.$subject->id);
        }
        if ($subject instanceof PendingBilling) {
            return 'Sipariş #'.$subject->id;
        }

        return class_basename((string) $this->subject_type).' #'.$this->subject_id;
    }

    public function subjectUrl(): ?string
    {
        return match ($this->subject_type) {
            (new SalesInvoice)->getMorphClass() => route('sales-invoices.show', $this->subject_id),
            (new Subscription)->getMorphClass() => route('subscriptions.show', $this->subject_id),
            default => null,
        };
    }
}
