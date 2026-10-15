<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Applications;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ApplicationsController extends Controller
{
    // List all applications
    public function index()
    {
        $applications = Applications::latest()->get()->map(function ($app) {
            // Fallback: if last_checked_at is null, use the latest monitoring check
            if (!$app->last_checked_at) {
                $latestCheck = $app->checks()->latest('checked_at')->first();
                if ($latestCheck) {
                    $app->last_checked_at = $latestCheck->checked_at;
                    $app->response_time = $latestCheck->response_time;
                    $app->status = strtolower($latestCheck->status ?? $app->status ?? 'unknown');
                }
            }
            return $app;
        });
        return view('applications.index', compact('applications'));
    }

    /**
     * API endpoint for real-time applications list updates
     */
    public function apiIndex()
    {
        // Prevent browser caching (especially Chrome)
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $applications = Applications::latest()->get()->map(function ($app) {
            // Fallback to last monitoring check when last_checked_at is missing
            if (!$app->last_checked_at) {
                $latestCheck = $app->checks()->latest('checked_at')->first();
                if ($latestCheck) {
                    $app->last_checked_at = $latestCheck->checked_at;
                    $app->response_time = $latestCheck->response_time;
                    $app->status = strtolower($latestCheck->status ?? $app->status ?? 'unknown');
                }
            }

            $isStale = !$app->last_checked_at || $app->last_checked_at->lt(now()->subMinutes(2));
            $isLikelyStale = $app->status === 'up' && 
                            $app->last_checked_at && 
                            $app->last_checked_at->lt(now()->subMinutes(5));
            
            return [
                'id' => $app->id,
                'name' => $app->name,
                'ip_address' => $app->ip_address,
                'protocol' => $app->protocol,
                'port' => $app->port,
                'endpoint' => $app->endpoint,
                'status' => $app->status,
                'response_time' => $app->response_time,
                'last_checked_at' => $app->last_checked_at ? $app->last_checked_at->toIso8601String() : null,
                'is_active' => $app->is_active,
                'is_stale' => $isStale || $isLikelyStale,
            ];
        });

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'applications' => $applications,
            ]
        ]);
    }

    // Show form to create a new application
    public function create()
    {
        return view('applications.create');
    }

    // Store new application
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string',
            'protocol' => 'required|in:http,https,tcp,ping',
            'port' => 'nullable|integer|min:1|max:65535',
            'endpoint' => 'nullable|string|max:255',
            'check_interval' => 'nullable|integer|min:1|max:1440',
            'timeout' => 'nullable|integer|min:1|max:300',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = true;
        $data['status'] = 'unknown'; // Status will be determined by monitoring
        $data['created_by'] = Auth::id();

        Applications::create($data);

        return redirect()->route('applications')->with('success', 'Application created successfully.');
    }

    // Show single application details
    public function show(Applications $application)
    {
        // Load relationships
        $application->load(['checks', 'incidents', 'alertRules']);
        
        // Get recent checks (last 100)
        $recentChecks = $application->checks()
            ->latest('checked_at')
            ->take(100)
            ->get();
        
        // Get trend data for graphs
        $trendData = $this->getTrendData($application);
        
        // Get statistics
        $stats = $this->getApplicationStats($application);
        
        return view('applications.show', compact('application', 'recentChecks', 'trendData', 'stats'));
    }

    /**
     * API endpoint for real-time single application updates
     */
    public function apiShow(Applications $application)
    {
        // Prevent browser caching (especially Chrome)
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Refresh application data
        $application->refresh();
        
        $isStale = !$application->last_checked_at || $application->last_checked_at->lt(now()->subMinutes(2));
        $isLikelyStale = $application->status === 'up' && 
                        $application->last_checked_at && 
                        $application->last_checked_at->lt(now()->subMinutes(5));

        // Get recent checks (last 10 for API)
        $recentChecks = $application->checks()
            ->latest('checked_at')
            ->take(10)
            ->get()
            ->map(function ($check) {
                return [
                    'id' => $check->id,
                    'status' => $check->status,
                    'response_time' => $check->response_time,
                    'status_code' => $check->status_code,
                    'error_message' => $check->error_message,
                    'checked_at' => $check->checked_at->toIso8601String(),
                ];
            });

        // Get statistics
        $stats = $this->getApplicationStats($application);
        $trendData = $this->getTrendData($application);

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $application->id,
                'name' => $application->name,
                'ip_address' => $application->ip_address,
                'protocol' => $application->protocol,
                'port' => $application->port,
                'endpoint' => $application->endpoint,
                'status' => $application->status,
                'response_time' => $application->response_time,
                'last_checked_at' => $application->last_checked_at ? $application->last_checked_at->toIso8601String() : null,
                'is_active' => $application->is_active,
                'is_stale' => $isStale || $isLikelyStale,
                'stats' => $stats,
                'recentChecks' => $recentChecks,
                'trendData' => $trendData,
            ]
        ]);
    }
    
    /**
     * Get trend data for application graphs
     */
    private function getTrendData(Applications $application)
    {
        $data = [
            'uptime_downtime' => $this->getUptimeDowntimeTrend($application),
            'response_time' => $this->getResponseTimeTrend($application),
            'status_over_time' => $this->getStatusOverTimeTrend($application),
        ];
        
        return $data;
    }
    
    /**
     * Get uptime/downtime trend for last 7 days
     */
    private function getUptimeDowntimeTrend(Applications $application)
    {
        $labels = [];
        $uptimeData = [];
        $downtimeData = [];
        
        $tz = config('app.timezone', 'UTC');

        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now($tz)->subDays($i);
            $labels[] = $date->format('M d');
            
            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();
            
            // Get incidents for this day
            $incidents = \App\Models\DowntimeIncident::where('application_id', $application->id)
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
            
            $downtimeMinutes = 0;
            if ($incidents->count() > 0) {
                foreach ($incidents as $incident) {
                    $start = max($incident->started_at, $startDate);
                    $end = $incident->resolved_at ? min($incident->resolved_at, $endDate) : $endDate;
                    $downtimeMinutes += $start->diffInMinutes($end);
                }
            } else {
                // Fallback to checks
                $checks = \App\Models\MonitoringCheck::where('application_id', $application->id)
                    ->whereBetween('checked_at', [$startDate, $endDate])
                    ->get();
                
                if ($checks->count() > 0) {
                    $downChecks = $checks->where('status', 'down')->count();
                    $downtimeMinutes = ($downChecks / $checks->count()) * 1440;
                }
            }
            
            $uptimePercentage = max(0, 100 - min($downtimeMinutes, 1440) / 1440 * 100);
            $uptimeData[] = round($uptimePercentage, 2);
            $downtimeData[] = round($downtimeMinutes / 60, 1);
        }
        
        return [
            'labels' => $labels,
            'uptime' => $uptimeData,
            'downtime' => $downtimeData,
        ];
    }
    
    /**
     * Get response time trend for last 24 hours
     */
    private function getResponseTimeTrend(Applications $application)
    {
        $labels = [];
        $responseTimes = [];

        // Use application timezone for display
        $tz = config('app.timezone', 'UTC');
        $now = \Carbon\Carbon::now($tz);

        // Get checks from last 24 hours, grouped by hour (in local timezone)
        for ($i = 23; $i >= 0; $i--) {
            $hourStart = $now->copy()->subHours($i)->startOfHour();
            $hourEnd = $now->copy()->subHours($i)->endOfHour();
            
            // Label with local time and timezone suffix for clarity
            $labels[] = $hourStart->format('H:i') . ' ' . $hourStart->format('T');
            
            $checks = \App\Models\MonitoringCheck::where('application_id', $application->id)
                ->whereBetween('checked_at', [$hourStart->copy()->timezone('UTC'), $hourEnd->copy()->timezone('UTC')])
                ->get();
            
            if ($checks->count() > 0) {
                $avgResponseTime = $checks->avg('response_time');
                $responseTimes[] = round($avgResponseTime, 0);
            } else {
                $responseTimes[] = null;
            }
        }
        
        return [
            'labels' => $labels,
            'response_times' => $responseTimes,
        ];
    }
    
    /**
     * Get status over time trend (last 24 hours)
     */
    private function getStatusOverTimeTrend(Applications $application)
    {
        $labels = [];
        $upCounts = [];
        $downCounts = [];
        
        // Use application timezone for display
        $tz = config('app.timezone', 'UTC');
        $now = \Carbon\Carbon::now($tz);
        
        // Get checks from last 24 hours, grouped by hour (local time labels, UTC query window)
        for ($i = 23; $i >= 0; $i--) {
            $hourStart = $now->copy()->subHours($i)->startOfHour();
            $hourEnd = $now->copy()->subHours($i)->endOfHour();
            
            $labels[] = $hourStart->format('H:i') . ' ' . $hourStart->format('T');
            
            $checks = \App\Models\MonitoringCheck::where('application_id', $application->id)
                ->whereBetween('checked_at', [$hourStart->copy()->timezone('UTC'), $hourEnd->copy()->timezone('UTC')])
                ->get();
            
            $upCounts[] = $checks->where('status', 'up')->count();
            $downCounts[] = $checks->where('status', 'down')->count();
        }
        
        return [
            'labels' => $labels,
            'up' => $upCounts,
            'down' => $downCounts,
        ];
    }
    
    /**
     * Get application statistics
     */
    private function getApplicationStats(Applications $application)
    {
        $totalChecks = $application->checks()->count();
        $upChecks = $application->checks()->where('status', 'up')->count();
        $downChecks = $application->checks()->where('status', 'down')->count();
        $uptimePercentage = $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : 0;
        
        $avgResponseTime = $application->checks()->avg('response_time') ?? 0;
        $minResponseTime = $application->checks()->min('response_time') ?? 0;
        $maxResponseTime = $application->checks()->max('response_time') ?? 0;
        
        $totalIncidents = $application->incidents()->count();
        $activeIncidents = $application->incidents()->whereNull('resolved_at')->count();
        
        return [
            'total_checks' => $totalChecks,
            'up_checks' => $upChecks,
            'down_checks' => $downChecks,
            'uptime_percentage' => $uptimePercentage,
            'avg_response_time' => round($avgResponseTime, 0),
            'min_response_time' => $minResponseTime,
            'max_response_time' => $maxResponseTime,
            'total_incidents' => $totalIncidents,
            'active_incidents' => $activeIncidents,
        ];
    }

    // Show form to edit an application
    public function edit(Applications $application)
    {
        return view('applications.edit', compact('application'));
    }

    // Update application
    public function update(Request $request, Applications $application)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string',
            'protocol' => 'required|in:http,https,tcp,ping',
            'port' => 'nullable|integer|min:1|max:65535',
            'endpoint' => 'nullable|string|max:255',
            'check_interval' => 'nullable|integer|min:1|max:1440',
            'timeout' => 'nullable|integer|min:1|max:300',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        // Don't update status through form - it's determined by monitoring
        unset($data['status']);

        $application->update($data);

        return redirect()->route('applications')->with('success', 'Application updated successfully.');
    }

    // Delete application
    public function destroy(Applications $application)
    {
        $application->delete();
        return redirect()->route('applications')->with('success', 'Application deleted successfully.');
    }
}
