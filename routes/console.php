<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:process-renewals')->daily();
Schedule::command('pending-billings:enqueue')->daily();
Schedule::command('exchange-rates:fetch')->daily();
Schedule::command('pending-billings:refresh-amounts')->daily();
