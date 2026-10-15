<?php

namespace App\Services;

use App\Models\Applications;
use App\Models\UptimeReport;
use App\Models\DowntimeIncident;
use App\Models\MonitoringCheck;
use App\Models\MaintenanceWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UptimeReportService
{
    /**
     * Generate and save uptime report for an application
     */
    public function generateReport(Applications $application, string $periodType = 'daily', ?Carbon $reportDate = null): UptimeReport
    {
        $reportDate = $reportDate ?? Carbon::now();
        
        // Calculate period boundaries
        [$startDate, $endDate] = $this->getPeriodBoundaries($reportDate, $periodType);
        
        // Get monitoring checks for the period
        $checks = MonitoringCheck::where('application_id', $application->id)
            ->whereBetween('checked_at', [$startDate, $endDate])
            ->get();
        
        $totalChecks = $checks->count();
        $successfulChecks = $checks->where('status', 'up')->count();
        $failedChecks = $checks->where('status', 'down')->count();
        
        // Calculate downtime from incidents
        $incidents = DowntimeIncident::where('application_id', $application->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('started_at', [$startDate, $endDate])
                  ->orWhereBetween('resolved_at', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('started_at', '<', $startDate)
                         ->where(function ($q3) use ($endDate) {
                             $q3->whereNull('resolved_at')
                                ->orWhere('resolved_at', '>', $endDate);
                         });
                  });
            })->get();
        
        $totalDowntimeMinutes = 0;
        foreach ($incidents as $incident) {
            $start = max($incident->started_at, $startDate);
            $end = $incident->resolved_at ? min($incident->resolved_at, $endDate) : $endDate;
            $totalDowntimeMinutes += (int) round($start->diffInMinutes($end));
        }
        
        // Exclude maintenance windows
        $maintenanceWindows = MaintenanceWindow::where('application_id', $application->id)
            ->where('disable_alerts', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_time', [$startDate, $endDate])
                  ->orWhereBetween('end_time', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_time', '<', $startDate)
                         ->where('end_time', '>', $endDate);
                  });
            })->get();
        
        foreach ($maintenanceWindows as $mw) {
            $start = max($mw->start_time, $startDate);
            $end = min($mw->end_time, $endDate);
            $totalDowntimeMinutes -= (int) round($start->diffInMinutes($end));
        }
        
        $totalDowntimeMinutes = max(0, (int) round($totalDowntimeMinutes));
        
        // Calculate uptime percentage
        $totalMinutes = $startDate->diffInMinutes($endDate);
        $uptimeMinutes = max(0, $totalMinutes - $totalDowntimeMinutes);
        $uptimePercentage = $totalMinutes > 0 ? round(($uptimeMinutes / $totalMinutes) * 100, 2) : 100;
        
        // Calculate response time statistics
        $responseTimes = $checks->whereNotNull('response_time')->pluck('response_time')->filter();
        $avgResponseTime = $responseTimes->isNotEmpty() ? (int) round($responseTimes->avg()) : null;
        $minResponseTime = $responseTimes->isNotEmpty() ? (int) $responseTimes->min() : null;
        $maxResponseTime = $responseTimes->isNotEmpty() ? (int) $responseTimes->max() : null;
        
        // Ensure all integer fields are properly cast
        $totalDowntimeMinutes = (int) round($totalDowntimeMinutes);
        
        // Create or update report
        $report = UptimeReport::updateOrCreate(
            [
                'application_id' => $application->id,
                'report_date' => $reportDate->toDateString(),
                'period_type' => $periodType,
            ],
            [
                'uptime_percentage' => $uptimePercentage,
                'total_checks' => (int) $totalChecks,
                'successful_checks' => (int) $successfulChecks,
                'failed_checks' => (int) $failedChecks,
                'total_downtime_minutes' => $totalDowntimeMinutes,
                'incident_count' => (int) $incidents->count(),
                'avg_response_time' => $avgResponseTime,
                'min_response_time' => $minResponseTime,
                'max_response_time' => $maxResponseTime,
            ]
        );
        
        Log::info("Uptime report generated for {$application->name} ({$periodType}): {$uptimePercentage}% uptime");
        
        return $report;
    }
    
    /**
     * Generate reports for all active applications
     */
    public function generateReportsForAllApplications(string $periodType = 'daily', ?Carbon $reportDate = null): void
    {
        $applications = Applications::where('is_active', true)->get();
        
        foreach ($applications as $application) {
            try {
                $this->generateReport($application, $periodType, $reportDate);
            } catch (\Exception $e) {
                Log::error("Failed to generate report for {$application->name}: {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Get period boundaries based on type
     */
    private function getPeriodBoundaries(Carbon $date, string $periodType): array
    {
        return match ($periodType) {
            'daily' => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
            'weekly' => [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
            default => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
        };
    }
}

