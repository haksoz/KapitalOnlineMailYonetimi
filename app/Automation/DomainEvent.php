<?php

namespace App\Automation;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

final class DomainEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly EventType $type,
        public readonly Model $subject,
        public readonly ?int $cariId,
        public readonly array $context,
        public readonly string $fingerprint,
        public readonly CarbonInterface $occurredAt,
    ) {
    }
}
