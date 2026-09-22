<?php

namespace App\Automation;

enum RunStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Succeeded => 'Başarılı',
            self::Failed => 'Hatalı',
            self::Skipped => 'Atlandı',
        };
    }
}
