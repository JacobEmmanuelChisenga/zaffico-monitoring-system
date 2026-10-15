<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register scheduled tasks (Laravel 11+ uses routes/console.php)
// These are also defined in app/Console/Kernel.php for schedule:work compatibility
Schedule::command('monitoring:run-checks')
    ->name('monitoring-checks')
    ->description('Execute monitoring checks for all active applications')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground()
    ->sendOutputTo(storage_path('logs/scheduler.log'))
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Monitoring checks scheduler task failed');
    })
    ->before(function () {
        \Illuminate\Support\Facades\Log::info('⏰ Scheduler triggered - running monitoring checks');
    });

Schedule::command('monitoring:generate-reports --type=daily')
    ->name('generate-daily-reports')
    ->description('Generate/update daily uptime reports for all applications')
    ->hourly()
    ->sendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('monitoring:generate-reports --type=weekly')
    ->name('generate-weekly-reports')
    ->description('Generate weekly uptime reports for all applications')
    ->weeklyOn(0, '00:00')
    ->sendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('monitoring:generate-reports --type=monthly')
    ->name('generate-monthly-reports')
    ->description('Generate monthly uptime reports for all applications')
    ->monthlyOn(1, '00:00')
    ->sendOutputTo(storage_path('logs/scheduler.log'));
