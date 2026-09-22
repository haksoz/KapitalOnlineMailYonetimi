<?php

namespace App\Automation;

enum ActionType: string
{
    case Email = 'email';
    case Webhook = 'webhook';
    case InApp = 'in_app';
    case Http = 'http';
    case AssignTask = 'assign_task';
    case Chain = 'chain';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'E-posta gönder',
            self::Webhook => 'Webhook gönder',
            self::InApp => 'Sistem içi bildirim',
            self::Http => 'API çağrısı',
            self::AssignTask => 'Görevi personele ata',
            self::Chain => 'Başka işlem tetikle',
        };
    }

    public function isImplemented(): bool
    {
        return $this === self::Email;
    }
}
