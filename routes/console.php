<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('communications:pull-gmail')->everyMinute()->withoutOverlapping();
<<<<<<< HEAD

// Invoicing automation
Schedule::command('invoices:run-recurring')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('invoices:send-reminders')->dailyAt('09:00')->withoutOverlapping();
=======
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
