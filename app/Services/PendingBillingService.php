<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\PendingBilling;
use App\Models\Subscription;
use Carbon\Carbon;

class PendingBillingService
{
    /**
     * Tek bir ödeme bekleyen kaydının beklenen alış/satış TL tutarlarını güncel kur ile günceller.
     * Kur verilmezse bugünkü USD efektif satış (forex_selling) kullanılır.
     *
     * @return bool Güncelleme yapıldıysa true (kur ve abonelik verisi yeterliyse)
     */
    public function refreshAmountsForRecord(PendingBilling $pendingBilling, ?float $rate = null): bool
    {
        if ($pendingBilling->status !== PendingBilling::STATUS_PENDING) {
            return false;
        }

        // Alış faturası girildiyse beklenen alış 0 TL sabit; kur ile ezme
        if ($pendingBilling->actual_alis_tl !== null && $pendingBilling->actual_alis_tl !== '') {
            return false;
        }

        if ($rate === null) {
            $usd = ExchangeRate::where('currency_code', 'USD')
                ->where('effective_date', Carbon::today()->toDateString())
                ->first();
            if ($usd?->forex_selling !== null && $usd->forex_selling !== '') {
                $rate = (float) $usd->forex_selling;
            } else {
                $usdLast = ExchangeRate::where('currency_code', 'USD')
                    ->whereNotNull('forex_selling')
                    ->orderByDesc('effective_date')
                    ->first();
                $rate = $usdLast?->forex_selling !== null && $usdLast->forex_selling !== '' ? (float) $usdLast->forex_selling : null;
            }
        }

        if ($rate === null) {
            return false;
        }

        $subscription = $pendingBilling->subscription;
        if ($subscription->usd_birim_alis === null) {
            return false;
        }

        $quantity = (int) $subscription->quantity;
        $alisKdvHaric = (float) $subscription->usd_birim_alis * $quantity * $rate;
        $satisTl = null;
        if ((float) $subscription->usd_birim_alis > 0 && $subscription->usd_birim_satis !== null) {
            $satisTl = $alisKdvHaric * ((float) $subscription->usd_birim_satis / (float) $subscription->usd_birim_alis);
        }

        $pendingBilling->update([
            'expected_alis_tl' => $alisKdvHaric,
            'expected_satis_tl' => $satisTl,
            'exchange_rate_used' => $rate,
            'amounts_updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Beklemedeki (pending) tüm kayıtların beklenen alış/satış tutarlarını bugünkü kur ile günceller.
     *
     * @return int Güncellenen kayıt sayısı
     */
    public function refreshAllPendingAmounts(): int
    {
        $usd = ExchangeRate::where('currency_code', 'USD')
            ->where('effective_date', Carbon::today()->toDateString())
            ->first();
        if ($usd?->forex_selling !== null && $usd->forex_selling !== '') {
            $rate = (float) $usd->forex_selling;
        } else {
            $usdLast = ExchangeRate::where('currency_code', 'USD')
                ->whereNotNull('forex_selling')
                ->orderByDesc('effective_date')
                ->first();
            $rate = $usdLast?->forex_selling !== null && $usdLast->forex_selling !== '' ? (float) $usdLast->forex_selling : null;
        }

        if ($rate === null) {
            return 0;
        }

        $pending = PendingBilling::where('status', PendingBilling::STATUS_PENDING)
            ->with('subscription')
            ->get();

        $updated = 0;
        foreach ($pending as $pb) {
            if ($this->refreshAmountsForRecord($pb, $rate)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Abonelik oluşturulduğunda ilk dönemi ödeme bekleyenlere ekler.
     * Koşul: baslangic_tarihi <= period_start < bitis_tarihi (ilk dönem için sağlanır).
     */
    public function addFirstPeriodForSubscription(Subscription $subscription): ?PendingBilling
    {
        $subscription->refresh();
        $baslangic = $subscription->baslangic_tarihi;
        $bitis = $subscription->bitis_tarihi;

        if (! $baslangic || ! $bitis) {
            return null;
        }

        $periodStart = $baslangic->copy();
        if ($periodStart->gte($bitis)) {
            return null;
        }

        $existing = PendingBilling::query()
            ->withDeleted()
            ->where('subscription_id', $subscription->id)
            ->where('period_start', $periodStart->toDateString())
            ->first();

        if ($existing !== null) {
            if ($existing->is_deleted) {
                $existing->update([
                    'is_deleted' => false,
                    'status' => PendingBilling::STATUS_PENDING,
                ]);

                return $existing->fresh();
            }

            return null;
        }

        $periodEnd = $this->computePeriodEnd($periodStart, $subscription->faturalama_periyodu);

        return PendingBilling::create([
            'subscription_id' => $subscription->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => PendingBilling::STATUS_PENDING,
        ]);
    }

    /**
     * Verilen tarihte dönem başı gelen aktif abonelikler için havuza kayıt ekler.
     * Dönem başı = aboneliğin baslangic_tarihi günü; aylık/yıllık faturalama_periyodu.
     * Koşul: baslangic_tarihi <= period_start < bitis_tarihi.
     *
     * @return int Eklenen kayıt sayısı
     */
    public function enqueueDuePeriods(Carbon $onDate): int
    {
        $day = $onDate->day;
        $year = $onDate->year;
        $month = $onDate->month;

        $subscriptions = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->whereNotNull('bitis_tarihi')
            ->whereNotNull('baslangic_tarihi')
            ->get();

        $added = 0;

        foreach ($subscriptions as $subscription) {
            // Aboneliğin faturalandırma günü (29, 30, 31 gibi yüksek günler için kısa aylarda ayın son günü kullanılır).
            $billingDay = $subscription->baslangic_tarihi->day;
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $effectiveBillingDay = min($billingDay, $daysInMonth);

            if ($effectiveBillingDay !== $day) {
                continue;
            }

            $periodStart = null;
            if ($subscription->faturalama_periyodu === Subscription::FATURALAMA_MONTHLY) {
                $periodStart = Carbon::createFromDate($year, $month, $effectiveBillingDay);
            } elseif ($subscription->faturalama_periyodu === Subscription::FATURALAMA_YEARLY) {
                if ($subscription->baslangic_tarihi->month !== $month) {
                    continue;
                }
                $periodStart = Carbon::createFromDate($year, $month, $effectiveBillingDay);
            } else {
                continue;
            }

            $baslangic = $subscription->baslangic_tarihi;
            $bitis = $subscription->bitis_tarihi;
            if ($periodStart->lt($baslangic) || $periodStart->gte($bitis)) {
                continue;
            }

            // Aynı abonelik için aynı yıl/ayda zaten bir sipariş varsa ikinciyi oluşturma.
            $exists = PendingBilling::where('subscription_id', $subscription->id)
                ->whereYear('period_start', $periodStart->year)
                ->whereMonth('period_start', $periodStart->month)
                ->exists();
            if ($exists) {
                continue;
            }

            $periodEnd = $this->computePeriodEnd($periodStart, $subscription->faturalama_periyodu);

            PendingBilling::create([
                'subscription_id' => $subscription->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => PendingBilling::STATUS_PENDING,
            ]);
            $added++;
        }

        return $added;
    }

    /**
     * Bugüne (veya verilen tarihe) kadar eksik kalan dönemleri tüm aktif abonelikler için ekler.
     * baslangic_tarihi ile upToDate arasında, henüz PendingBilling kaydı olmayan her dönem için kayıt oluşturur.
     * Koşul: baslangic_tarihi <= period_start < bitis_tarihi.
     *
     * @return int Eklenen kayıt sayısı
     */
    public function enqueueMissingPeriodsUpTo(Carbon $upToDate): int
    {
        $subscriptions = Subscription::query()
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->whereNotNull('bitis_tarihi')
            ->whereNotNull('baslangic_tarihi')
            ->get();

        $added = 0;

        foreach ($subscriptions as $subscription) {
            $baslangic = $subscription->baslangic_tarihi;
            $bitis = $subscription->bitis_tarihi;
            $billingDay = $baslangic->day;
            $cursor = $baslangic->copy()->startOfDay();

            while ($cursor->lte($upToDate)) {
                if ($cursor->lt($baslangic) || $cursor->gte($bitis)) {
                    $this->advancePeriodCursor($cursor, $subscription->faturalama_periyodu, $billingDay);
                    continue;
                }

                // Aynı abonelik ve yıl/ay için zaten bir sipariş varsa ikinciyi oluşturma.
                $exists = PendingBilling::where('subscription_id', $subscription->id)
                    ->whereYear('period_start', $cursor->year)
                    ->whereMonth('period_start', $cursor->month)
                    ->exists();
                if ($exists) {
                    $this->advancePeriodCursor($cursor, $subscription->faturalama_periyodu, $billingDay);
                    continue;
                }

                $periodEnd = $this->computePeriodEnd($cursor->copy(), $subscription->faturalama_periyodu);
                PendingBilling::create([
                    'subscription_id' => $subscription->id,
                    'period_start' => $cursor->copy(),
                    'period_end' => $periodEnd,
                    'status' => PendingBilling::STATUS_PENDING,
                ]);
                $added++;

                $this->advancePeriodCursor($cursor, $subscription->faturalama_periyodu, $billingDay);
            }
        }

        return $added;
    }

    /**
     * Belirtilen cariye ait aktif abonelikler için, verilen tarihe kadar eksik dönem siparişlerini oluşturur.
     * Abone Takip sayfasında "Bu ay için siparişleri oluştur" aksiyonu bu metodu kullanır.
     *
     * @return int Eklenen kayıt sayısı
     */
    public function enqueueMissingPeriodsUpToForCustomer(Carbon $upToDate, int $customerCariId): int
    {
        $subscriptions = Subscription::query()
            ->where('customer_cari_id', $customerCariId)
            ->where('durum', Subscription::DURUM_ACTIVE)
            ->whereNotNull('bitis_tarihi')
            ->whereNotNull('baslangic_tarihi')
            ->get();

        $added = 0;

        foreach ($subscriptions as $subscription) {
            $baslangic = $subscription->baslangic_tarihi;
            $bitis = $subscription->bitis_tarihi;
            $billingDay = $baslangic->day;
            $cursor = $baslangic->copy()->startOfDay();

            while ($cursor->lte($upToDate)) {
                if ($cursor->lt($baslangic) || $cursor->gte($bitis)) {
                    $this->advancePeriodCursor($cursor, $subscription->faturalama_periyodu, $billingDay);
                    continue;
                }

                $exists = PendingBilling::where('subscription_id', $subscription->id)
                    ->whereYear('period_start', $cursor->year)
                    ->whereMonth('period_start', $cursor->month)
                    ->exists();
                if ($exists) {
                    $this->advancePeriodCursor($cursor, $subscription->faturalama_periyodu, $billingDay);
                    continue;
                }

                $periodEnd = $this->computePeriodEnd($cursor->copy(), $subscription->faturalama_periyodu);
                PendingBilling::create([
                    'subscription_id' => $subscription->id,
                    'period_start' => $cursor->copy(),
                    'period_end' => $periodEnd,
                    'status' => PendingBilling::STATUS_PENDING,
                ]);
                $added++;

                $this->advancePeriodCursor($cursor, $subscription->faturalama_periyodu, $billingDay);
            }
        }

        return $added;
    }

    /**
     * Dönem cursor'ını bir periyot ileri taşır.
     *
     * Aylıkta: bir ay ileri, gün = min(abonelik başlangıç günü, yeni ayın son günü).
     * Yıllıkta: bir yıl ileri, gün = min(abonelik başlangıç günü, yeni yıl/ayın son günü).
     */
    private function advancePeriodCursor(Carbon $cursor, string $faturalamaPeriyodu, int $billingDay): void
    {
        if ($faturalamaPeriyodu === Subscription::FATURALAMA_MONTHLY) {
            // Bir sonraki ayın 1. gününe git, sonra faturalandırma gününü uygula
            $year = $cursor->year;
            $month = $cursor->month;

            if ($month === 12) {
                $year++;
                $month = 1;
            } else {
                $month++;
            }

            $cursor->setDate($year, $month, 1);
        } else {
            // Yıllıkta: bir sonraki yılın aynı ayının 1. gününe git
            $year = $cursor->year + 1;
            $month = $cursor->month;
            $cursor->setDate($year, $month, 1);
        }

        $daysInMonth = $cursor->daysInMonth;
        $cursor->day = min($billingDay, $daysInMonth);
    }

    /**
     * Dönem başlangıcına göre dönem bitiş tarihi (son gün).
     */
    public function computePeriodEnd(Carbon $periodStart, string $faturalamaPeriyodu): Carbon
    {
        $copy = $periodStart->copy();
        if ($faturalamaPeriyodu === Subscription::FATURALAMA_MONTHLY) {
            $copy->addMonth()->subDay();
        } else {
            $copy->addYear()->subDay();
        }

        return $copy;
    }
}
