<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// End-of-day attendance automation: auto-marks absent and sends notifications
// Runs every 5 minutes. Cron must be configured on the live server.
Schedule::command('app:process-end-of-day-attendance')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
