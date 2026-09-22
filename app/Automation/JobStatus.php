<?php

namespace App\Automation;

enum JobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Processing => 'İşleniyor',
            self::Succeeded => 'Başarılı',
            self::Failed => 'Hatalı',
            self::Cancelled => 'İptal edildi',
            self::Skipped => 'Atlandı',
        };
    }
}
