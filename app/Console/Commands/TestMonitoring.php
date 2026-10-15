<?php

namespace App\Console\Commands;

use App\Models\Applications;
use App\Services\HealthCheckService;
use App\Services\AlertRuleService;
use Illuminate\Console\Command;

class TestMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:test {--app-id= : Test specific application by ID} {--all : Test all active applications}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Test monitoring checks on applications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $appId = $this->option('app-id');
        $all = $this->option('all');

        $query = Applications::where('is_active', true);

        if ($appId) {
            $query->where('id', $appId);
        }

        $applications = $query->get();

        if ($applications->isEmpty()) {
            $this->error('No applications found to test.');
            return 1;
        }

        $healthCheckService = new HealthCheckService();
        $alertRuleService = new AlertRuleService();

        foreach ($applications as $application) {
            $this->info("Testing application: {$application->name} ({$application->ip_address})");

            try {
                // Execute check
                $check = $healthCheckService->executeAndRecord($application);

                // Update application status
                $application->update([
                    'status' => $check->status,
                    'response_time' => $check->response_time,
                    'last_checked_at' => now(),
                ]);

                // Evaluate alert rules
                $alertRuleService->evaluateRules($application, $check);

                $statusEmoji = $check->status === 'up' ? '✓' : '✗';
                $this->line("  {$statusEmoji} Status: {$check->status}");
                $this->line("  ⏱ Response Time: {$check->response_time}ms");
                if ($check->status_code) {
                    $this->line("  • Status Code: {$check->status_code}");
                }
                if ($check->error_message) {
                    $this->line("  ✗ Error: {$check->error_message}");
                }
            } catch (\Exception $e) {
                $this->error("Failed to test {$application->name}: {$e->getMessage()}");
                return 1;
            }
        }

        $this->info('✓ Monitoring test completed successfully!');
        return 0;
    }
}
