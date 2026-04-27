<?php

declare(strict_types=1);

use App\Jobs\DrainCaseFundJob;
use App\Models\GameCase;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('skins:catalog-sync')->dailyAt('04:15');
Schedule::command('skins:cases-import')->weeklyOn(1, '04:30');
Schedule::command('skins:prices-sync')->dailyAt('03:00');
Schedule::command('deals:settle-due')->everyTenMinutes()->withoutOverlapping();

Schedule::call(function (): void {
    GameCase::query()->active()->each(
        static fn (GameCase $case): mixed => DrainCaseFundJob::dispatch($case),
    );
})->dailyAt('00:00')->name('drain-case-funds')->withoutOverlapping();
