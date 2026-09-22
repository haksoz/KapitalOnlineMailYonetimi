<?php

namespace App\Models;

use App\Automation\ActionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRuleAction extends Model
{
    protected $fillable = [
        'automation_rule_id',
        'action_type',
        'notification_template_id',
        'config',
        'sort_order',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'config' => 'array',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AutomationJob::class, 'automation_rule_action_id');
    }

    public function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }
}
