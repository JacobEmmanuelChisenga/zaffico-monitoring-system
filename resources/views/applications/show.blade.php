@extends('layouts.app')

@section('page_title', $application->name)

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time updates for single application (no page reload)
    let consecutiveFailures = 0;
    const MAX_FAILURES = 3;
    const POLL_INTERVAL = 5000; // 5 seconds for faster network failure detection
    const applicationId = {{ $application->id }};
    
    function fetchApplicationUpdate() {
        // Chrome-specific: Don't rely on navigator.onLine - it's unreliable
        
        // Add timestamp to prevent Chrome caching
        const timestamp = new Date().getTime();
        const url = `{{ route("api.applications.show", $application->id) }}?_t=` + timestamp;
        
        // Create abort controller for better timeout handling
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
                updateApplicationData(data.data);
                consecutiveFailures = 0;
            }
            return data;
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error fetching application status:', error);
            
            // Detect network errors and mark app as down
            const isNetworkError = 
                error.name === 'AbortError' ||
                error.name === 'TypeError' ||
                error.message.includes('Failed to fetch') ||
                error.message.includes('NetworkError');
            
            if (isNetworkError) {
                console.warn('Network error detected - marking app as down');
                // Mark this application as down
                const statusBadge = document.querySelector('[data-status-badge]');
                if (statusBadge) {
                    statusBadge.className = 'px-4 py-2 rounded-lg text-sm font-semibold bg-red-100 text-red-800';
                    statusBadge.innerHTML = '<i class="fas fa-times-circle mr-2"></i>DOWN';
                }
            }
            
            consecutiveFailures++;
            if (consecutiveFailures >= MAX_FAILURES) {
                console.warn('Too many consecutive failures. Pausing updates.');
            }
            throw error;
        });
    }
    
    function updateApplicationData(data) {
        // Update status badge
        const statusBadge = document.querySelector('[data-status-badge]');
        if (statusBadge) {
            const statusClass = data.status === 'up' ? 'bg-green-100 text-green-800' : 
                              (data.status === 'down' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
            const iconClass = data.status === 'up' ? 'fa-check-circle' : 'fa-times-circle';
            statusBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
            statusBadge.innerHTML = `<i class="fas ${iconClass} mr-2"></i>${data.status.charAt(0).toUpperCase() + data.status.slice(1)}`;
        }
        
        // Update response time
        const responseTimeEl = document.querySelector('[data-response-time]');
        if (responseTimeEl && data.response_time) {
            responseTimeEl.textContent = data.response_time + 'ms';
        }
        
        // Update last checked
        const lastCheckedEl = document.querySelector('[data-last-checked]');
        if (lastCheckedEl && data.last_checked_at) {
            const date = new Date(data.last_checked_at);
            lastCheckedEl.textContent = date.toLocaleString();
        }
        
        // Update stats if available
        if (data.stats) {
            // Update any stat elements that exist
            Object.keys(data.stats).forEach(key => {
                const el = document.querySelector(`[data-stat="${key}"]`);
                if (el) el.textContent = data.stats[key];
            });
        }

        // Update charts if trend data provided
        if (data.trendData) {
            updateCharts(data.trendData);
        }
        
        // Update last updated time
        const lastUpdatedEl = document.getElementById('lastUpdated');
        if (lastUpdatedEl) {
            const now = new Date();
            lastUpdatedEl.textContent = now.toLocaleString();
        }
    }
    
    // Start polling
    fetchApplicationUpdate();
    setInterval(function() {
        // Chrome-specific: Don't rely on navigator.onLine - try to fetch anyway
        if (consecutiveFailures < MAX_FAILURES) {
            fetchApplicationUpdate();
        }
    }, POLL_INTERVAL);
    
    // Listen for online/offline events
    window.addEventListener('online', () => {
        consecutiveFailures = 0;
        fetchApplicationUpdate();
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
                <strong>Last Updated:</strong> <span id="lastUpdated">{{ now()->format('M d, Y H:i:s') }}</span> 
                <span class="text-blue-600">({{ config('app.timezone') }})</span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-blue-600">Auto-refresh: 30s</span>
            <button onclick="window.location.reload()" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <i class="fas fa-redo"></i> Refresh Now
            </button>
        </div>
    </div>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ $application->name }}</h1>
            <p class="text-gray-600 mt-1"><i class="fas fa-network-wired"></i> {{ $application->ip_address }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('applications.edit', $application->id) }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('applications') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm mb-2">Current Status</p>
            <span data-status-badge class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $application->status == 'up' ? 'bg-green-100 text-green-800' : ($application->status == 'down' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                <i class="fas {{ $application->status == 'up' ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                {{ ucfirst($application->status ?? 'Unknown') }}
            </span>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm mb-2">Uptime</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['uptime_percentage'] }}%</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stats['up_checks'] }} up / {{ $stats['total_checks'] }} total</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm mb-2">Avg Response Time</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['avg_response_time'] }} ms</p>
            <p class="text-xs text-gray-500 mt-1">Min: {{ $stats['min_response_time'] }}ms | Max: {{ $stats['max_response_time'] }}ms</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-600 text-sm mb-2">Incidents</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_incidents'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stats['active_incidents'] }} active</p>
        </div>
    </div>

    <!-- Trending Graphs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Uptime/Downtime Trend (7 Days) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-line text-blue-600"></i> Uptime/Downtime Trend (7 Days)
            </h3>
            <canvas id="uptimeDowntimeChart" height="80"></canvas>
        </div>

        <!-- Response Time Trend (24 Hours) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-tachometer-alt text-green-600"></i> Response Time Trend (24 Hours)
            </h3>
            <canvas id="responseTimeChart" height="80"></canvas>
        </div>

        <!-- Status Over Time (24 Hours) -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-signal text-purple-600"></i> Status Over Time (24 Hours)
            </h3>
            <canvas id="statusOverTimeChart" height="60"></canvas>
        </div>
    </div>

    <!-- Configuration & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Protocol</p>
                    <p class="text-gray-800">{{ strtoupper($application->protocol ?? 'N/A') }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">IP Address</p>
                    <p class="text-gray-800">{{ $application->ip_address }}</p>
                </div>
                @if($application->port)
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-1">Port</p>
                        <p class="text-gray-800">{{ $application->port }}</p>
                    </div>
                @endif
                @if($application->endpoint)
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-1">Endpoint</p>
                        <p class="text-gray-800">{{ $application->endpoint }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Check Interval</p>
                    <p class="text-gray-800">{{ $application->check_interval ?? 5 }} min</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Timeout</p>
                    <p class="text-gray-800">{{ $application->timeout ?? 10 }}s</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Last Checked</p>
                    <p class="text-gray-800" data-last-checked>{{ $application->last_checked_at?->diffForHumans() ?? 'Never' }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-1">Is Active</p>
                    <p class="text-gray-800">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $application->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $application->is_active ? 'Yes' : 'No' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Recent Checks -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Recent Checks</h3>
                <a href="{{ route('checks.index', ['application_id' => $application->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">
                    View All
                </a>
            </div>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($recentChecks->take(10) as $check)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                {{ $check->status === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($check->status) }}
                            </span>
                            <span class="text-sm text-gray-600">{{ $check->response_time }}ms</span>
                        </div>
                        <span class="text-xs text-gray-500">{{ $check->checked_at?->diffForHumans() ?? '—' }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-4">No checks recorded yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    // Chart data from controller
    let trendData = {!! json_encode($trendData) !!};
    let responseTimeChart = null;
    let statusOverTimeChart = null;

    // Uptime/Downtime Chart (7 Days)
    const uptimeDowntimeCtx = document.getElementById('uptimeDowntimeChart').getContext('2d');
    new Chart(uptimeDowntimeCtx, {
        type: 'line',
        data: {
            labels: trendData.uptime_downtime.labels,
            datasets: [
                {
                    label: 'Uptime %',
                    data: trendData.uptime_downtime.uptime,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Downtime (hours)',
                    data: trendData.uptime_downtime.downtime,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    min: 0,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Uptime %',
                        color: '#22c55e'
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Downtime (hours)',
                        color: '#ef4444'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Response Time Chart (24 Hours)
    const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
    function buildResponseTimeChart(data) {
        if (responseTimeChart) {
            responseTimeChart.destroy();
        }
        responseTimeChart = new Chart(responseTimeCtx, {
            type: 'line',
            data: {
                labels: data.response_time.labels,
                datasets: [{
                    label: 'Response Time (ms)',
                    data: data.response_time.response_times,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Response Time (ms)',
                            color: '#3b82f6'
                        }
                    }
                }
            }
        });
    }

    // Status Over Time Chart (24 Hours)
    const statusOverTimeCtx = document.getElementById('statusOverTimeChart').getContext('2d');
    function buildStatusOverTimeChart(data) {
        if (statusOverTimeChart) {
            statusOverTimeChart.destroy();
        }
        statusOverTimeChart = new Chart(statusOverTimeCtx, {
            type: 'bar',
            data: {
                labels: data.status_over_time.labels,
                datasets: [
                    {
                        label: 'Up',
                        data: data.status_over_time.up,
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: 'Down',
                        data: data.status_over_time.down,
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Checks',
                            color: '#666'
                        }
                    }
                }
            }
        });
    }

    // Build initial charts
    buildResponseTimeChart(trendData);
    buildStatusOverTimeChart(trendData);

    // Update charts when new trend data arrives
    function updateCharts(newTrendData) {
        if (!newTrendData) return;
        trendData = newTrendData;
        buildResponseTimeChart(trendData);
        buildStatusOverTimeChart(trendData);
    }
</script>
@endsection
