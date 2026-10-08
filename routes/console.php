<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Boletines programados: requiere el cron de Laravel (* * * * * php artisan schedule:run).
Schedule::command('campaigns:dispatch-due')->everyMinute()->withoutOverlapping();
Schedule::command('leads:rescore --open')->dailyAt('03:30')->withoutOverlapping();
