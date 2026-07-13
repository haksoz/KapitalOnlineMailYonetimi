<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Models\SubscriptionPriceHistory;

class SubscriptionObserver
{
    private const PRICE_FIELDS = [
        'usd_birim_alis',
        'usd_birim_satis',
    ];

    public function updated(Subscription $subscription): void
    {
        $changedBy = auth()->id();
        $now = now();

        foreach (self::PRICE_FIELDS as $field) {
            if (! $subscription->wasChanged($field)) {
                continue;
            }

            $oldValue = $subscription->getOriginal($field);
            $newValue = $subscription->getAttribute($field);

            if ($this->isEqual($oldValue, $newValue)) {
                continue;
            }

            SubscriptionPriceHistory::create([
                'subscription_id' => $subscription->id,
                'field_name' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $changedBy,
                'reason' => null,
                'created_at' => $now,
            ]);
        }
    }

    private function isEqual(mixed $old, mixed $new): bool
    {
        if ($old === null && $new === null) {
            return true;
        }

        if ($old === null || $new === null) {
            return false;
        }

        return (string) $old === (string) $new;
    }
}
