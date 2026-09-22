<?php

namespace App\Automation;

use App\Models\AutomationRule;
use App\Models\AutomationRuleAction;

final class OccurrenceKey
{
    public static function make(DomainEvent $event, AutomationRule $rule, AutomationRuleAction $action): string
    {
        $subject = $event->subject;
        $base = $event->type->value
            .':'.$subject->getMorphClass()
            .':'.$subject->getKey();

        $policy = $rule->dedupePolicy();
        if ($policy === DedupePolicy::Once) {
            return $base.':once';
        }

        return $base.':'.$event->fingerprint;
    }
}
