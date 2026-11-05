<?php

namespace App\Console\Commands;

use App\Services\RenewalWorkTaskService;
use Illuminate\Console\Command;

class DetectRenewalWorkTasks extends Command
{
    protected $signature = 'work-tasks:detect-renewals';

    protected $description = 'Yaklaşan abonelik dönemleri için mükerrer olmayan yenileme işleri oluşturur';

    public function handle(RenewalWorkTaskService $service): int
    {
        $this->info($service->detect().' yenileme işi oluşturuldu.');

        return self::SUCCESS;
    }
}
