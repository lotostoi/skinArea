<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('skins:catalog-sync')->dailyAt('04:15');
Schedule::command('deals:settle-due')->everyTenMinutes()->withoutOverlapping();
