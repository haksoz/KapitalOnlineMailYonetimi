<?php

namespace App\Automation;

use App\Models\PendingBilling;
use App\Models\Subscription;

final class DomainPlaceholders
{
    /**
     * @return array<string, string>
     */
    public static function forSubscription(Subscription $subscription): array
    {
        $subscription->loadMissing(['customerCari']);
        $cari = $subscription->customerCari;
        $price = $subscription->usd_birim_satis !== null && $subscription->usd_birim_satis !== ''
            ? number_format((float) $subscription->usd_birim_satis, 2, ',', '.')
            : '';

        return [
            '{musteri}' => (string) ($cari?->short_name ?: $cari?->name ?: ''),
            '{abonelik_no}' => (string) ($subscription->sozlesme_no ?? ''),
            '{bitis_tarihi}' => $subscription->bitis_tarihi?->format('d.m.Y') ?? '',
            '{baslangic_tarihi}' => $subscription->baslangic_tarihi?->format('d.m.Y') ?? '',
            '{adet}' => (string) ((int) $subscription->quantity),
            '{fiyat}' => $price,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function subscriptionContext(Subscription $subscription, array $extra = []): array
    {
        return array_merge([
            'auto_renew' => (bool) $subscription->auto_renew,
            'subscription_durum' => (string) $subscription->durum,
            'bitis_tarihi' => $subscription->bitis_tarihi?->toDateString(),
            'placeholders' => self::forSubscription($subscription),
        ], $extra);
    }

    /**
     * @return array<string, string>
     */
    public static function forOrder(PendingBilling $order): array
    {
        $order->loadMissing(['subscription.customerCari']);
        $subscription = $order->subscription;
        $base = $subscription instanceof Subscription
            ? self::forSubscription($subscription)
            : [];

        return array_merge($base, [
            '{donem_baslangic}' => $order->period_start?->format('d.m.Y') ?? '',
            '{donem_bitis}' => $order->period_end?->format('d.m.Y') ?? '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function orderContext(PendingBilling $order, array $extra = []): array
    {
        $order->loadMissing(['subscription']);

        return array_merge([
            'auto_renew' => (bool) ($order->subscription?->auto_renew ?? false),
            'subscription_durum' => (string) ($order->subscription?->durum ?? ''),
            'period_start' => $order->period_start?->toDateString(),
            'period_end' => $order->period_end?->toDateString(),
            'placeholders' => self::forOrder($order),
        ], $extra);
    }
}
