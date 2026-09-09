<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDefinition extends Model
{
    public const KEY_INVOICE_DUE_REMINDER = 'invoice_due_reminder';
    public const KEY_INVOICE_OVERDUE = 'invoice_overdue';

    public const TIMEZONE = 'Europe/Istanbul';

    protected $table = 'notification_definitions';

    protected $fillable = [
        'key',
        'name',
        'is_enabled',
        'start_after_days',
        'interval_days',
        'send_at',
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

    public function sendAtForInput(): string
    {
        $value = $this->send_at;
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '10:00';
        }

        if (preg_match('/(\d{1,2}):(\d{2})/', $value, $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return '10:00';
    }

    public function isSendTimeReached(?CarbonInterface $now = null): bool
    {
        $now = Carbon::parse($now ?? now())->timezone(self::TIMEZONE);
        [$hour, $minute] = array_pad(explode(':', $this->sendAtForInput()), 2, '0');
        $scheduled = $now->copy()->setTime((int) $hour, (int) $minute, 0);

        return $now->gte($scheduled);
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
