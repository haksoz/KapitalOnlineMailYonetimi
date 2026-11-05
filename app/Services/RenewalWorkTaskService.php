<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\WorkTask;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RenewalWorkTaskService
{
    public function detect(?CarbonInterface $asOf = null): int
    {
        $today = Carbon::parse($asOf ?? now())->timezone('Europe/Istanbul')->startOfDay();
        $created = 0;
        Subscription::query()->where('durum', Subscription::DURUM_ACTIVE)
            ->whereDate('bitis_tarihi', '>=', $today->toDateString())
            ->whereDate('bitis_tarihi', '<=', $today->copy()->addDays(15)->toDateString())
            ->where(function ($query) {
                $query->whereIn('taahhut_tipi', [Subscription::TAAHHUT_MONTHLY_COMMITMENT, Subscription::TAAHHUT_ANNUAL_COMMITMENT])
                    ->orWhere(function ($query) {
                        $query->where('taahhut_tipi', Subscription::TAAHHUT_MONTHLY_NO_COMMITMENT)->where('auto_renew', false);
                    });
            })->with('product')->chunkById(100, function ($subscriptions) use (&$created, $today) {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, &$created, $today) {
                        $snapshot = [
                            'subscription_id' => $subscription->id,
                            'product_id' => $subscription->product_id,
                            'product_name' => $subscription->product?->name ?? 'Ürün',
                            'end_date' => $subscription->bitis_tarihi->toDateString(),
                            'quantity' => (int) $subscription->quantity,
                            'commitment' => $subscription->taahhut_tipi,
                            'billing_period' => $subscription->faturalama_periyodu,
                            'auto_renew' => $subscription->auto_renew,
                            'currency' => $subscription->currency ?? 'USD',
                            'cost' => $subscription->usd_birim_alis,
                            'sale' => $subscription->usd_birim_satis,
                        ];
                        $task = WorkTask::firstOrCreate([
                            'occurrence_key' => 'subscription-renewal:'.$subscription->id.':'.$snapshot['end_date'],
                        ], [
                            'type' => WorkTask::TYPE_RENEWAL,
                            'subject_type' => $subscription->getMorphClass(), 'subject_id' => $subscription->id,
                            'customer_cari_id' => $subscription->customer_cari_id,
                            'title' => $subscription->auto_renew ? 'Yeni dönem fiyatlarını kontrol et ve bildir' : 'Abonelik bitiyor. Yenileme teklifi hazırla.',
                            'status' => 'cost_pending', 'due_date' => $snapshot['end_date'],
                            'next_follow_up' => $today->toDateString(), 'snapshot' => $snapshot,
                        ]);
                        if ($task->wasRecentlyCreated) {
                            $task->record('Abonelik bitişine 15 gün veya daha az kaldığı için iş oluşturuldu.');
                            $created++;
                        }
                    });
                }
            });

        return $created;
    }
}
