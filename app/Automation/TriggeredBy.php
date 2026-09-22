<?php

namespace App\Automation;

enum TriggeredBy: string
{
    case Scheduler = 'scheduler';
    case AdminRetry = 'admin_retry';
    case AdminManual = 'admin_manual';

    public function label(): string
    {
        return match ($this) {
            self::Scheduler => 'Zamanlayıcı',
            self::AdminRetry => 'Admin tekrar',
            self::AdminManual => 'Admin manuel',
        };
    }
}
