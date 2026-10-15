<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ExecuteMonitoringChecks;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run monitoring checks every minute for real-time detection
        // All applications are checked every run (check_interval is ignored for real-time monitoring)
        // This ensures network failures are detected within 1 minute
        $schedule->command('monitoring:run-checks')
            ->name('monitoring-checks')
            ->description('Execute monitoring checks for all active applications (real-time mode)')
            ->everyMinute()
            ->withoutOverlapping(2) // Allow 2 minutes max execution time before releasing lock
            ->runInBackground() // Run in background to prevent blocking
            ->sendOutputTo(storage_path('logs/scheduler.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Monitoring checks scheduler task failed');
            })
            ->before(function () {
                \Illuminate\Support\Facades\Log::info('⏰ Scheduler triggered - running monitoring checks (real-time mode)');
            });

        // Generate daily uptime reports every hour to keep them current
        // This ensures reports are always up-to-date throughout the day
        $schedule->command('monitoring:generate-reports --type=daily')
            ->name('generate-daily-reports')
            ->description('Generate/update daily uptime reports for all applications')
            ->hourly()
            ->sendOutputTo(storage_path('logs/scheduler.log'));

        // Generate weekly uptime reports on Sundays at midnight
        $schedule->command('monitoring:generate-reports --type=weekly')
            ->name('generate-weekly-reports')
            ->description('Generate weekly uptime reports for all applications')
            ->weeklyOn(0, '00:00')
            ->sendOutputTo(storage_path('logs/scheduler.log'));

        // Generate monthly uptime reports on the 1st of each month at midnight
        $schedule->command('monitoring:generate-reports --type=monthly')
            ->name('generate-monthly-reports')
            ->description('Generate monthly uptime reports for all applications')
            ->monthlyOn(1, '00:00')
            ->sendOutputTo(storage_path('logs/scheduler.log'));

        // Clean up old monitoring data daily to prevent database bloat
        // Runs at 2 AM to avoid peak usage times
        if (config('monitoring.cleanup.auto_cleanup_enabled', true)) {
            $schedule->command('monitoring:cleanup --force')
                ->name('cleanup-monitoring-data')
                ->description('Clean up old monitoring data to prevent database bloat')
                ->dailyAt('02:00')
                ->sendOutputTo(storage_path('logs/cleanup.log'))
                ->onFailure(function () {
                    \Illuminate\Support\Facades\Log::error('Monitoring data cleanup task failed');
                });
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
