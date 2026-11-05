<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('work-tasks:detect-renewals')->dailyAt('00:01')->timezone('Europe/Istanbul')->withoutOverlapping()->appendOutputTo(storage_path('logs/work-tasks.log'));
Schedule::command('subscriptions:process-renewals')->dailyAt('00:10')->timezone('Europe/Istanbul')->withoutOverlapping()->sendOutputTo(storage_path('logs/subscriptions-process-renewals.log'))->emailOutputOnFailure('haksoz@kapital-online.net');
Schedule::command('pending-billings:enqueue')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/pending-billings-enqueue.log'))->emailOutputOnFailure('haksoz@kapital-online.net');
Schedule::command('exchange-rates:fetch')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/exchange-rates-fetch.log'))->emailOutputOnFailure('haksoz@kapital-online.net');
Schedule::command('pending-billings:refresh-amounts')->daily()->withoutOverlapping()->sendOutputTo(storage_path('logs/pending-billings-refresh-amounts.log'))->emailOutputOnFailure('haksoz@kapital-online.net');
Schedule::command('notifications:dispatch')->everyFiveMinutes()->weekdays()->timezone('Europe/Istanbul')->withoutOverlapping()->sendOutputTo(storage_path('logs/notifications-dispatch.log'))->emailOutputOnFailure('haksoz@kapital-online.net');

