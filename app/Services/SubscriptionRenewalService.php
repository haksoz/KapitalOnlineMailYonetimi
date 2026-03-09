<?php

namespace App\Services;

use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionRenewalService
{
    /**
     * Aktif, otomatik yenileme açık ve dönem bitişi geçmiş aboneliklerin bitiş tarihini
     * taahhüt tipine göre bir periyot ileri alır ve kaydeder.
     *
     * @param  Carbon|null  $asOfDate  Hangi tarihe göre "geçmiş" sayılacak (varsayılan: bugün)
     * @return array Yenilenen abonelik id'leri
     */
    public function processRenewals(?Carbon $asOfDate = null): array
    {
        $asOf = $asOfDate ?? Carbon::today();

        $subscriptions = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->where('auto_renew', true)
            ->whereNotNull('bitis_tarihi')
            ->whereDate('bitis_tarihi', '<=', $asOf)
            ->get();

        $renewed = [];

        foreach ($subscriptions as $subscription) {
            $currentEnd = $subscription->bitis_tarihi;
            if (! $currentEnd) {
                continue;
            }

            $nextEnd = $this->addPeriod($currentEnd, $subscription->taahhut_tipi);
            $subscription->update(['bitis_tarihi' => $nextEnd]);
            $renewed[] = $subscription->id;
        }

        return $renewed;
    }

    /**
     * Bitiş tarihi verilen tarihe kadar geçmiş olan (aktif, otomatik yenileme açık) aboneliklerin
     * bitiş tarihini, bitiş > upToDate olana kadar periyot ekleyerek günceller.
     * Tek seferde "bugüne kadar" yenileme yapar.
     *
     * @return array{renewed_ids: array<int>, total_extensions: int}
     */
    public function processRenewalsUpTo(Carbon $upToDate): array
    {
        $subscriptions = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->where('auto_renew', true)
            ->whereNotNull('bitis_tarihi')
            ->whereDate('bitis_tarihi', '<=', $upToDate)
            ->get();

        $renewedIds = [];
        $totalExtensions = 0;

        foreach ($subscriptions as $subscription) {
            $currentEnd = $subscription->bitis_tarihi;
            if (! $currentEnd) {
                continue;
            }

            $extensions = 0;
            while ($currentEnd->lte($upToDate)) {
                $nextEnd = $this->addPeriod($currentEnd->copy(), $subscription->taahhut_tipi);
                $subscription->update(['bitis_tarihi' => $nextEnd]);
                $currentEnd = $nextEnd;
                $subscription->refresh();
                $extensions++;
                $totalExtensions++;
            }

            if ($extensions > 0) {
                $renewedIds[] = $subscription->id;
            }
        }

        return [
            'renewed_ids' => $renewedIds,
            'total_extensions' => $totalExtensions,
        ];
    }

    /**
     * Taahhüt tipine göre verilen tarihe bir periyot ekler.
     */
    public function addPeriod(Carbon $date, string $taahhutTipi): Carbon
    {
        $copy = $date->copy();

        if ($taahhutTipi === Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT) {
            return $copy->addMonth();
        }

        return $copy->addYear();
    }

    /**
     * Başlangıç tarihi ve taahhüt tipine göre ilk dönem bitiş tarihini hesaplar.
     * Yeni abonelik kaydında bitiş boşsa kullanılır.
     */
    public function computeInitialEndDate(Carbon $baslangicTarihi, string $taahhutTipi): Carbon
    {
        return $this->addPeriod($baslangicTarihi->copy(), $taahhutTipi);
    }
}
