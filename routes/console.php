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

// Process queued SMS/push-notification jobs (lesson evaluation, homework,
// attendance, etc.) in the background so HTTP requests that dispatch them
// (e.g. submitting a lesson evaluation) return immediately instead of
// blocking on SMS gateway calls. Runs a short worker burst every minute and
// exits once the queue is empty — safe to run without a persistent daemon.
// Requires the OS-level cron/Task Scheduler entry to call `schedule:run`
// every minute (see deployment notes).
Schedule::command('queue:work --queue=default --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
