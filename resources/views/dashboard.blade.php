@extends('layouts.app')

@section('page_title', 'Dashboard')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time updates for dashboard (no page reload)
    let consecutiveFailures = 0;
    const MAX_FAILURES = 3;
    const POLL_INTERVAL = 5000; // 5 seconds for faster network failure detection
    
    // Chrome-specific: Poll network status since online/offline events can be unreliable
    let lastOnlineStatus = navigator.onLine;
    setInterval(function() {
        const isOnline = navigator.onLine;
        if (lastOnlineStatus !== isOnline) {
            console.log('Network status changed (polling):', isOnline ? 'online' : 'offline');
            lastOnlineStatus = isOnline;
            if (isOnline) {
                // Force immediate update when coming back online
                fetchDashboardUpdate();
            } else {
                // When network goes down, mark all apps as down immediately
                console.log('Network lost - marking all apps as down');
                markAllDashboardAppsAsDown();
            }
        }
        
        // Chrome-specific: Also try a quick fetch test to detect network issues
        // navigator.onLine can be wrong in Chrome, so we test with a fetch
        if (isOnline) {
            // Try a HEAD request to detect if we can actually reach the server
            fetch('{{ route("api.dashboard.status") }}?_test=' + Date.now(), {
                method: 'HEAD',
                cache: 'no-store',
                signal: AbortSignal.timeout(3000) // 3 second timeout for quick test
            }).catch(() => {
                // If HEAD request fails, network is actually down despite navigator.onLine
                console.warn('Network test failed - marking all apps as down');
                markAllDashboardAppsAsDown();
            });
        }
    }, 3000); // Check every 3 seconds (more frequent for Chrome)
    
    // Listen for browser online/offline events (works better in Firefox)
    window.addEventListener('online', function() {
        console.log('Network connection restored (online event)');
        fetchDashboardUpdate();
    });
    
    window.addEventListener('offline', function() {
        console.log('Network connection lost (offline event)');
    });
    
    function fetchDashboardUpdate() {
        // Chrome-specific: Don't rely on navigator.onLine - it's unreliable
        // Instead, try to fetch and detect errors from the response
        
        // Add timestamp to prevent Chrome caching
        const timestamp = new Date().getTime();
        const url = '{{ route("api.dashboard.status") }}?_t=' + timestamp;
        
        // Create abort controller for better timeout handling (Chrome-specific)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
        }, 8000); // 8 second timeout for faster detection
        
        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            cache: 'no-store', // Prevent Chrome from caching
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateDashboardData(data.data);
                consecutiveFailures = 0;
            }
            return data;
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error fetching dashboard status:', error);
            
            // Chrome-specific: Detect ANY fetch failure as potential network issue
            const isNetworkError = 
                error.name === 'AbortError' ||  // Timeout
                error.name === 'TypeError' ||   // Network failure (most common in Chrome)
                error.name === 'NetworkError' ||
                error.message.includes('Failed to fetch') ||
                error.message.includes('NetworkError') ||
                error.message.includes('network') ||
                error.message.includes('ERR_INTERNET_DISCONNECTED') ||
                error.message.includes('ERR_NETWORK_CHANGED') ||
                error.message.includes('ERR_CONNECTION_REFUSED') ||
                error.message.includes('ERR_CONNECTION_TIMED_OUT') ||
                error.message.includes('ERR_NAME_NOT_RESOLVED');
            
            if (isNetworkError) {
                console.warn('Network error detected - marking all apps as down');
                // Mark all apps as down when network fails
                markAllDashboardAppsAsDown();
            }
            
            consecutiveFailures++;
            if (consecutiveFailures >= MAX_FAILURES) {
                console.warn('Too many consecutive failures. Pausing updates.');
            }
            throw error;
        });
    }
    
    /**
     * Mark all applications as down when network fails
     */
    function markAllDashboardAppsAsDown() {
        console.log('Marking all applications as DOWN due to network failure');
        
        // Update stats to show all apps down
        const totalApps = parseInt(document.getElementById('totalApplications')?.textContent || '0');
        if (totalApps > 0) {
            updateElement('applicationsUp', 0);
            updateElement('applicationsDown', totalApps);
            updateElement('activeIncidents', totalApps); // Assume all are incidents
            
            // Update progress bar
            const progressBar = document.querySelector('.bg-green-500.h-2');
            if (progressBar) {
                progressBar.style.width = '0%';
            }
            
            // Update system availability
            updateElement('systemAvailabilityPercentage', '0%');
            updateElement('systemAvailabilityStatus', 'DOWN');
        }
    }
    
    function updateDashboardData(data) {
        // Update stats
        updateElement('totalApplications', data.totalApplications);
        updateElement('applicationsUp', data.applicationsUp);
        updateElement('applicationsDown', data.applicationsDown);
        updateElement('activeIncidents', data.activeIncidents);
        
        // Update progress bar
        const totalApps = data.totalApplications;
        if (totalApps > 0) {
            const upPercentage = (data.applicationsUp / totalApps) * 100;
            const progressBar = document.querySelector('.bg-green-500.h-2');
            if (progressBar) {
                progressBar.style.width = upPercentage + '%';
            }
        }
        
        // Update system availability
        if (data.systemAvailability) {
            const avail = data.systemAvailability;
            updateElement('systemAvailabilityPercentage', avail.percentage + '%');
            updateElement('systemAvailabilityStatus', avail.status);
        }
        
        // Update last updated time
        if (data.serverTime) {
            updateElement('lastUpdated', data.serverTime);
        }
    }
    
    function updateElement(id, text) {
        const element = document.getElementById(id);
        if (element) element.textContent = text;
    }
    
    // Start polling
    fetchDashboardUpdate();
    setInterval(function() {
        // Chrome-specific: Don't rely on navigator.onLine - try to fetch anyway
        // If fetch fails, we'll detect it as a network error
        if (consecutiveFailures < MAX_FAILURES) {
            fetchDashboardUpdate();
        }
    }, POLL_INTERVAL);
    
    // Listen for online/offline events
    window.addEventListener('online', () => {
        consecutiveFailures = 0;
        fetchDashboardUpdate();
    });
});
</script>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Last Updated Indicator -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fas fa-sync-alt text-blue-600 animate-spin"></i>
            <span class="text-sm text-blue-800">
                <strong>Last Updated:</strong> <span id="lastUpdated">{{ $serverTime ?? now()->format('M d, Y H:i:s') }}</span> 
                <span class="text-blue-600">({{ $timezone ?? config('app.timezone') }})</span>
            </span>
        </div>
        <button onclick="window.location.reload()" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
            <i class="fas fa-redo"></i> Refresh Now
        </button>
    </div>
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Applications -->
        <div class="stat-card border-l-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Applications</p>
                    <p id="totalApplications" class="text-3xl font-bold text-gray-800 mt-2">{{ $totalApplications }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-server text-blue-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4 flex items-center gap-1">
                <i class="fas fa-arrow-up text-green-500"></i> Applications being monitored
            </p>
        </div>

        <!-- Applications UP -->
        <div class="stat-card border-l-green-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Applications UP</p>
                    <p id="applicationsUp" class="text-3xl font-bold text-gray-800 mt-2">{{ $applicationsUp }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $totalApplications > 0 ? ($applicationsUp / $totalApplications) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>

        <!-- Applications DOWN -->
        <div class="stat-card border-l-red-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Applications DOWN</p>
                    <p id="applicationsDown" class="text-3xl font-bold text-gray-800 mt-2">{{ $applicationsDown }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
            </div>
            @if($applicationsDown > 0)
                <p class="text-xs text-red-600 mt-4 font-medium">
                    <i class="fas fa-warning"></i> Immediate attention required
                </p>
            @endif
        </div>

        <!-- Active Incidents -->
        <div class="stat-card border-l-yellow-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Active Incidents</p>
                    <p id="activeIncidents" class="text-3xl font-bold text-gray-800 mt-2">{{ $activeIncidents }}</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-exclamation-circle text-yellow-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">Ongoing issues</p>
        </div>
    </div>

    <!-- Executive Summary Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- SLA Compliance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">SLA Compliance (30 Days)</h3>
            <div class="text-center">
                <p class="text-4xl font-bold {{ $slaData['current'] >= $slaData['target'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $slaData['current'] }}%
                </p>
                <p class="text-gray-600 text-sm mt-2">Target: {{ $slaData['target'] }}%</p>
                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $slaData['onTrack'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $slaData['onTrack'] ? '✓ On Track' : '✗ At Risk' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- MTTR -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">MTTR</h3>
            <div class="text-center">
                <p class="text-4xl font-bold text-blue-600">{{ $slaData['mttr'] }}</p>
                <p class="text-gray-600 text-sm mt-2">Hours</p>
                <p class="text-xs text-gray-500 mt-4">Based on {{ $slaData['incidents'] }} resolved incidents</p>
            </div>
        </div>

        <!-- System Availability -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">System Availability (7 Days)</h3>
            <div class="text-center">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="transform -rotate-90 w-24 h-24">
                        <circle cx="48" cy="48" r="45" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                        <circle cx="48" cy="48" r="45" 
                            stroke="{{ $systemAvailability['color'] === 'green' ? '#22c55e' : ($systemAvailability['color'] === 'yellow' ? '#eab308' : '#ef4444') }}" 
                            stroke-width="8" fill="none" stroke-dasharray="{{ $systemAvailability['percentage'] * 2.83 }} 283"
                            style="transition: stroke-dasharray 0.3s;"></circle>
                    </svg>
                    <span class="absolute text-2xl font-bold text-gray-800">{{ $systemAvailability['percentage'] }}%</span>
                </div>
                <p class="text-sm mt-4 font-semibold text-gray-700">{{ $systemAvailability['status'] }}</p>
                <p class="text-xs text-gray-500">{{ $systemAvailability['downtime'] }}h downtime</p>
            </div>
        </div>

        <!-- Incident Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Incident Severity (30 Days)</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">🔴 Critical</span>
                    <span class="font-bold text-red-600">{{ $severityDistribution['critical'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">🟠 High</span>
                    <span class="font-bold text-orange-600">{{ $severityDistribution['high'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">🟡 Medium</span>
                    <span class="font-bold text-yellow-600">{{ $severityDistribution['medium'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">🔵 Low</span>
                    <span class="font-bold text-blue-600">{{ $severityDistribution['low'] }}</span>
                </div>
                <div class="border-t pt-2 mt-2 flex items-center justify-between font-semibold">
                    <span class="text-gray-800">Total</span>
                    <span>{{ $severityDistribution['total'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Problems and Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Uptime Trends Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Application Status Trends (Last 7 Days)</h3>
                <select id="appSelector" class="text-sm border border-gray-300 rounded-lg px-3 py-1">
                    <option value="all">All Applications</option>
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}">{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="showUptime" checked class="rounded">
                        <span class="text-green-600 font-medium">Uptime %</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="showDowntime" checked class="rounded">
                        <span class="text-red-600 font-medium">Downtime (hours)</span>
                    </label>
                </div>
            </div>
            <canvas id="uptimeChart" height="80"></canvas>
        </div>

        <!-- Top Problem Applications -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Top Problem Applications (30 Days)</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @if($topProblems->count() > 0)
                    @foreach($topProblems as $problem)
                        <div class="border-b pb-3 last:border-b-0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-800 text-sm">{{ $problem['app'] }}</span>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                    {{ $problem['incidents'] }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600">{{ $problem['downtime'] ?? 0 }} min downtime</p>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ ($problem['incidents'] / ($topProblems->first()['incidents'] ?? 1)) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-gray-600 text-sm text-center py-4">No incidents in last 30 days</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Recent Incidents</h3>
            <a href="{{ route('incidents.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
        </div>
        <div class="overflow-x-auto">
            @if($recentIncidents->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Started At</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recentIncidents as $incident)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-800 font-medium">{{ $incident->application->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($incident->started_at)
                                    {{ is_string($incident->started_at) ? \Carbon\Carbon::parse($incident->started_at)->format('M d, Y H:i') : $incident->started_at->format('M d, Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $incident->duration ? $incident->duration . ' min' : '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                        {{ $incident->severity === 'critical' ? 'bg-red-100 text-red-800' : ($incident->severity === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ ucfirst($incident->severity ?? 'unknown') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                        {{ $incident->resolved_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $incident->resolved_at ? 'Resolved' : 'Ongoing' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('incidents.show', $incident->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                    <p class="text-gray-600">No recent incidents. Your applications are healthy!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Chart data from controller
    const chartData = {!! json_encode($chartData) !!};
    const applicationsData = {!! json_encode($applications->pluck('name', 'id')) !!};
    
    let chart = null;

    function generateChartConfig(selectedAppId = 'all', showUptime = true, showDowntime = true) {
        let datasets = [];
        const colors = [
            { up: 'rgb(34, 197, 94)', upBg: 'rgba(34, 197, 94, 0.1)', down: 'rgb(239, 68, 68)', downBg: 'rgba(239, 68, 68, 0.1)' },
            { up: 'rgb(59, 130, 246)', upBg: 'rgba(59, 130, 246, 0.1)', down: 'rgb(168, 85, 247)', downBg: 'rgba(168, 85, 247, 0.1)' },
            { up: 'rgb(249, 115, 22)', upBg: 'rgba(249, 115, 22, 0.1)', down: 'rgb(236, 72, 153)', downBg: 'rgba(236, 72, 153, 0.1)' },
            { up: 'rgb(6, 182, 212)', upBg: 'rgba(6, 182, 212, 0.1)', down: 'rgb(14, 165, 233)', downBg: 'rgba(14, 165, 233, 0.1)' }
        ];
        
        let colorIndex = 0;

        if (selectedAppId === 'all') {
            // Show all applications with both uptime and downtime
            Object.keys(chartData.apps).forEach(appName => {
                const appData = chartData.apps[appName];
                const color = colors[colorIndex % colors.length];

                if (showUptime) {
                datasets.push({
                    label: appName + ' - Uptime %',
                    data: appData.uptime,
                    borderColor: color.up,
                    backgroundColor: color.upBg,
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: color.up,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                        yAxisID: 'y',
                        hidden: !showUptime
                    });
                }

                if (showDowntime) {
                    datasets.push({
                        label: appName + ' - Downtime (hours)',
                        data: appData.downtime,
                        borderColor: color.down,
                        backgroundColor: color.downBg,
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: color.down,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        yAxisID: 'y1',
                        hidden: !showDowntime
                });
                }

                colorIndex++;
            });
        } else {
            // Show specific application with uptime and downtime
            const appName = applicationsData[selectedAppId];
            const appData = chartData.apps[appName];
            
            if (appData) {
                if (showUptime) {
                datasets.push({
                    label: appName + ' - Uptime %',
                    data: appData.uptime,
                    fill: true,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(34, 197, 94)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                        yAxisID: 'y',
                        hidden: !showUptime
                });
                }

                if (showDowntime) {
                datasets.push({
                    label: appName + ' - Downtime (hours)',
                    data: appData.downtime,
                    fill: true,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(239, 68, 68)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                        yAxisID: 'y1',
                        hidden: !showDowntime
                });
                }
            }
        }

        return {
            labels: chartData.labels,
            datasets: datasets
        };
    }

    const ctx = document.getElementById('uptimeChart').getContext('2d');
    let showUptime = true;
    let showDowntime = true;
    
    chart = new Chart(ctx, {
        type: 'line',
        data: generateChartConfig('all', showUptime, showDowntime),
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        font: { size: 12 },
                        color: '#666',
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(context) {
                            const label = context[0].label;
                            const timezone = '{{ config("app.timezone") }}';
                            // If label doesn't have year, add current year
                            if (!label.match(/\d{4}/)) {
                                const currentYear = new Date().getFullYear();
                                return label + ', ' + currentYear + ' (' + timezone + ')';
                            }
                            return label + ' (' + timezone + ')';
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + (context.dataset.yAxisID === 'y' ? '%' : ' hours');
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    min: 0,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Uptime %',
                        color: '#22c55e',
                        font: { weight: 'bold' }
                    },
                    ticks: { color: '#999' },
                    grid: { color: '#f0f0f0' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Downtime (hours)',
                        color: '#ef4444',
                        font: { weight: 'bold' }
                    },
                    ticks: { color: '#999' },
                    grid: { drawOnChartArea: false }
                },
                x: {
                    ticks: { 
                        color: '#999',
                        callback: function(value, index) {
                            const label = this.getLabelForValue(value);
                            // Ensure year is shown if not present
                            if (label && !label.match(/\d{4}/)) {
                                const currentYear = new Date().getFullYear();
                                return label + ', ' + currentYear;
                            }
                            return label;
                        }
                    },
                    grid: { display: false },
                    title: {
                        display: true,
                        text: 'Date ({{ config("app.timezone") }})',
                        color: '#666',
                        font: { size: 12 }
                    }
                }
            }
        }
    });

    // Handle app selector change
    document.getElementById('appSelector').addEventListener('change', function() {
        const selectedId = this.value;
        chart.data = generateChartConfig(selectedId, showUptime, showDowntime);
        chart.update();
    });
        
    // Handle uptime checkbox
    document.getElementById('showUptime').addEventListener('change', function() {
        showUptime = this.checked;
        chart.data = generateChartConfig(document.getElementById('appSelector').value, showUptime, showDowntime);
        chart.update();
    });

    // Handle downtime checkbox
    document.getElementById('showDowntime').addEventListener('change', function() {
        showDowntime = this.checked;
        chart.data = generateChartConfig(document.getElementById('appSelector').value, showUptime, showDowntime);
        chart.update();
    });
</script>
@endsection
