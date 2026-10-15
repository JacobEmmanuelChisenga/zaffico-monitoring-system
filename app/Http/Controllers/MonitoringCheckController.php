<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitoringCheck;
use App\Models\Applications;
use App\Services\HealthCheckService;
use App\Services\AlertRuleService;

class MonitoringCheckController extends Controller
{
    // List all monitoring checks
    public function index(Request $request)
    {
        $query = MonitoringCheck::with('application');
        
        // Filter by application if provided
        if ($request->has('application_id') && $request->application_id) {
            $query->where('application_id', $request->application_id);
        }
        
        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $checks = $query->latest('checked_at')->paginate(50);
        
        // Group checks by intervals for display
        $groupedChecks = $this->groupChecksByInterval($checks->items() ?? []);
        
        $applications = Applications::where('is_active', true)->get();
        
        return view('checks.index', compact('checks', 'groupedChecks', 'applications'));
    }
    
    /**
     * Group checks by time intervals based on application check_interval
     */
    private function groupChecksByInterval($checks)
    {
        $grouped = [];
        
        foreach ($checks as $check) {
            if (!$check->application || !$check->checked_at) {
                continue;
            }
            
            $interval = $check->application->check_interval ?? 5; // Default 5 minutes
            $checkedAt = $check->checked_at;
            
            // Round down to the nearest interval
            $minutes = $checkedAt->minute;
            $roundedMinutes = floor($minutes / $interval) * $interval;
            $intervalStart = $checkedAt->copy()->setTime($checkedAt->hour, $roundedMinutes, 0);
            
            $key = $intervalStart->format('Y-m-d H:i');
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'interval_start' => $intervalStart,
                    'interval' => $interval,
                    'checks' => []
                ];
            }
            
            $grouped[$key]['checks'][] = $check;
        }
        
        // Sort by interval start (newest first)
        krsort($grouped);
        
        return $grouped;
    }

    // Show form to trigger a manual check
    public function create()
    {
        $applications = Applications::where('is_active', true)->get();
        return view('checks.create', compact('applications'));
    }

    // Trigger a manual monitoring check
    public function store(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $application = Applications::findOrFail($request->application_id);

        try {
            $healthCheckService = new HealthCheckService();
            $alertRuleService = new AlertRuleService();

            // Execute the health check
            $check = $healthCheckService->executeAndRecord($application);

            // Update application status
            $application->update([
                'status' => $check->status,
                'response_time' => $check->response_time,
                'last_checked_at' => now(),
            ]);

            // Evaluate alert rules
            $alertRuleService->evaluateRules($application, $check);

            return redirect()->route('checks.show', $check->id)
                ->with('success', "Health check completed for {$application->name}. Status: {$check->status}");
        } catch (\Exception $e) {
            return redirect()->route('checks.index')
                ->with('error', "Failed to execute health check: {$e->getMessage()}");
        }
    }

    // Show single monitoring check details
    public function show(MonitoringCheck $check)
    {
        return view('checks.show', compact('check'));
    }

    // Show form to edit a monitoring check (read-only view)
    public function edit(MonitoringCheck $check)
    {
        return view('checks.edit', compact('check'));
    }

    // Monitoring checks are read-only
    public function update(Request $request, MonitoringCheck $check)
    {
        return redirect()->route('checks.index')
            ->with('info', 'Monitoring checks are read-only and managed by the system.');
    }

    // Delete a monitoring check
    public function destroy(MonitoringCheck $check)
    {
        $check->delete();
        return redirect()->route('checks.index')
            ->with('success', 'Monitoring check deleted successfully.');
    }
}

