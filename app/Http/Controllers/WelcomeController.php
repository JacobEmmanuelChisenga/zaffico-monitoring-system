<?php

namespace App\Http\Controllers;

use App\Models\Applications;
use App\Models\DowntimeIncident;
use App\Models\MonitoringCheck;
use App\Jobs\ExecuteMonitoringChecks;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class WelcomeController extends Controller
{
    /**
     * Display the public welcome page with application metrics
     */
    public function index(Request $request)
    {
        // Always refresh application data from database to ensure we have latest status
        // This prevents showing stale data, especially after network reconnection
        
        // Optional: Trigger immediate check for all apps when page loads (if requested)
        // This helps detect network issues immediately when viewing the page
        // Use async job to prevent page timeout
        if ($request->has('check_now') && $request->boolean('check_now')) {
            try {
                ExecuteMonitoringChecks::dispatch();
            } catch (\Exception $e) {
                Log::warning("Could not dispatch monitoring checks job: " . $e->getMessage());
            }
        }
        
        // Get all active applications - refresh from database to get latest status
        $applications = Applications::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function($app) {
                // Ensure status is normalized to lowercase
                if ($app->status) {
                    $app->status = strtolower($app->status);
                }
                return $app;
            });

        $totalApplications = $applications->count();
        
        // Count apps by their actual status column (case-insensitive)
        $applicationsUp = $applications->filter(function($app) {
            return strtolower($app->status ?? '') === 'up';
        })->count();
        $applicationsDown = $totalApplications - $applicationsUp;
        
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

        // Calculate system availability
        $systemAvailability = $this->calculateSystemAvailability($applications);

        // Get current server time
        $serverTime = Carbon::now()->format('M d, Y H:i:s');
        $timezone = config('app.timezone');
        
        // Pass to view
        return view('welcome', compact(
            'totalApplications',
            'applicationsUp',
            'applicationsDown',
            'activeIncidents',
            'totalChecks',
            'recentIncidents',
            'chartData',
            'applications',
            'systemAvailability',
            'serverTime',
            'timezone'
        ));
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
     * Calculate uptime trend data for the past 7 days
     */
    private function getUptimeTrendData($applications)
    {
        $days = collect();
        $appTrends = [];

        // Get last 7 days with proper timezone
        $timezone = config('app.timezone', 'UTC');
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now($timezone)->subDays($i);
            // Format: "Nov 28" or "Nov 28, 2025" if different year
            $currentYear = Carbon::now($timezone)->year;
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
            $now = Carbon::now($timezone);
            
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = Carbon::now($timezone)->subDays($i)->startOfDay();
                // For today, use current time; for past days, use end of day
                $endDate = ($i === 0) ? $now : Carbon::now($timezone)->subDays($i)->endOfDay();
                $startDate = $dayStart;

                // First try to use incidents (more accurate) - use select to limit memory
                // IMPORTANT: Include active incidents (not resolved) that started before endDate
                // For today, we need to include ALL active incidents, even if they started before today
                $incidents = DowntimeIncident::where('application_id', $app->id)
                    ->where(function ($q) use ($startDate, $endDate, $i) {
                        // For today (i === 0), include ALL active incidents that haven't been resolved
                        if ($i === 0) {
                            // Today: Include incidents that:
                            // 1. Started today and are still active
                            // 2. Started before today but are still active (ongoing from previous days)
                            // 3. Started or ended today
                            $q->where(function ($qToday) use ($startDate, $endDate) {
                                $qToday->whereBetween('started_at', [$startDate, $endDate])
                                      ->orWhereBetween('resolved_at', [$startDate, $endDate])
                                      ->orWhere(function ($qActive) use ($endDate) {
                                          // Active incidents (not resolved) that started before or during today
                                          $qActive->whereNull('resolved_at')
                                                 ->where('started_at', '<=', $endDate);
                                      });
                            });
                        } else {
                            // Past days: Only incidents that started or ended during that day
                            $q->whereBetween('started_at', [$startDate, $endDate])
                              ->orWhereBetween('resolved_at', [$startDate, $endDate])
                              ->orWhere(function ($q2) use ($startDate, $endDate) {
                                  $q2->where('started_at', '<', $startDate)
                                     ->where(function ($q3) use ($endDate) {
                                         $q3->whereNull('resolved_at')
                                            ->orWhere('resolved_at', '>', $endDate);
                                     });
                              });
                        }
                    })
                    ->select('started_at', 'resolved_at', 'duration')
                    ->get();

                $downtimeMinutes = 0;
                
                if ($incidents->count() > 0) {
                    // Calculate downtime from incidents
                    foreach ($incidents as $incident) {
                        $start = max($incident->started_at, $startDate);
                        // For active incidents (not resolved), use current time as end
                        // This ensures today's chart shows current downtime in real-time
                        // IMPORTANT: For today, always use current time for active incidents
                        if ($i === 0 && !$incident->resolved_at) {
                            // Today + active incident = use current time (real-time)
                            $end = $now;
                        } else {
                            // Past days or resolved incidents = use resolved_at or endDate
                            $end = $incident->resolved_at ? min($incident->resolved_at, $endDate) : $endDate;
                        }
                        
                        // Calculate minutes of downtime for this incident within this day
                        $incidentDowntime = $start->diffInMinutes($end);
                        $downtimeMinutes += $incidentDowntime;
                    }
                    
                    // Log for debugging (can be removed in production)
                    if ($i === 0 && $downtimeMinutes > 0) {
                        $activeCount = $incidents->whereNull('resolved_at')->count();
                        Log::debug("Today's downtime for {$app->name}: {$downtimeMinutes} minutes (incidents: {$incidents->count()}, active: {$activeCount})");
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
                        // For today, use elapsed minutes; for past days, use full day
                        $totalMinutesInPeriod = ($i === 0) ? 
                            max(1, $startDate->diffInMinutes($now)) : // Today: use elapsed minutes
                            1440; // Past days: full day
                        $downtimeMinutes = ($downChecks / $totalChecks) * $totalMinutesInPeriod;
                    }
                }

                // For today, calculate based on elapsed time, not full day
                // For past days, use full 1440 minutes
                $totalMinutesInPeriod = ($i === 0) ? 
                    max(1, $startDate->diffInMinutes($now)) : // Today: use elapsed minutes
                    1440; // Past days: full day
                
                $uptimePercentage = max(0, 100 - min($downtimeMinutes, $totalMinutesInPeriod) / $totalMinutesInPeriod * 100);
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
     * API endpoint for real-time status updates (AJAX polling)
     * Returns JSON data for updating the welcome page without refresh
     */
    public function apiStatus(Request $request)
    {
        // Increase execution time for API endpoints to prevent timeout
        set_time_limit(120); // 2 minutes should be enough for data retrieval
        
        // Prevent browser caching (especially Chrome)
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // If data is stale (last check > 2 minutes ago), trigger immediate check
        $staleThreshold = now()->subMinutes(2);
        $hasStaleData = Applications::where('is_active', true)
            ->where(function($q) use ($staleThreshold) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', $staleThreshold);
            })
            ->exists();
        
        // Always trigger immediate check if data is stale OR if explicitly requested
        // This ensures fast recovery when network reconnects
        $shouldCheck = $hasStaleData || $request->has('force_check');
        
        if ($shouldCheck && ($request->has('auto_check') && $request->boolean('auto_check') || $request->has('force_check'))) {
            // Trigger background check asynchronously (don't wait for it)
            // This prevents API timeout when multiple apps are slow/unreachable
            // Only dispatch if queue is configured (not sync), otherwise skip to prevent timeout
            $queueConnection = config('queue.default', 'sync');
            if ($queueConnection !== 'sync') {
                try {
                    ExecuteMonitoringChecks::dispatch();
                    Log::info("Triggered immediate monitoring checks due to stale data or force_check request");
                } catch (\Exception $e) {
                    // If queue is not available, log but don't fail the request
                    Log::warning("Could not dispatch monitoring checks job: " . $e->getMessage());
                }
            } else {
                // Queue is sync - don't trigger checks from API to prevent timeout
                // The scheduled command will handle checks instead
                Log::debug("Skipping immediate checks from API (sync queue would cause timeout)");
            }
        }
        
        // Get all active applications
        $applications = Applications::where('is_active', true)->get();

        $totalApplications = $applications->count();
        // Count apps by their actual status column (case-insensitive)
        $applicationsUp = $applications->filter(function($app) {
            return strtolower($app->status ?? '') === 'up';
        })->count();
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

        // Application statuses with stale detection
        // IMPORTANT: If network is down, all apps should show as DOWN
        // Check if we can detect network connectivity issues
        $applicationStatuses = $applications->map(function ($app) {
            // Use last_checked_at from application, or fallback to most recent monitoring check
            $lastChecked = $app->last_checked_at;
            if (!$lastChecked) {
                // Fallback: Get the most recent monitoring check
                $recentCheck = \App\Models\MonitoringCheck::where('application_id', $app->id)
                    ->orderBy('checked_at', 'desc')
                    ->first();
                if ($recentCheck && $recentCheck->checked_at) {
                    $lastChecked = $recentCheck->checked_at;
                }
            }
            
            $isStale = !$lastChecked || $lastChecked->lt(now()->subMinutes(2));
            
            // Normalize status to lowercase for consistency
            $normalizedStatus = strtolower($app->status ?? 'unknown');
            
            // If status is "up" but last check was more than 5 minutes ago, it's likely stale
            // This helps catch cases where scheduler stopped or network was down
            $isLikelyStale = $normalizedStatus === 'up' && 
                            $lastChecked && 
                            $lastChecked->lt(now()->subMinutes(5));
            
            return [
                'id' => $app->id,
                'name' => $app->name,
                'status' => $normalizedStatus, // Always return lowercase for consistency
                'response_time' => $app->response_time,
                'ip_address' => $app->ip_address,
                'protocol' => $app->protocol ?? 'http',
                'endpoint' => $app->endpoint,
                'last_checked_at' => $lastChecked ? $lastChecked->toIso8601String() : null,
                'is_stale' => $isStale || $isLikelyStale,
                'stale_message' => $isStale ? 'Status may be outdated - check in progress...' : 
                                 ($isLikelyStale ? 'Status may be outdated - last checked ' . $lastChecked->diffForHumans() : null),
            ];
        });

        // System availability
        $systemAvailability = $this->calculateSystemAvailability($applications);

        // Chart data (only if requested - can be heavy)
        $chartData = null;
        if ($request->has('include_charts') && $request->boolean('include_charts')) {
            $chartData = $this->getUptimeTrendData($applications);
        }

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
                'applications' => $applicationStatuses,
                'recentIncidents' => $recentIncidents,
                'serverTime' => $serverTime,
                'timezone' => $timezone,
                'chartData' => $chartData,
                'hasStaleData' => $hasStaleData,
            ]
        ]);
    }

    /**
     * Perform immediate checks for all active applications
     * Called when user requests fresh data or when page loads with check_now parameter
     */
    private function performImmediateChecks(): void
    {
        $applications = Applications::where('is_active', true)->get();
        
        if ($applications->isEmpty()) {
            return;
        }

        $healthCheckService = new \App\Services\HealthCheckService();
        $alertRuleService = new \App\Services\AlertRuleService();

        foreach ($applications as $application) {
            try {
                $check = $healthCheckService->executeAndRecord($application);

                $application->update([
                    'status' => $check->status,
                    'response_time' => $check->response_time,
                    'last_checked_at' => now(),
                ]);

                // Update incidents
                $activeIncident = \App\Models\DowntimeIncident::where('application_id', $application->id)
                    ->whereNull('resolved_at')
                    ->first();

                if ($check->status === 'down' && !$activeIncident) {
                    \App\Models\DowntimeIncident::create([
                        'application_id' => $application->id,
                        'started_at' => now(),
                        'severity' => 'high',
                        'error_message' => $check->error_message,
                    ]);
                } elseif ($check->status === 'up' && $activeIncident) {
                    $activeIncident->update([
                        'resolved_at' => now(),
                        'duration' => (int) round($activeIncident->started_at->diffInMinutes(now())),
                        'resolution_notes' => 'Application is back online',
                    ]);
                }

                // Evaluate alert rules
                $alertRuleService->evaluateRules($application, $check);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Immediate check failed for {$application->name}: {$e->getMessage()}");
            }
        }
    }
}

