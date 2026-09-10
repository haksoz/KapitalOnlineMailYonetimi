<?php

namespace App\Console\Commands;

use App\Services\InvoiceNotificationDispatcher;
use App\Models\NotificationDefinition;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchInvoiceNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch
                            {--date= : İşlemin baz alınacağı tarih (Y-m-d). Varsayılan: bugün}';

    protected $description = 'Vade öncesi hatırlatma, vade sonrası gecikme ve faiz/kapatma bildirimlerini gönderir.';

    public function handle(InvoiceNotificationDispatcher $dispatcher): int
    {
        $dateStr = $this->option('date');
        if ($dateStr) {
            $now = Carbon::parse($dateStr, NotificationDefinition::TIMEZONE)->endOfDay();
            $sent = $dispatcher->dispatch($now, respectSendAt: false);
        } else {
            $sent = $dispatcher->dispatch(now());
        }

        $this->info("{$sent} bildiri e-postası gönderildi.");

        return self::SUCCESS;
    }
}
