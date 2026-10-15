<?php

namespace App\Services;

use App\Models\Applications;
use App\Models\MonitoringCheck;
use App\Models\DowntimeIncident;
use App\Models\AlertRule;
use Illuminate\Support\Facades\Log;

class AlertRuleService
{
    /**
     * Evaluate all alert rules for an application based on check result
     */
    public function evaluateRules(Applications $application, MonitoringCheck $check): void
    {
        $alertRules = AlertRule::where('application_id', $application->id)
            ->where('is_active', true)
            ->get();

        foreach ($alertRules as $rule) {
            $this->evaluateRule($application, $check, $rule);
        }
    }

    /**
     * Evaluate a single alert rule
     */
    private function evaluateRule(Applications $application, MonitoringCheck $check, AlertRule $rule): void
    {
        $triggered = false;

        // Evaluate condition based on rule type
        switch ($rule->condition_type) {
            case 'status_down':
                $triggered = $check->status === 'down';
                break;

            case 'response_time':
                $triggered = $check->response_time > $rule->condition_value;
                break;

            case 'status_code':
                $triggered = $check->status_code !== $rule->condition_value;
                break;

            case 'consecutive_failures':
                $triggered = $this->checkConsecutiveFailures($application, $rule->condition_value);
                break;
        }

        if ($triggered) {
            $this->handleTriggeredRule($application, $check, $rule);
        }
    }

    /**
     * Check if there are consecutive failures
     */
    private function checkConsecutiveFailures(Applications $application, int $threshold): bool
    {
        $failureCount = MonitoringCheck::where('application_id', $application->id)
            ->where('status', 'down')
            ->latest('checked_at')
            ->take($threshold)
            ->count();

        return $failureCount >= $threshold;
    }

    /**
     * Handle when an alert rule is triggered
     */
    private function handleTriggeredRule(Applications $application, MonitoringCheck $check, AlertRule $rule): void
    {
        // Check if there's already an active incident for this application
        $activeIncident = DowntimeIncident::where('application_id', $application->id)
            ->whereNull('resolved_at')
            ->first();

        // Only create incident if one doesn't exist and the check status is down
        if (!$activeIncident && $check->status === 'down') {
            $incident = DowntimeIncident::create([
                'application_id' => $application->id,
                'started_at' => now(),
                'severity' => $rule->severity ?? 'high',
                'error_message' => $check->error_message,
            ]);

            Log::info("Incident created for application {$application->id}: {$incident->id}");

            // Send notifications
            $notificationService = new NotificationService();
            $notificationService->notifyIncident($incident, $rule);
        }

        // Resolve incident if status is up and there's an active incident
        if ($check->status === 'up' && $activeIncident) {
            $activeIncident->update([
                'resolved_at' => now(),
                'duration' => (int) round($activeIncident->started_at->diffInMinutes(now())),
                'resolution_notes' => 'Application is back online',
            ]);

            Log::info("Incident {$activeIncident->id} resolved for application {$application->id}");

            // Send resolution notifications
            $notificationService = new NotificationService();
            $notificationService->notifyResolution($activeIncident);
        }
    }
}
