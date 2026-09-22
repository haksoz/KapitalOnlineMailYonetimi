<?php

namespace App\Automation;

use App\Models\AutomationRule;

final class ConditionEvaluator
{
    public function matches(AutomationRule $rule, DomainEvent $event): bool
    {
        $conditions = $rule->conditions;
        if (! is_array($conditions) || $conditions === []) {
            return true;
        }

        $context = $event->context;

        if (array_key_exists('auto_renew', $conditions)) {
            $expected = (bool) $conditions['auto_renew'];
            $actual = (bool) ($context['auto_renew'] ?? false);
            if ($actual !== $expected) {
                return false;
            }
        }

        if (array_key_exists('subscription_durum', $conditions)) {
            $expected = (string) $conditions['subscription_durum'];
            $actual = (string) ($context['subscription_durum'] ?? '');
            if ($actual !== $expected) {
                return false;
            }
        }

        if (array_key_exists('invoice_paid', $conditions)) {
            $expected = (bool) $conditions['invoice_paid'];
            $actual = (bool) ($context['invoice_paid'] ?? false);
            if ($actual !== $expected) {
                return false;
            }
        }

        return true;
    }
}
