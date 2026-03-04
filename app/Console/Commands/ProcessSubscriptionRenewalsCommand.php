<?php

namespace App\Console\Commands;

use App\Models\SubscriptionRenewalLog;
use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewalsCommand extends Command
{
    protected $signature = 'subscriptions:process-renewals
                            {--date= : İşlemin baz alınacağı tarih (Y-m-d). Varsayılan: bugün}';

    protected $description = 'Dönem bitişi geçmiş, otomatik yenileme açık aboneliklerin bitiş tarihini bir periyot ileri alır.';

    public function handle(SubscriptionRenewalService $renewalService): int
    {
        $dateStr = $this->option('date');
        $asOf = $dateStr ? \Carbon\Carbon::parse($dateStr) : null;

        Log::info('subscriptions:process-renewals başlatıldı', [
            'as_of_date' => $asOf?->toDateString(),
        ]);

        $renewed = $renewalService->processRenewals($asOf);

        $count = count($renewed);

        SubscriptionRenewalLog::create([
            'run_at' => now(),
            'as_of_date' => $asOf?->toDateString(),
            'renewed_count' => $count,
            'renewed_ids' => $renewed,
        ]);

        Log::info('subscriptions:process-renewals tamamlandı', [
            'renewed_count' => $count,
            'renewed_ids' => $renewed,
        ]);

        if ($count > 0) {
            $this->info("{$count} abonelik yenilendi (ID: " . implode(', ', $renewed) . ').');
        } else {
            $this->info('Yenilenecek abonelik yok.');
        }

        return self::SUCCESS;
    }
}
