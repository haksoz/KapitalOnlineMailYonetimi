<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDefinition extends Model
{
    public const KEY_INVOICE_DUE_REMINDER = 'invoice_due_reminder';
    public const KEY_INVOICE_OVERDUE = 'invoice_overdue';

    protected $table = 'notification_definitions';

    protected $fillable = [
        'key',
        'name',
        'is_enabled',
        'start_after_days',
        'interval_days',
        'subject',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'start_after_days' => 'integer',
            'interval_days' => 'integer',
        ];
    }

    public function sends(): HasMany
    {
        return $this->hasMany(NotificationSend::class, 'notification_definition_id');
    }

    public function renderSubject(array $replacements): string
    {
        return strtr($this->subject, $replacements);
    }

    public function renderBody(array $replacements): string
    {
        return strtr($this->body, $replacements);
    }
}
