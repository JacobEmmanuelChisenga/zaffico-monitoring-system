<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applications;
use App\Models\DowntimeIncident;
use App\Models\MonitoringCheck;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all applications
        $applications = Applications::all();

        $totalApplications = $applications->count();
        
        // Count apps by their actual status column
        $applicationsUp = $applications->where('status', 'up')->count();
        $applicationsDown = $totalApplications - $applicationsUp;
        
        // Ensure incidents exist for currently down applications
        $this->ensureIncidentsForDownApplications($applications);
        
        // Active incidents (unresolved)
        $activeIncidents = DowntimeIncident::whereNull('resolved_at')->count();
        
        // Total checks
        $totalChecks = MonitoringCheck::count();

        // Recent incidents with application names (include active ones first)
        $recentIncidents = DowntimeIncident::with('application')
            ->orderByRaw('resolved_at IS NULL DESC') // Active incidents first
            ->orderBy('started_at', 'desc')
            ->take(5)
            ->get();

        // Build chart data: Last 7 days uptime per application
        $chartData = $this->getUptimeTrendData($applications);

        // Calculate SLA & MTTR metrics
        $slaData = $this->calculateSLAMetrics($applications);
        
        // Calculate severity distribution
        $severityDistribution = $this->getSeverityDistribution();
        
        // Calculate system availability
        $systemAvailability = $this->calculateSystemAvailability($applications);
        
        // Get top problem applications
        $topProblems = $this->getTopProblemApplications();

        // Get current server time
        $serverTime = Carbon::now()->format('M d, Y H:i:s');
        $timezone = config('app.timezone');
        
        // Pass to view
        return view('dashboard', compact(
            'totalApplications',
            'applicationsUp',
            'applicationsDown',
            'activeIncidents',
            'totalChecks',
            'recentIncidents',
            'chartData',
            'applications',
            'slaData',
            'severityDistribution',
            'systemAvailability',
            'topProblems',
            'serverTime',
            'timezone'
        ));
    }

    /**
     * Calculate SLA compliance and MTTR
     */
    private function calculateSLAMetrics($applications)
    {
        $targetSLA = 99.9; // Target SLA percentage
        
        // Calculate current uptime for last 30 days using aggregation to save memory
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        // Use chunking to process incidents in batches to avoid memory issues
        $totalMinutes = Carbon::now()->diffInMinutes($thirtyDaysAgo);
        $downtimeMinutes = 0;
        $totalRecoveryTime = 0;
        $resolvedIncidents = 0;

        // Process incidents in chunks to avoid memory exhaustion
        DowntimeIncident::where('started_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('resolved_at')
            ->select('started_at', 'resolved_at', 'duration')
            ->chunk(1000, function ($incidents) use (&$downtimeMinutes, &$totalRecoveryTime, &$resolvedIncidents) {
                foreach ($incidents as $incident) {
                    if ($incident->resolved_at) {
                        // Use duration if available, otherwise calculate
                        $duration = $incident->duration ?? $incident->started_at->diffInMinutes($incident->resolved_at);
                        $downtimeMinutes += $duration;
                        $totalRecoveryTime += $duration;
                        $resolvedIncidents++;
                    }
                }
            });

        $currentUptime = $totalMinutes > 0 ? round((($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100, 2) : 100;
        $mttr = $resolvedIncidents > 0 ? round($totalRecoveryTime / $resolvedIncidents / 60, 1) : 0; // in hours
        $onTrack = $currentUptime >= $targetSLA;

        return [
            'target' => $targetSLA,
            'current' => $currentUptime,
            'onTrack' => $onTrack,
            'mttr' => $mttr,
            'incidents' => $resolvedIncidents
        ];
    }

    /**
     * Get severity distribution
     */
    private function getSeverityDistribution()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $critical = DowntimeIncident::where('severity', 'critical')
            ->where('started_at', '>=', $thirtyDaysAgo)->count();
        $high = DowntimeIncident::where('severity', 'high')
            ->where('started_at', '>=', $thirtyDaysAgo)->count();
        $medium = DowntimeIncident::where('severity', 'medium')
            ->where('started_at', '>=', $thirtyDaysAgo)->count();
        $low = DowntimeIncident::where('severity', 'low')
            ->where('started_at', '>=', $thirtyDaysAgo)->count();

        return [
            'critical' => $critical,
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
            'total' => $critical + $high + $medium + $low
        ];
    }

    /**
     * Calculate overall system availability
     */
    private function calculateSystemAvailability($applications)
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        
        $totalMinutes = Carbon::now()->diffInMinutes($sevenDaysAgo);
        $downtimeMinutes = 0;

        // Use chunking to process incidents in batches to avoid memory issues
        DowntimeIncident::where('started_at', '>=', $sevenDaysAgo)
            ->select('started_at', 'resolved_at', 'duration')
            ->chunk(1000, function ($incidents) use (&$downtimeMinutes) {
                foreach ($incidents as $incident) {
                    $end = $incident->resolved_at ?: Carbon::now();
                    // Use duration if available, otherwise calculate
                    $duration = $incident->duration ?? $incident->started_at->diffInMinutes($end);
                    $downtimeMinutes += $duration;
                }
            });

        $availability = $totalMinutes > 0 ? round((($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100, 1) : 100;
        
        // Determine status color
        if ($availability >= 99) {
            $status = 'Excellent';
            $color = 'green';
        } elseif ($availability >= 95) {
            $status = 'Good';
            $color = 'yellow';
        } else {
            $status = 'At Risk';
            $color = 'red';
        }

        return [
            'percentage' => $availability,
            'status' => $status,
            'color' => $color,
            'downtime' => round($downtimeMinutes / 60, 1) // hours
        ];
    }

    /**
     * Get top problem applications
     */
    private function getTopProblemApplications()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $problems = DowntimeIncident::where('started_at', '>=', $thirtyDaysAgo)
            ->selectRaw('application_id, COUNT(*) as incident_count, SUM(duration) as total_downtime')
            ->groupBy('application_id')
            ->orderByDesc('incident_count')
            ->with('application')
            ->take(5)
            ->get();

        return $problems->map(function ($problem) {
            return [
                'app' => $problem->application->name,
                'incidents' => $problem->incident_count,
                'downtime' => $problem->total_downtime ?? 0
            ];
        });
    }

    /**
     * Ensure incidents exist for applications that are currently down
     */
    private function ensureIncidentsForDownApplications($applications): void
    {
        foreach ($applications as $application) {
            // Only check active applications
            if (!$application->is_active) {
                continue;
            }
            
            // If application is down, ensure there's an active incident
            if ($application->status === 'down') {
                $activeIncident = DowntimeIncident::where('application_id', $application->id)
                    ->whereNull('resolved_at')
                    ->first();
                
                // If no active incident exists, create one
                if (!$activeIncident) {
                    // Get the most recent down check to get error message
                    $lastDownCheck = \App\Models\MonitoringCheck::where('application_id', $application->id)
                        ->where('status', 'down')
                        ->latest('checked_at')
                        ->first();
                    
                    $incident = DowntimeIncident::create([
                        'application_id' => $application->id,
                        'started_at' => $lastDownCheck->checked_at ?? now(),
                        'severity' => 'high',
                        'error_message' => $lastDownCheck->error_message ?? 'Application is down',
                    ]);
                    
                    \Illuminate\Support\Facades\Log::info("🚨 Incident created for down application {$application->name}: #{$incident->id}");
                }
            } else if ($application->status === 'up') {
                // If application is up, resolve any active incidents
                $activeIncident = DowntimeIncident::where('application_id', $application->id)
                    ->whereNull('resolved_at')
                    ->first();
                
                if ($activeIncident) {
                    $activeIncident->update([
                        'resolved_at' => now(),
                        'duration' => (int) round($activeIncident->started_at->diffInMinutes(now())),
                        'resolution_notes' => 'Application is back online',
                    ]);
                    
                    \Illuminate\Support\Facades\Log::info("✅ Incident #{$activeIncident->id} resolved for {$application->name}");
                }
            }
        }
    }

    /**
     * Calculate uptime trend data for the past 7 days
     */
    private function getUptimeTrendData($applications)
    {
        $days = collect();
        $appTrends = [];

        // Get last 7 days with proper timezone
        $timezone = config('app.timezone', 'UTC');
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now($timezone)->subDays($i);
            // Format: "Nov 28" or "Nov 28, 2025" if different year
            $currentYear = \Carbon\Carbon::now($timezone)->year;
            $dateYear = $date->year;
            if ($dateYear === $currentYear) {
                $days->push($date->format('M d'));
            } else {
                $days->push($date->format('M d, Y'));
            }
        }

        // Calculate uptime/downtime for each day for each app
        foreach ($applications as $app) {
            $uptimeData = [];
            $downtimeData = [];

            // Use timezone-aware dates
            $timezone = config('app.timezone', 'UTC');
            for ($i = 6; $i >= 0; $i--) {
                $startDate = \Carbon\Carbon::now($timezone)->subDays($i)->startOfDay();
                $endDate = \Carbon\Carbon::now($timezone)->subDays($i)->endOfDay();

                // First try to use incidents (more accurate) - use select to limit memory
                $incidents = DowntimeIncident::where('application_id', $app->id)
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
                    })
                    ->select('started_at', 'resolved_at', 'duration')
                    ->get();

                $downtimeMinutes = 0;
                
                if ($incidents->count() > 0) {
                    // Calculate downtime from incidents
                    foreach ($incidents as $incident) {
                        $start = max($incident->started_at, $startDate);
                        $end = $incident->resolved_at ? min($incident->resolved_at, $endDate) : $endDate;
                        // Use duration if available and within the day, otherwise calculate
                        if ($incident->duration && $incident->resolved_at && 
                            $incident->started_at >= $startDate && $incident->resolved_at <= $endDate) {
                            $downtimeMinutes += $incident->duration;
                        } else {
                            $downtimeMinutes += $start->diffInMinutes($end);
                        }
                    }
                } else {
                    // Fallback: Use monitoring checks aggregation to avoid loading all records
                    $downChecks = MonitoringCheck::where('application_id', $app->id)
                        ->whereBetween('checked_at', [$startDate, $endDate])
                        ->where('status', 'down')
                        ->count();
                    
                    $totalChecks = MonitoringCheck::where('application_id', $app->id)
                        ->whereBetween('checked_at', [$startDate, $endDate])
                        ->count();
                    
                    if ($totalChecks > 0) {
                        // Estimate downtime based on percentage of down checks
                        // Assume checks are evenly distributed throughout the day
                        $downtimeMinutes = ($downChecks / $totalChecks) * 1440; // 1440 minutes in a day
                    }
                }

                $uptimePercentage = max(0, 100 - min($downtimeMinutes, 1440) / 1440 * 100);
                $uptimeData[] = round($uptimePercentage, 2);
                $downtimeData[] = round($downtimeMinutes / 60, 1); // Convert to hours
            }

            $appTrends[$app->name] = [
                'uptime' => $uptimeData,
                'downtime' => $downtimeData
            ];
        }

        return [
            'labels' => $days->toArray(),
            'apps' => $appTrends
        ];
    }

    /**
     * API endpoint for real-time dashboard updates (AJAX polling)
     */
    public function apiStatus(Request $request)
    {
        // Prevent browser caching (especially Chrome)
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Get all applications
        $applications = Applications::all();

        $totalApplications = $applications->count();
        $applicationsUp = $applications->where('status', 'up')->count();
        $applicationsDown = $totalApplications - $applicationsUp;
        $activeIncidents = DowntimeIncident::whereNull('resolved_at')->count();

        // Recent incidents (last 5)
        $recentIncidents = DowntimeIncident::with('application')
            ->orderByRaw('resolved_at IS NULL DESC')
            ->orderBy('started_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($incident) {
                return [
                    'id' => $incident->id,
                    'application_name' => $incident->application->name ?? 'Unknown',
                    'started_at' => $incident->started_at->format('M d, Y H:i'),
                    'resolved_at' => $incident->resolved_at ? $incident->resolved_at->format('M d, Y H:i') : null,
                    'duration' => $incident->duration,
                    'status' => $incident->resolved_at ? 'resolved' : 'active',
                ];
            });

        // Application statuses
        $applicationStatuses = $applications->map(function ($app) {
            $isStale = !$app->last_checked_at || $app->last_checked_at->lt(now()->subMinutes(2));
            $isLikelyStale = $app->status === 'up' && 
                            $app->last_checked_at && 
                            $app->last_checked_at->lt(now()->subMinutes(5));
            
            return [
                'id' => $app->id,
                'name' => $app->name,
                'status' => $app->status,
                'response_time' => $app->response_time,
                'ip_address' => $app->ip_address,
                'last_checked_at' => $app->last_checked_at ? $app->last_checked_at->toIso8601String() : null,
                'is_stale' => $isStale || $isLikelyStale,
            ];
        });

        // System availability
        $systemAvailability = $this->calculateSystemAvailability($applications);
        
        // SLA metrics
        $slaData = $this->calculateSLAMetrics($applications);
        
        // Top problems
        $topProblems = $this->getTopProblemApplications();

        // Current server time
        $serverTime = Carbon::now()->format('M d, Y H:i:s');
        $timezone = config('app.timezone');

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'totalApplications' => $totalApplications,
                'applicationsUp' => $applicationsUp,
                'applicationsDown' => $applicationsDown,
                'activeIncidents' => $activeIncidents,
                'systemAvailability' => $systemAvailability,
                'slaData' => $slaData,
                'topProblems' => $topProblems,
                'applications' => $applicationStatuses,
                'recentIncidents' => $recentIncidents,
                'serverTime' => $serverTime,
                'timezone' => $timezone,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
