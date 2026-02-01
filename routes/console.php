<?php

use App\Jobs\SendScheduledNotificationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule notification job to run daily at 7:08 AM (Asia/Manila)
Schedule::job(new SendScheduledNotificationsJob)
    ->dailyAt('18:39')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

// Keep command for manual/CLI runs: php artisan notifications:check
