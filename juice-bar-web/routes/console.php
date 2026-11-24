<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup (needs the cPanel cron: * * * * * php artisan schedule:run).
Schedule::command('roshbar:backup')->dailyAt('23:45')->timezone(config('pos.timezone'));
