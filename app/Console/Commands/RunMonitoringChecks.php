<?php

namespace App\Console\Commands;

use App\Models\Applications;
use App\Services\HealthCheckService;
use App\Services\AlertRuleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunMonitoringChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:run-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute monitoring checks for all active applications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("⏰ Scheduler executed at " . now()->format('Y-m-d H:i:s'));
        Log::info("⏰ Scheduler executed at " . now()->format('Y-m-d H:i:s'));
        
        $healthCheckService = new HealthCheckService();
        $alertRuleService = new AlertRuleService();

        $applications = Applications::where('is_active', true)->get();
        
        if ($applications->isEmpty()) {
            $this->info("ℹ️  No active applications to check");
            Log::info("ℹ️  No active applications to check");
            return 0;
        }

        $this->info("🔄 Scheduler running - checking {$applications->count()} active application(s)");
        Log::info("🔄 Scheduler running - checking {$applications->count()} active application(s)");

        // Track network connectivity issues
        $networkFailureCount = 0;
        $totalChecks = $applications->count();
        
        foreach ($applications as $application) {
            try {
                // REAL-TIME MODE: Check all applications every run, regardless of check_interval
                // This ensures immediate detection when network goes down
                $this->info("🔄 Executing health check for application: {$application->name} ({$application->ip_address})");
                Log::info("🔄 Executing health check for application: {$application->name} ({$application->ip_address})");

                $check = $healthCheckService->executeAndRecord($application);

                // If check failed due to network error, increment counter
                if ($check->status === 'down' && $check->error_message && 
                    (str_contains(strtolower($check->error_message), 'network') ||
                     str_contains(strtolower($check->error_message), 'connection') ||
                     str_contains(strtolower($check->error_message), 'timeout') ||
                     str_contains(strtolower($check->error_message), 'unreachable'))) {
                    $networkFailureCount++;
                }

                // Normalize status to lowercase for consistency
                $normalizedStatus = strtolower($check->status ?? 'unknown');
                
                $application->update([
                    'status' => $normalizedStatus,
                    'response_time' => $check->response_time,
                    'last_checked_at' => now(),
                ]);

                $statusIcon = $check->status === 'up' ? '✅' : '❌';
                $message = "{$statusIcon} Health check completed for {$application->name}: {$check->status} (Response: {$check->response_time}ms)";
                $this->info($message);
                Log::info($message);

                // Always create/update incidents based on status
                $this->updateIncidents($application, $check);

                // Evaluate alert rules for notifications
                $alertRuleService->evaluateRules($application, $check);
                
                // Update uptime report for today after each check
                // This ensures reports are always current, not just hourly
                $this->updateUptimeReport($application);
            } catch (\Exception $e) {
                $errorMessage = "❌ Failed to execute health check for {$application->name}: {$e->getMessage()}";
                $this->error($errorMessage);
                Log::error($errorMessage);
                
                // If exception is network-related, mark as down and create incident
                if (str_contains(strtolower($e->getMessage()), 'network') ||
                    str_contains(strtolower($e->getMessage()), 'connection') ||
                    str_contains(strtolower($e->getMessage()), 'timeout')) {
                    $networkFailureCount++;
                    
                    // Create a monitoring check record for the failure
                    $check = \App\Models\MonitoringCheck::create([
                        'application_id' => $application->id,
                        'status' => 'down',
                        'response_time' => 0,
                        'status_code' => null,
                        'error_message' => 'Network error: ' . $e->getMessage(),
                        'response_body' => null,
                        'checked_at' => now(),
                    ]);
                    
                    // Mark application as down due to network error
                    $application->update([
                        'status' => 'down', // Already lowercase
                        'last_checked_at' => now(),
                    ]);
                    
                    // Create/update incidents for network failures
                    $this->updateIncidents($application, $check);
                    
                    // Update uptime report
                    $this->updateUptimeReport($application);
                }
            }
        }
        
        // If ALL applications failed with network errors, log a warning
        // This indicates the monitoring server itself may have network issues
        if ($networkFailureCount > 0 && $networkFailureCount === $totalChecks && $totalChecks > 0) {
            $warning = "⚠️  WARNING: All {$totalChecks} applications failed with network errors. The monitoring server may have lost network connectivity.";
            $this->warn($warning);
            Log::warning($warning);
        }

        return 0;
    }

    /**
     * Update incidents based on check status
     */
    private function updateIncidents($application, $check): void
    {
        $activeIncident = \App\Models\DowntimeIncident::where('application_id', $application->id)
            ->whereNull('resolved_at')
            ->first();

        if ($check->status === 'down' && !$activeIncident) {
            // Create incident when application goes down
            $incident = \App\Models\DowntimeIncident::create([
                'application_id' => $application->id,
                'started_at' => now(),
                'severity' => 'high',
                'error_message' => $check->error_message,
            ]);
            $this->info("🚨 Incident created for {$application->name}: #{$incident->id}");
            Log::info("🚨 Incident created for {$application->name}: #{$incident->id}");
            
            // Create notification for incident (even without alert rule)
            $notificationService = new \App\Services\NotificationService();
            $alertRule = \App\Models\AlertRule::where('application_id', $application->id)->where('is_active', true)->first();
            $notificationService->notifyIncident($incident, $alertRule);
        } elseif ($check->status === 'up' && $activeIncident) {
            // Resolve incident when application comes back up
            $activeIncident->update([
                'resolved_at' => now(),
                'duration' => (int) round($activeIncident->started_at->diffInMinutes(now())),
                'resolution_notes' => 'Application is back online',
            ]);
            $this->info("✅ Incident #{$activeIncident->id} resolved for {$application->name}");
            Log::info("✅ Incident #{$activeIncident->id} resolved for {$application->name}");
            
            // Create notification for resolution
            $notificationService = new \App\Services\NotificationService();
            $notificationService->notifyResolution($activeIncident);
        }
    }

    /**
     * Update uptime report for today after each check
     * This ensures reports are always current with the latest data
     */
    private function updateUptimeReport($application): void
    {
        try {
            $uptimeReportService = new \App\Services\UptimeReportService();
            // Update today's daily report after each check
            // This ensures uptime reports reflect the most current status
            $uptimeReportService->generateReport($application, 'daily', \Carbon\Carbon::now());
        } catch (\Exception $e) {
            // Log error but don't fail the monitoring check
            Log::warning("Failed to update uptime report for {$application->name}: {$e->getMessage()}");
        }
    }
}

