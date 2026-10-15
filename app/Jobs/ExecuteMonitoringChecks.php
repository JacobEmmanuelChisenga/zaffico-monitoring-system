<?php

namespace App\Jobs;

use App\Models\Applications;
use App\Services\HealthCheckService;
use App\Services\AlertRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExecuteMonitoringChecks implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;
    public $backoff = [10, 60]; // Retry after 10s then 60s

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?int $applicationId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $healthCheckService = new HealthCheckService();
        $alertRuleService = new AlertRuleService();

        // Get applications to check
        $query = Applications::where('is_active', true);

        if ($this->applicationId) {
            $query->where('id', $this->applicationId);
        }

        $applications = $query->get();

        if ($applications->isEmpty()) {
            Log::info('No active applications to check');
            return;
        }

        foreach ($applications as $application) {
            try {
                Log::info("Executing health check for application: {$application->name}");

                // Execute the health check
                $check = $healthCheckService->executeAndRecord($application);

                // Normalize status to lowercase for consistency
                $normalizedStatus = strtolower($check->status ?? 'unknown');
                
                // Update application status
                $application->update([
                    'status' => $normalizedStatus,
                    'response_time' => $check->response_time,
                    'last_checked_at' => now(),
                ]);

                Log::info("Health check completed for {$application->name}: {$check->status}");

                // Evaluate alert rules
                $alertRuleService->evaluateRules($application, $check);
            } catch (\Exception $e) {
                Log::error("Failed to execute health check for application {$application->id}: {$e->getMessage()}");
                $this->fail($e);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ExecuteMonitoringChecks job failed: {$exception->getMessage()}");
    }
}
