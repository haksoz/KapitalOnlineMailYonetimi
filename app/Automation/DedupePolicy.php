<?php

namespace App\Automation;

enum DedupePolicy: string
{
    case Once = 'once';
    case PerOccurrenceKey = 'per_occurrence_key';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Bir kez (otomatik tekrar yok)',
            self::PerOccurrenceKey => 'Pencere başına bir kez',
        };
    }
}
