<?php

use App\Jobs\AutoCloseTicketsJob;
use App\Jobs\SlaBreachCheckJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SlaBreachCheckJob)->everyFifteenMinutes();
Schedule::job(new AutoCloseTicketsJob)->dailyAt('00:00');
