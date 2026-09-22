<?php

namespace App\Automation;

enum TimingMode: string
{
    case Immediate = 'immediate';
    case AtSendAt = 'at_send_at';
    case OffsetDays = 'offset_days';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Hemen',
            self::AtSendAt => 'Bugün şu saatte',
            self::OffsetDays => 'X gün sonra şu saatte',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Immediate => 'Kayıt anında gider, cron beklemez.',
            self::AtSendAt => 'Aynı gün seçilen saatte gider. Saat geçtiyse o an gider.',
            self::OffsetDays => 'Olaydan belirtilen gün sonra, seçilen saatte gider.',
        };
    }
}
