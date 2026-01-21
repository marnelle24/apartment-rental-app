<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule notification checks to run daily at 8:00 AM
Schedule::command('notifications:check --quiet')
    ->dailyAt('07:08')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->runInBackground();
