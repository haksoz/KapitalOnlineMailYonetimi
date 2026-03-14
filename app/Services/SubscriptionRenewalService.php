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

        $this->applyCancellationsUpTo($asOf);

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

        $this->applyCancellationsUpTo($upToDate);

        return [
            'renewed_ids' => $renewedIds,
            'total_extensions' => $totalExtensions,
        ];
    }

    /**
     * Sadece belirtilen aboneliklerin bitiş tarihini verilen tarihe kadar uzatır.
     * Önizleme sonrası "sadece seçilenleri güncelle" için kullanılır. İptal (applyCancellations) çalıştırılmaz.
     *
     * @param  array<int>  $subscriptionIds
     * @return array{renewed_ids: array<int>, total_extensions: int}
     */
    public function processRenewalsUpToForSubscriptionIds(Carbon $upToDate, array $subscriptionIds): array
    {
        if ($subscriptionIds === []) {
            return ['renewed_ids' => [], 'total_extensions' => 0];
        }

        $subscriptions = Subscription::query()
            ->whereIn('id', $subscriptionIds)
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
     * planned_cancel_date veya auto_renew=false / bitiş tarihi geçmiş olan abonelikleri iptal eder.
     */
    protected function applyCancellationsUpTo(Carbon $upToDate): void
    {
        // 1) İptal talimatı girilmiş abonelikler
        $planned = Subscription::query()
            ->where('durum', Subscription::DURUM_PENDING)
            ->whereNotNull('planned_cancel_date')
            ->whereDate('planned_cancel_date', '<=', $upToDate)
            ->get();

        foreach ($planned as $subscription) {
            $subscription->update([
                'durum' => Subscription::DURUM_CANCELLED,
            ]);
        }

        // 2) Otomatik yenileme kapalı, bitiş tarihi geçmiş aktif abonelikler
        $expiredNonRenewing = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->where('auto_renew', false)
            ->whereNotNull('bitis_tarihi')
            ->whereDate('bitis_tarihi', '<=', $upToDate)
            ->get();

        foreach ($expiredNonRenewing as $subscription) {
            $subscription->update([
                'durum' => Subscription::DURUM_CANCELLED,
            ]);
        }
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
