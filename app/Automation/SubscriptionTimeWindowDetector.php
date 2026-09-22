<?php

namespace App\Automation;

use App\Models\AutomationRule;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class SubscriptionTimeWindowDetector
{
    public function __construct(
        private readonly AutomationBus $bus,
        private readonly ConditionEvaluator $conditions,
    ) {
    }

    public function detect(?CarbonInterface $now = null): int
    {
        $now = Carbon::parse($now ?? now())->timezone(Automation::TIMEZONE);
        $today = Carbon::parse($now->toDateString())->startOfDay();

        $rules = AutomationRule::query()
            ->enabled()
            ->whereIn('event_type', array_map(
                fn (EventType $type) => $type->value,
                EventType::subscriptionWindowTypes(),
            ))
            ->with(['actions' => fn ($q) => $q->enabled()->orderBy('sort_order')])
            ->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        $subscriptions = Subscription::query()
            ->with(['customerCari'])
            ->whereNotNull('bitis_tarihi')
            ->whereHas('customerCari', function ($q): void {
                $q->receivesNotifications();
            })
            ->get();

        $queued = 0;
        foreach ($rules as $rule) {
            foreach ($subscriptions as $subscription) {
                if (! $this->matchesWindow($rule, $subscription, $today)) {
                    continue;
                }

                $end = Carbon::parse($subscription->bitis_tarihi->format('Y-m-d'))->startOfDay();
                $fingerprint = $rule->event_type === EventType::SubscriptionExpiryApproaching
                    ? 'end:'.$end->toDateString().':offset:'.$rule->offsetDays()
                    : 'end:'.$end->toDateString();

                $event = new DomainEvent(
                    type: $rule->event_type,
                    subject: $subscription,
                    cariId: $subscription->customer_cari_id,
                    context: DomainPlaceholders::subscriptionContext($subscription),
                    fingerprint: $fingerprint,
                    occurredAt: $now,
                );
                if (! $this->conditions->matches($rule, $event)) {
                    continue;
                }

                foreach ($rule->actions as $action) {
                    if ($this->bus->enqueue($event, $rule, $action) !== null) {
                        $queued++;
                    }
                }
            }
        }

        return $queued;
    }

    private function matchesWindow(AutomationRule $rule, Subscription $subscription, Carbon $today): bool
    {
        if ($subscription->bitis_tarihi === null) {
            return false;
        }
        $end = Carbon::parse($subscription->bitis_tarihi->format('Y-m-d'))->startOfDay();

        if ($rule->event_type === EventType::SubscriptionExpiryApproaching) {
            if ($today->gt($end)) {
                return false;
            }
            $startOn = $end->copy()->subDays($rule->offsetDays());

            return $today->gte($startOn);
        }

        if ($rule->event_type === EventType::SubscriptionExpired) {
            return $today->gt($end) || (
                $today->equalTo($end) && $subscription->durum === Subscription::DURUM_CANCELLED
            );
        }

        return false;
    }
}
