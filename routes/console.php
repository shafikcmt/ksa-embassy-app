<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduled Tasks ────────────────────────────────────────────────────────
// Cron entry: * * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1

Schedule::command('app:check-subscriptions')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('app:check-license-expiry')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('app:check-passport-expiry')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('app:backup-database')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
