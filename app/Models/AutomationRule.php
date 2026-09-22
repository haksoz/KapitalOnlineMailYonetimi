<?php

namespace App\Models;

use App\Automation\DedupePolicy;
use App\Automation\EventType;
use App\Automation\TimingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $fillable = [
        'name',
        'legacy_key',
        'event_type',
        'is_enabled',
        'conditions',
        'timing',
        'dedupe_policy',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'conditions' => 'array',
            'timing' => 'array',
            'event_type' => EventType::class,
            'dedupe_policy' => DedupePolicy::class,
        ];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationRuleAction::class, 'automation_rule_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AutomationJob::class, 'automation_rule_id');
    }

    public function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    public function timingMode(): TimingMode
    {
        $stored = TimingMode::tryFrom((string) ($this->timing['mode'] ?? ''));
        $event = $this->event_type instanceof EventType ? $this->event_type : null;
        if ($stored instanceof TimingMode && ($event === null || $event->allowsTimingMode($stored))) {
            return $stored;
        }

        return $event?->defaultTimingMode() ?? TimingMode::AtSendAt;
    }

    public function dedupePolicy(): DedupePolicy
    {
        $stored = $this->dedupe_policy instanceof DedupePolicy
            ? $this->dedupe_policy
            : DedupePolicy::tryFrom((string) $this->dedupe_policy);
        $event = $this->event_type instanceof EventType ? $this->event_type : null;
        if ($stored instanceof DedupePolicy && ($event === null || $event->allowsDedupePolicy($stored))) {
            return $stored;
        }

        return $event?->defaultDedupePolicy() ?? DedupePolicy::PerOccurrenceKey;
    }

    public function offsetDays(): int
    {
        return (int) ($this->timing['offset_days'] ?? 0);
    }

    public function intervalDays(): int
    {
        return max(1, (int) ($this->timing['interval_days'] ?? 1));
    }

    public function sendAtForInput(): string
    {
        $value = trim((string) ($this->timing['send_at'] ?? ''));
        if ($value === '') {
            return '10:00';
        }
        if (preg_match('/(\d{1,2}):(\d{2})/', $value, $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return '10:00';
    }
}
