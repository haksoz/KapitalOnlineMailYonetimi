<?php

namespace App\Console\Commands;

use App\Services\PendingBillingService;
use Illuminate\Console\Command;

class RefreshPendingBillingAmountsCommand extends Command
{
    protected $signature = 'pending-billings:refresh-amounts';

    protected $description = 'Beklemedeki ödeme bekleyen kayıtların beklenen alış/satış TL tutarlarını güncel kur ile günceller (günlük çalıştırılır).';

    public function handle(PendingBillingService $pendingBillingService): int
    {
        $updated = $pendingBillingService->refreshAllPendingAmounts();

        $this->info("Ödeme bekleyenlerde {$updated} kaydın tutarları güncel kur ile güncellendi.");

        return self::SUCCESS;
    }
}
