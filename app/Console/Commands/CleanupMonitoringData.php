<?php

namespace App\Console\Commands;

use App\Models\MonitoringCheck;
use App\Models\DowntimeIncident;
use App\Models\UptimeReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupMonitoringData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:cleanup 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old monitoring data to prevent database bloat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
            $this->newLine();
        }

        $retentionDays = config('monitoring.retention.monitoring_checks_days', 30);
        $incidentsDays = config('monitoring.retention.resolved_incidents_days', 90);
        $reportsDays = config('monitoring.retention.uptime_reports_days', 365);
        $batchSize = config('monitoring.cleanup.batch_size', 1000);

        $this->info("📊 Data Retention Policy:");
        $this->line("   - Monitoring Checks: {$retentionDays} days");
        $this->line("   - Resolved Incidents: {$incidentsDays} days");
        $this->line("   - Uptime Reports: {$reportsDays} days");
        $this->newLine();

        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $incidentsCutoff = Carbon::now()->subDays($incidentsDays);
        $reportsCutoff = Carbon::now()->subDays($reportsDays);

        // 1. Clean up old monitoring checks
        $this->info("🧹 Cleaning up old monitoring checks (older than {$retentionDays} days)...");
        $checksCount = $this->cleanupMonitoringChecks($cutoffDate, $batchSize, $dryRun);
        $this->info("   ✅ Deleted {$checksCount} old monitoring check records");
        $this->newLine();

        // 2. Clean up old resolved incidents
        $this->info("🧹 Cleaning up old resolved incidents (older than {$incidentsDays} days)...");
        $incidentsCount = $this->cleanupResolvedIncidents($incidentsCutoff, $batchSize, $dryRun);
        $this->info("   ✅ Deleted {$incidentsCount} old resolved incident records");
        $this->newLine();

        // 3. Clean up old uptime reports
        $this->info("🧹 Cleaning up old uptime reports (older than {$reportsDays} days)...");
        $reportsCount = $this->cleanupUptimeReports($reportsCutoff, $batchSize, $dryRun);
        $this->info("   ✅ Deleted {$reportsCount} old uptime report records");
        $this->newLine();

        // 4. Show database statistics
        $this->showDatabaseStats();

        $this->info("✅ Cleanup completed successfully!");
        
        return 0;
    }

    /**
     * Clean up old monitoring checks
     */
    private function cleanupMonitoringChecks(Carbon $cutoffDate, int $batchSize, bool $dryRun): int
    {
        $query = MonitoringCheck::where('checked_at', '<', $cutoffDate);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->line("   ℹ️  No old monitoring checks to delete");
            return 0;
        }

        $this->line("   Found {$totalCount} records to delete");

        if ($dryRun) {
            return $totalCount;
        }

        $deleted = 0;
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        // Delete in batches to avoid long-running queries
        do {
            $deletedBatch = MonitoringCheck::where('checked_at', '<', $cutoffDate)
                ->limit($batchSize)
                ->delete();
            
            $deleted += $deletedBatch;
            $progressBar->advance($deletedBatch);

            // Small delay to prevent database lock
            if ($deletedBatch > 0) {
                usleep(100000); // 0.1 second
            }
        } while ($deletedBatch > 0);

        $progressBar->finish();
        $this->newLine();

        Log::info("Cleaned up {$deleted} old monitoring checks (older than {$cutoffDate->toDateString()})");

        return $deleted;
    }

    /**
     * Clean up old resolved incidents
     */
    private function cleanupResolvedIncidents(Carbon $cutoffDate, int $batchSize, bool $dryRun): int
    {
        $query = DowntimeIncident::whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoffDate);
        
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->line("   ℹ️  No old resolved incidents to delete");
            return 0;
        }

        $this->line("   Found {$totalCount} records to delete");

        if ($dryRun) {
            return $totalCount;
        }

        $deleted = 0;
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        do {
            $deletedBatch = DowntimeIncident::whereNotNull('resolved_at')
                ->where('resolved_at', '<', $cutoffDate)
                ->limit($batchSize)
                ->delete();
            
            $deleted += $deletedBatch;
            $progressBar->advance($deletedBatch);

            if ($deletedBatch > 0) {
                usleep(100000);
            }
        } while ($deletedBatch > 0);

        $progressBar->finish();
        $this->newLine();

        Log::info("Cleaned up {$deleted} old resolved incidents (older than {$cutoffDate->toDateString()})");

        return $deleted;
    }

    /**
     * Clean up old uptime reports
     */
    private function cleanupUptimeReports(Carbon $cutoffDate, int $batchSize, bool $dryRun): int
    {
        $query = UptimeReport::where('report_date', '<', $cutoffDate);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->line("   ℹ️  No old uptime reports to delete");
            return 0;
        }

        $this->line("   Found {$totalCount} records to delete");

        if ($dryRun) {
            return $totalCount;
        }

        $deleted = 0;
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        do {
            $deletedBatch = UptimeReport::where('report_date', '<', $cutoffDate)
                ->limit($batchSize)
                ->delete();
            
            $deleted += $deletedBatch;
            $progressBar->advance($deletedBatch);

            if ($deletedBatch > 0) {
                usleep(100000);
            }
        } while ($deletedBatch > 0);

        $progressBar->finish();
        $this->newLine();

        Log::info("Cleaned up {$deleted} old uptime reports (older than {$cutoffDate->toDateString()})");

        return $deleted;
    }

    /**
     * Show database statistics
     */
    private function showDatabaseStats(): void
    {
        $this->info("📈 Current Database Statistics:");
        
        $totalChecks = MonitoringCheck::count();
        $totalIncidents = DowntimeIncident::count();
        $activeIncidents = DowntimeIncident::whereNull('resolved_at')->count();
        $totalReports = UptimeReport::count();

        $oldestCheck = MonitoringCheck::orderBy('checked_at', 'asc')->first();
        $newestCheck = MonitoringCheck::orderBy('checked_at', 'desc')->first();

        $this->line("   - Total Monitoring Checks: " . number_format($totalChecks));
        if ($oldestCheck && $newestCheck) {
            $this->line("     Range: {$oldestCheck->checked_at->format('Y-m-d')} to {$newestCheck->checked_at->format('Y-m-d')}");
        }
        
        $this->line("   - Total Incidents: " . number_format($totalIncidents) . " ({$activeIncidents} active)");
        $this->line("   - Total Uptime Reports: " . number_format($totalReports));
        
        // Estimate database size (PostgreSQL)
        try {
            $dbName = config('database.connections.pgsql.database');
            $sizeQuery = DB::selectOne("
                SELECT pg_size_pretty(pg_database_size('{$dbName}')) as size
            ");
            if ($sizeQuery) {
                $this->line("   - Database Size: {$sizeQuery->size}");
            }
        } catch (\Exception $e) {
            // Ignore if not PostgreSQL or query fails
        }
        
        $this->newLine();
    }
}

