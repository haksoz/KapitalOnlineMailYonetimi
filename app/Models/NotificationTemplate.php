<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'name',
        'channel',
        'legacy_key',
        'subject',
        'body',
    ];

    public function renderSubject(array $replacements): string
    {
        return strtr((string) $this->subject, $replacements);
    }

    public function renderBody(array $replacements): string
    {
        return strtr((string) $this->body, $replacements);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationRuleAction::class, 'notification_template_id');
    }
}
