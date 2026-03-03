<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use App\Models\SubscriptionMonthlyProjection;
use Carbon\Carbon;

class SubscriptionProjectionService
{
    /**
     * Get USD→TRY exchange rate for a given date (uses forex_selling from exchange_rates).
     * Returns the rate effective on or before the given date; fallback 1.0 if none found.
     */
    public function getUsdTryRateForDate(Carbon $date): float
    {
        $rate = ExchangeRate::where('currency_code', 'USD')
            ->whereDate('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();

        if ($rate && $rate->forex_selling !== null) {
            return (float) $rate->forex_selling;
        }

        return 1.0;
    }

    /**
     * Create or update a monthly projection for a subscription.
     *
     * Example: SubscriptionProjectionService::generateForSubscriptionAndMonth($subscription, 2026, 3);
     */
    public function generateForSubscriptionAndMonth(
        Subscription $subscription,
        int $year,
        int $month,
        ?int $expectedQuantity = null,
        ?float $expectedUnitCostUsd = null,
        ?float $estimatedExchangeRate = null
    ): SubscriptionMonthlyProjection {
        $quantity = $expectedQuantity ?? (int) ($subscription->quantity ?? 1);
        $unitCostUsd = $expectedUnitCostUsd ?? (float) ($subscription->usd_birim_alis ?? 0);

        $rateDate = Carbon::createFromDate($year, $month, 1);
        $rate = $estimatedExchangeRate ?? $this->getUsdTryRateForDate($rateDate);
        $expectedTotalTry = round($quantity * $unitCostUsd * $rate, 2);

        $projection = SubscriptionMonthlyProjection::firstOrNew([
            'subscription_id' => $subscription->id,
            'year' => $year,
            'month' => $month,
        ]);

        $projection->expected_quantity = $quantity;
        $projection->expected_unit_cost_usd = $unitCostUsd;
        $projection->expected_total_usd = 0;
        $projection->estimated_exchange_rate = $rate;
        $projection->expected_total_try = $expectedTotalTry;

        if (! $projection->exists) {
            $projection->status = SubscriptionMonthlyProjection::STATUS_PROJECTED;
        }
        // If already exists (e.g. invoiced), do not overwrite actual_* / difference_* / status

        $projection->save();

        return $projection;
    }

    /**
     * Generate projection records for a given date's year/month for all active subscriptions.
     * Intended for manual trigger or future cron; billing_day logic can be added later.
     */
    public function generateForBillingDate(Carbon $date): void
    {
        $year = (int) $date->year;
        $month = (int) $date->month;

        $subscriptions = Subscription::where('durum', Subscription::DURUM_ACTIVE)->get();

        foreach ($subscriptions as $subscription) {
            $this->generateForSubscriptionAndMonth($subscription, $year, $month);
        }
    }

    /**
     * Apply an invoice item to the corresponding monthly projection: set actual totals,
     * compute differences, and mark status as invoiced.
     * Call this during invoice import to update the projection for that subscription/month.
     *
     * Example: SubscriptionProjectionService::applyInvoiceItem($invoiceItem);
     */
    public function applyInvoiceItem(InvoiceItem $item): SubscriptionMonthlyProjection
    {
        $item->loadMissing(['subscription', 'supplierInvoice']);
        $subscription = $item->subscription;
        $invoice = $item->supplierInvoice;

        if (! $subscription || ! $invoice) {
            throw new \InvalidArgumentException('InvoiceItem must have subscription and supplierInvoice loaded.');
        }

        $year = (int) ($invoice->donem_yil ?? $invoice->fatura_tarihi?->year ?? now()->year);
        $month = (int) ($invoice->donem_ay ?? $invoice->fatura_tarihi?->month ?? now()->month);

        $projection = $this->generateForSubscriptionAndMonth($subscription, $year, $month);

        $currentActualTry = (float) ($projection->actual_total_try ?? 0);
        $projection->actual_total_try = $currentActualTry + (float) $item->tl_toplam;

        $projection->difference_try = round(
            (float) $projection->actual_total_try - (float) $projection->expected_total_try,
            2
        );
        $projection->status = SubscriptionMonthlyProjection::STATUS_INVOICED;
        $projection->save();

        return $projection;
    }
}
