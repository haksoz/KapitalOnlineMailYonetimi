<?php

namespace App\Console\Commands;

use App\Services\PendingBillingService;
use Illuminate\Console\Command;

class EnqueuePendingBillingsCommand extends Command
{
    protected $signature = 'pending-billings:enqueue
                            {--date= : İşlemin baz alınacağı tarih (Y-m-d). Varsayılan: bugün}';

    protected $description = 'Bugün dönem başı gelen abonelikler için ödeme bekleyenlere kayıt ekler.';

    public function handle(PendingBillingService $pendingBillingService): int
    {
        $dateStr = $this->option('date');
        $onDate = $dateStr ? \Carbon\Carbon::parse($dateStr) : \Carbon\Carbon::today();

        $added = $pendingBillingService->enqueueDuePeriods($onDate);

        $this->info("Ödeme bekleyenlere {$added} kayıt eklendi.");

        return self::SUCCESS;
    }
}
