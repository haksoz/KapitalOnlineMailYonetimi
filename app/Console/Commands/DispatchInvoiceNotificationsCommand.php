<?php

namespace App\Console\Commands;

use App\Services\InvoiceNotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchInvoiceNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch
                            {--date= : İşlemin baz alınacağı tarih (Y-m-d). Varsayılan: bugün}';

    protected $description = 'Vade öncesi hatırlatma ve vade sonrası gecikme bildirimlerini gönderir.';

    public function handle(InvoiceNotificationDispatcher $dispatcher): int
    {
        $dateStr = $this->option('date');
        $onDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();

        $sent = $dispatcher->dispatch($onDate);

        $this->info("{$sent} bildiri e-postası gönderildi.");

        return self::SUCCESS;
    }
}
