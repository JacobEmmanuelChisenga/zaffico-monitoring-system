<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaffico Monitoring System - Public Status</title>
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-md overflow-hidden">
                        <img src="{{ asset('images/image.png') }}" alt="Zaffico Logo"
                            class="w-full h-full object-contain"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-full bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center"
                            style="display: none;">
                            <i class="fas fa-heartbeat text-white text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Zaffico</h1>
                        <p class="text-xs text-gray-500">Monitoring System</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-4 py-2 border border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
        </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Title -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">System Status Overview</h2>
            <p class="text-gray-600">Real-time monitoring status of all applications</p>
            <div class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                <i class="fas fa-clock"></i>
                <span data-clock>Last Updated: {{ $serverTime ?? now()->format('M d, Y H:i:s') }}
                    ({{ $timezone ?? config('app.timezone') }})</span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Applications -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Applications</p>
                        <p id="totalApplications" class="text-3xl font-bold text-gray-800 mt-2">{{ $totalApplications }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-server text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Applications UP -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
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
                        <div class="bg-green-500 h-2 rounded-full"
                            style="width: {{ $totalApplications > 0 ? ($applicationsUp / $totalApplications) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications DOWN -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Applications DOWN</p>
                        <p id="applicationsDown" class="text-3xl font-bold text-gray-800 mt-2">{{ $applicationsDown }}
                        </p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-lg">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
                @if ($applicationsDown > 0)
                    <p class="text-xs text-red-600 mt-4 font-medium">
                        <i class="fas fa-warning"></i> Immediate attention required
                    </p>
                @endif
            </div>

            <!-- Active Incidents -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Active Incidents</p>
                        <p id="activeIncidents" class="text-3xl font-bold text-gray-800 mt-2">{{ $activeIncidents }}
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-exclamation-circle text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-4">Ongoing issues</p>
            </div>
        </div>



        <!-- Applications (cards like index) -->
        <div class="bg-white rounded-lg shadow p-4 mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800">Applications</h3>
                <p class="text-xs text-gray-500">Protocol, last check, response time</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse($applications as $app)
                    <div class="border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition"
                        data-app-id="{{ $app->id }}">
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-2.5 h-2.5 rounded-full status-dot {{ strtolower($app->status) === 'up' ? 'bg-green-500' : 'bg-red-500' }}">
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-gray-800">{{ $app->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $app->ip_address }}</p>
                                </div>
                            </div>
                            <span
                                class="status-badge px-2 py-0.5 rounded-full text-xs font-medium {{ strtolower($app->status) === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ strtoupper($app->status) }}
                                </span>
                        </div>

                        <div class="px-4 py-2.5 space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Protocol</p>
                                    <p class="text-xs text-gray-800 mt-0.5" data-protocol>
                                        {{ strtoupper($app->protocol ?? 'HTTP') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Response Time</p>
                                    <p class="text-xs text-gray-800 mt-0.5" data-response-time>
                                        {{ $app->response_time ? $app->response_time . 'ms' : '—' }}
                                    </p>
                                </div>
                            </div>
                            @if ($app->endpoint)
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Endpoint</p>
                                    <p class="text-xs text-gray-800 mt-0.5">{{ $app->endpoint }}</p>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold">Last Checked</p>
                                <p class="text-xs text-gray-800 mt-0.5" data-last-checked>
                                    @if ($app->last_checked_at)
                                        <span
                                            title="{{ $app->last_checked_at->format('M d, Y H:i:s') }} ({{ config('app.timezone') }})">
                                            {{ $app->last_checked_at->diffForHumans() }}
                            </span>
                                    @else
                                        Never
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-full bg-gray-50 border border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                        <p class="text-gray-600 font-semibold text-sm">No applications yet</p>
                        <p class="text-gray-500 text-xs mt-1">Once monitoring starts, applications will appear here.</p>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            <!-- System Availability -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">System Availability (Last 7 Days)</h3>

                <div class="flex items-center gap-6">
                    <div class="text-center">
                        @php
                            $colorClass = match ($systemAvailability['color']) {
                                'green' => 'text-green-600',
                                'yellow' => 'text-yellow-600',
                                'red' => 'text-red-600',
                                default => 'text-gray-600',
                            };
                            $bgColorClass = match ($systemAvailability['color']) {
                                'green' => 'bg-green-500',
                                'yellow' => 'bg-yellow-500',
                                'red' => 'bg-red-500',
                                default => 'bg-gray-500',
                            };
                        @endphp

                        <div id="systemAvailabilityPercentage" class="text-4xl font-bold {{ $colorClass }}">
                            {{ $systemAvailability['percentage'] }}%
                        </div>

                        <p id="systemAvailabilityStatus" class="text-sm text-gray-500 mt-1">
                            {{ $systemAvailability['status'] }}
                        </p>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                            <span>Uptime</span>
                            <span id="systemDowntime">{{ $systemAvailability['downtime'] }}h downtime</span>
                        </div>

                        <div class="w-full bg-gray-200 rounded-full h-4" title="{{ $systemAvailability['percentage'] }}% uptime · {{ $systemAvailability['downtime'] }}h downtime">
                            <div class="{{ $bgColorClass }} h-4 rounded-full"
                                style="width: {{ $systemAvailability['percentage'] }}%"
                                title="{{ $systemAvailability['percentage'] }}% uptime · {{ $systemAvailability['downtime'] }}h downtime">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uptime Trend Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Uptime Trend (Last 7 Days)</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="uptimeChart"></canvas>
                </div>
            </div>

        </div>


        <!-- Individual Application Graphs -->
        @if ($applications->count() > 0)
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Application Uptime & Downtime Trends (Last 7 Days)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($applications as $app)
                        @php
                            $appChartData = $chartData['apps'][$app->name] ?? null;
                        @endphp
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-3 h-3 rounded-full {{ strtolower($app->status) === 'up' ? 'bg-green-500' : 'bg-red-500' }}">
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm">{{ $app->name }}</h4>
                                </div>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium {{ strtolower($app->status) === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ strtoupper($app->status) }}
                            </span>
                            </div>
                            @if ($appChartData && isset($appChartData['uptime']) && isset($appChartData['downtime']))
                                <div style="position: relative; height: 250px;">
                                    <canvas id="appChart_{{ $app->id }}"></canvas>
                                </div>
                            @else
                                <div class="text-center py-8 text-gray-500 text-sm">
                                    <i class="fas fa-chart-line text-gray-300 text-2xl mb-2"></i>
                                    <p>No data available yet</p>
                                    <p class="text-xs mt-1">Data will appear after monitoring begins</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent Incidents -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Incidents</h3>
            @if ($recentIncidents->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Application</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Started</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Duration</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($recentIncidents as $incident)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $incident->application->name ?? 'Unknown' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $incident->started_at->format('M d, Y H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($incident->resolved_at)
                                            <span
                                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Resolved
                                </span>
                                        @else
                                            <span
                                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Active
                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if ($incident->resolved_at && $incident->duration)
                                            {{ $incident->duration }} minutes
                                        @elseif(!$incident->resolved_at)
                                            Ongoing
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No recent incidents</p>
            @endif
        </div>

        <!-- Login CTA -->
        @guest
            <div class="mt-8 bg-gradient-to-r from-green-600 to-green-700 rounded-lg shadow-lg p-8 text-center text-white">
                <h3 class="text-2xl font-bold mb-2">Need More Details?</h3>
                <p class="text-green-100 mb-6">Login to access the full dashboard with detailed metrics, historical data,
                    and management tools.</p>
                <a href="{{ route('login') }}"
                    class="inline-block px-6 py-3 bg-white text-green-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login to Dashboard
                </a>
                </div>
        @endguest
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} Zaffico Monitoring System. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Chart.js Script with Real-time Updates -->
    <script>
        // Global chart instances for updates
        let uptimeChart = null;
        let appCharts = {};

        // Wait for DOM and Chart.js to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Check if Chart is available
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }

            // Initialize charts on page load
            initializeCharts();

            // Start real-time updates (poll every 10 seconds)
            startRealTimeUpdates();

            // Update last updated time every second
            updateClock();
            setInterval(updateClock, 1000);

            // Listen for browser online/offline events
            // Chrome sometimes doesn't fire these events reliably, so we also poll
            window.addEventListener('online', function() {
                console.log('Network connection restored (online event)');
                hideNetworkOfflineWarning();
                // Immediately fetch fresh data with charts
                fetchStatusUpdate(true);
            });

            window.addEventListener('offline', function() {
                console.log('Network connection lost (offline event)');
                showNetworkOfflineWarning();
            });

            // Chrome-specific: Also check navigator.onLine periodically
            // Chrome's online/offline events can be unreliable
            // Additionally, try a test fetch to detect network issues
            setInterval(function() {
                const wasOnline = typeof window.lastOnlineStatus !== 'undefined' ? window.lastOnlineStatus :
                    navigator.onLine;
                const isOnline = navigator.onLine;

                if (wasOnline !== isOnline) {
                    console.log('Network status changed (polling):', isOnline ? 'online' : 'offline');
                    window.lastOnlineStatus = isOnline;

                    if (isOnline) {
                        hideNetworkOfflineWarning();
                        // Force immediate update with charts when coming back online
                        console.log('Network restored - triggering immediate checks and updating charts');
                        
                        // Trigger immediate server-side check to detect apps coming back online
                        const checkUrl = '{{ route("api.status") }}?force_check=true&auto_check=true&include_charts=true&_t=' + Date.now();
                        fetch(checkUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Cache-Control': 'no-cache, no-store, must-revalidate',
                                'Pragma': 'no-cache',
                                'Expires': '0'
                            },
                            cache: 'no-store'
                        }).catch(err => console.warn('Failed to trigger immediate check:', err));
                        
                        // Also update UI immediately
                        fetchStatusUpdate(true).then(() => {
                            // Force another update after short delays to ensure data is fresh
                            setTimeout(() => fetchStatusUpdate(true), 2000);
                            setTimeout(() => fetchStatusUpdate(true), 5000);
                        });
                    } else {
                        showNetworkOfflineWarning();
                        // When network goes down, mark all apps as down immediately
                        console.log('Network lost - marking all apps as down');
                        markAllAppsAsDown();
                    }
                }

                // Light reachability check only. Do not hit /api/status every few seconds —
                // that saturates the single-threaded PHP-CGI workers and causes nginx 502s.
                if (isOnline) {
                    fetch('{{ url('/') }}', {
                        method: 'HEAD',
                        cache: 'no-store',
                        signal: AbortSignal.timeout(3000)
                    }).catch(() => {
                        console.warn('Network test failed - marking all apps as down');
                        markAllAppsAsDown();
                        showNetworkErrorWarning();
                    });
                }
            }, 15000); // Every 15 seconds is enough for online/offline detection

            // Initial network check
            window.lastOnlineStatus = navigator.onLine;
            if (!navigator.onLine) {
                showNetworkOfflineWarning();
            }
        });

        /**
         * Initialize all charts on page load
         */
        function initializeCharts() {
            // Uptime Trend Chart
            const uptimeCtx = document.getElementById('uptimeChart');
            if (uptimeCtx) {
                try {
                    const chartData = @json($chartData ?? ['labels' => [], 'apps' => []]);
                    const datasets = [];
                    const colors = ['#27ae60', '#3498db', '#9b59b6', '#e74c3c', '#f39c12', '#1abc9c', '#34495e'];

                    // Check if chartData has apps
                    if (chartData && chartData.apps && Object.keys(chartData.apps).length > 0) {
                        let colorIndex = 0;
                        for (const [appName, data] of Object.entries(chartData.apps)) {
                            if (data && data.uptime && Array.isArray(data.uptime)) {
                                datasets.push({
                                    label: `${appName} - Uptime %`,
                                    data: data.uptime,
                                    borderColor: colors[colorIndex % colors.length],
                                    backgroundColor: colors[colorIndex % colors.length] + '20',
                                    tension: 0.4,
                                    fill: true
                                });
                                colorIndex++;
                            }
                        }
                    }

                    // Only create chart if we have data
                    if (datasets.length > 0 && chartData.labels && chartData.labels.length > 0) {
                        uptimeChart = new Chart(uptimeCtx, {
                            type: 'line',
                            data: {
                                labels: chartData.labels,
                                datasets: datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                aspectRatio: 2,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 12,
                                            padding: 10,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            title: function(context) {
                                                const label = context[0].label;
                                                const timezone = '{{ config('app.timezone') }}';
                                                // If label doesn't have year, add current year
                                                if (!label.match(/\d{4}/)) {
                                                    const currentYear = new Date().getFullYear();
                                                    return label + ', ' + currentYear + ' (' + timezone + ')';
                                                }
                                                return label + ' (' + timezone + ')';
                                            },
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.parsed.y.toFixed(
                                                    2) + '%';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        display: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
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
                                        title: {
                                            display: true,
                                            text: 'Date ({{ config('app.timezone') }})',
                                            color: '#6b7280',
                                            font: {
                                                size: 12
                                            }
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return value + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        // Show message if no data
                        const chartContainer = uptimeCtx.closest('div');
                        if (chartContainer) {
                            chartContainer.innerHTML =
                                '<p class="text-gray-500 text-center py-8">No chart data available. Chart will appear once monitoring data is collected.</p>';
                        }
                    }
                } catch (error) {
                    console.error('Error creating chart:', error);
                    uptimeCtx.parentElement.innerHTML =
                        '<p class="text-red-500 text-center py-8">Error loading chart data</p>';
                }
            }

            // Individual Application Charts
            const chartData = @json($chartData ?? ['labels' => [], 'apps' => []]);
            const applications = @json($applications ?? []);

            applications.forEach(function(app) {
                const canvasId = 'appChart_' + app.id;
                const canvas = document.getElementById(canvasId);

                if (!canvas) return;

                const appData = chartData.apps && chartData.apps[app.name];

                if (!appData || !appData.uptime || !appData.downtime) {
                    canvas.parentElement.innerHTML =
                        '<p class="text-gray-500 text-center py-4 text-sm">No data available yet</p>';
                    return;
                }

                try {
                    appCharts[app.id] = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: chartData.labels || [],
                            datasets: [{
                                    label: 'Uptime %',
                                    data: appData.uptime,
                                    borderColor: '#27ae60',
                                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    yAxisID: 'y',
                                    pointRadius: 3,
                                    pointHoverRadius: 5
                                },
                                {
                                    label: 'Downtime (hours)',
                                    data: appData.downtime,
                                    borderColor: '#e74c3c',
                                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    yAxisID: 'y1',
                                    pointRadius: 3,
                                    pointHoverRadius: 5
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 8,
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        title: function(context) {
                                            const label = context[0].label;
                                            const timezone = '{{ config('app.timezone') }}';
                                            if (!label.match(/\d{4}/)) {
                                                const currentYear = new Date().getFullYear();
                                                return label + ', ' + currentYear + ' (' + timezone +
                                                    ')';
                                            }
                                            return label + ' (' + timezone + ')';
                                        },
                                        label: function(context) {
                                            if (context.datasetIndex === 0) {
                                                return 'Uptime: ' + context.parsed.y.toFixed(2) + '%';
                                            } else {
                                                return 'Downtime: ' + context.parsed.y.toFixed(1) +
                                                    ' hours';
                                            }
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45,
                                        font: {
                                            size: 9
                                        },
                                        callback: function(value, index) {
                                            const label = this.getLabelForValue(value);
                                            if (label && !label.match(/\d{4}/)) {
                                                const currentYear = new Date().getFullYear();
                                                return label + ', ' + currentYear;
                                            }
                                            return label;
                                        }
                                    }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    beginAtZero: true,
                                    max: 100,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        },
                                        font: {
                                            size: 9
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Uptime %',
                                        color: '#27ae60',
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    beginAtZero: true,
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            return value + 'h';
                                        },
                                        font: {
                                            size: 9
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Downtime (hours)',
                                        color: '#e74c3c',
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creating chart for ' + app.name + ':', error);
                    canvas.parentElement.innerHTML =
                        '<p class="text-red-500 text-center py-4 text-sm">Error loading chart</p>';
                }
            });

        }

        /**
         * Start real-time updates via AJAX polling
         */
        let chartUpdateCounter = 0;
        // Charts are heavy — refresh them every 3rd poll, not every time
        const CHART_UPDATE_INTERVAL = 3;
        let lastDownCount = 0;
        let lastActiveIncidents = 0;

        function startRealTimeUpdates() {
            // 30s keeps the dashboard fresh without filling all PHP-CGI workers
            const POLL_INTERVAL = 30000;

            let consecutiveFailures = 0;
            const MAX_FAILURES = 3;

            fetchStatusUpdate(true);
            chartUpdateCounter = 0;

            setInterval(function() {
                if (consecutiveFailures >= MAX_FAILURES) {
                    return;
                }

                chartUpdateCounter++;
                const includeCharts = (chartUpdateCounter % CHART_UPDATE_INTERVAL === 0);

                fetchStatusUpdate(includeCharts).then((data) => {
                    consecutiveFailures = 0;

                    if (data && data.data && data.data.applicationsDown !== undefined) {
                        if (data.data.applicationsDown !== lastDownCount) {
                            lastDownCount = data.data.applicationsDown;
                            console.log('Status changed: ' + data.data.applicationsDown +
                                ' apps down. Charts updated.');
                        }
                    }

                    if (data && data.data && data.data.activeIncidents !== undefined) {
                        if (data.data.activeIncidents !== lastActiveIncidents) {
                            const oldCount = lastActiveIncidents;
                            lastActiveIncidents = data.data.activeIncidents;

                            if (data.data.activeIncidents !== oldCount) {
                                console.log('Incidents changed: ' + data.data.activeIncidents +
                                    ' active. Refreshing charts...');
                                setTimeout(() => fetchStatusUpdate(true), 2000);
                            }
                        }
                    }
                }).catch(() => {
                    consecutiveFailures++;
                    if (consecutiveFailures >= MAX_FAILURES) {
                        console.warn('Too many consecutive failures. Pausing updates.');
                    }
                });
            }, POLL_INTERVAL);
        }

        /**
         * Fetch status update from API
         * Returns a Promise for error handling
         * @param {boolean} includeCharts - Whether to include chart data (heavier request)
         */
        function fetchStatusUpdate(includeCharts = false) {
            // Chrome-specific: Don't rely on navigator.onLine - it's unreliable
            // Instead, try to fetch and detect errors from the response

            // Auto-trigger check if data is stale
            // Add timestamp to prevent Chrome caching
            const timestamp = new Date().getTime();
            const url = '{{ route('api.status') }}?include_charts=' + (includeCharts ? 'true' : 'false') +
                '&auto_check=true&_t=' + timestamp;

            // Create abort controller for better timeout handling (Chrome-specific)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                controller.abort();
            }, 30000); // Allow slow chart/status queries without aborting early

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
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Debug: Log status data to help identify issues
                        if (data.data && data.data.applications) {
                            console.log('API returned applications:', data.data.applications.map(a => ({
                                name: a.name,
                                status: a.status,
                                response_time: a.response_time
                            })));
                        }
                        updatePageData(data.data, data.timestamp);

                        // Update chart if chart data is included
                        if (includeCharts && data.data.chartData) {
                            console.log('Updating charts with new data...');
                            updateUptimeChart(data.data.chartData);
                            updateApplicationCharts(data.data.chartData);
                        } else if (includeCharts && !data.data.chartData) {
                            console.warn('Chart update requested but no chart data in response');
                        }

                        // Hide network warnings if we got data
                        hideNetworkOfflineWarning();
                        hideNetworkErrorWarning();

                        // Show warning if data is stale
                        if (data.data.hasStaleData) {
                            showStaleWarning();
                        } else {
                            hideStaleWarning();
                        }
                    }
                    return data;
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    console.error('Error fetching status:', error);

                    // Chrome-specific: Detect ANY fetch failure as potential network issue
                    // Chrome's navigator.onLine is unreliable, so we detect from fetch errors
                    const isNetworkError =
                        error.name === 'AbortError' || // Timeout
                        error.name === 'TypeError' || // Network failure (most common in Chrome)
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
                        console.warn('Network error detected in Chrome - marking all apps as down');
                        showNetworkErrorWarning('Network connection failed. All applications should show as DOWN.');
                        // Force all apps to show as down when network fails
                        markAllAppsAsDown();
                    } else {
                        // Other errors (server errors, etc.)
                        showNetworkErrorWarning('Unable to reach monitoring server. Status may be outdated.');
                    }

                    // Re-throw for caller to handle
                    throw error;
                });
        }

        /**
         * Show stale data warning
         */
        function showStaleWarning() {
            let warning = document.getElementById('staleWarning');
            if (!warning) {
                warning = document.createElement('div');
                warning.id = 'staleWarning';
                warning.className =
                    'fixed top-4 right-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg shadow-lg z-50 max-w-md';
                warning.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <p class="font-semibold">Checking Status...</p>
                           
                </div>
        </div>
                `;
                document.body.appendChild(warning);
            }
        }

        /**
         * Hide stale data warning
         */
        function hideStaleWarning() {
            const warning = document.getElementById('staleWarning');
            if (warning) {
                warning.remove();
            }
        }

        /**
         * Show network offline warning (browser has no network)
         */
        function showNetworkOfflineWarning() {
            let warning = document.getElementById('networkOfflineWarning');
            if (!warning) {
                warning = document.createElement('div');
                warning.id = 'networkOfflineWarning';
                warning.className =
                    'fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg z-50 max-w-md';
                warning.innerHTML = `
                    <div class="flex items-start gap-3">
                        <i class="fas fa-wifi-slash text-xl"></i>
                        <div class="flex-1">
                            <p class="font-semibold mb-1">No Network Connection</p>
                            <p class="text-sm mb-2">Your device is offline. Cannot check application status.</p>
                            <p class="text-xs text-red-600">Status shown may be outdated. Please check your network connection.</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                document.body.appendChild(warning);
            }
        }

        /**
         * Hide network offline warning
         */
        function hideNetworkOfflineWarning() {
            const warning = document.getElementById('networkOfflineWarning');
            if (warning) {
                warning.remove();
            }
        }

        /**
         * Show network error warning (can't reach server)
         */
        function showNetworkErrorWarning(message) {
            const defaultMessage =
                'Cannot reach monitoring server. If your network is disconnected, all applications should show as DOWN.';
            let warning = document.getElementById('networkErrorWarning');
            if (!warning) {
                warning = document.createElement('div');
                warning.id = 'networkErrorWarning';
                warning.className =
                    'fixed top-4 right-4 bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 rounded-lg shadow-lg z-50 max-w-md';
                document.body.appendChild(warning);
            }
            warning.innerHTML = `
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                    <div class="flex-1">
                        <p class="font-semibold mb-1">Connection Error</p>
                        <p class="text-sm">${message || defaultMessage}</p>
                        <p class="text-xs text-orange-600 mt-2">Status updates paused. Will resume when connection is restored.</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-orange-500 hover:text-orange-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }

        /**
         * Hide network error warning
         */
        function hideNetworkErrorWarning() {
            const warning = document.getElementById('networkErrorWarning');
            if (warning) {
                warning.remove();
            }
        }

        /**
         * Update page data without refresh
         */
        function updatePageData(data, serverNowIso) {
            // Update stats cards
            updateElement('totalApplications', data.totalApplications);
            updateElement('applicationsUp', data.applicationsUp);
            updateElement('applicationsDown', data.applicationsDown);
            updateElement('activeIncidents', data.activeIncidents);

            // Update progress bars
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
                updateElement('systemDowntime', avail.downtime + 'h downtime');

                // Update availability bar
                const availBar = document.querySelector('.bg-green-500.h-4, .bg-yellow-500.h-4, .bg-red-500.h-4');
                if (availBar) {
                    availBar.className = `h-4 rounded-full ${getAvailabilityColorClass(avail.color)}`;
                    availBar.style.width = avail.percentage + '%';
                    availBar.title = `${avail.percentage}% uptime · ${avail.downtime}h downtime`;
                }
                const availBarWrapper = availBar ? availBar.parentElement : null;
                if (availBarWrapper) {
                    availBarWrapper.title = `${avail.percentage}% uptime · ${avail.downtime}h downtime`;
                }
            }

            // Update application statuses
            if (data.applications && Array.isArray(data.applications)) {
                updateApplicationStatuses(data.applications, serverNowIso);

                // Show stale indicators
                data.applications.forEach(app => {
                    if (app.is_stale) {
                        showStaleIndicator(app.id);
                    } else {
                        hideStaleIndicator(app.id);
                    }
                });
            }

            // Update recent incidents
            if (data.recentIncidents && Array.isArray(data.recentIncidents)) {
                updateRecentIncidents(data.recentIncidents);
            }

            // Update server time
            if (data.serverTime) {
                updateElement('serverTime', data.serverTime + ' (' + (data.timezone || 'UTC') + ')');
            }
        }

        /**
         * Mark all applications as down when network fails
         * Chrome-specific: When fetch fails, we can't get real data, so mark all as down
         */
        function markAllAppsAsDown() {
            console.log('Marking all applications as DOWN due to network failure');

            // Get all application cards
            const appCards = document.querySelectorAll('[data-app-id]');

            appCards.forEach(card => {
                // Update status dot to red
                const statusDot = card.querySelector('.status-dot');
                if (statusDot) {
                    statusDot.className = 'w-3 h-3 rounded-full bg-red-500 animate-pulse';
                    statusDot.title = 'Network connection failed - cannot verify status';
                }

                // Update status badge to DOWN
                const statusBadge = card.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = 'px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
                    statusBadge.textContent = 'DOWN';
                    statusBadge.title = 'Network connection failed';
                }
            });

            // Update stats cards
            const totalApps = appCards.length;
            if (totalApps > 0) {
                updateElement('applicationsUp', 0);
                updateElement('applicationsDown', totalApps);

                // Update progress bar
                const progressBar = document.querySelector('.bg-green-500.h-2');
                if (progressBar) {
                    progressBar.style.width = '0%';
                }
            }
        }

        /**
         * Update application statuses in the list
         */
        function updateApplicationStatuses(applications, serverNowIso) {
            applications.forEach(app => {
                // Debug: Log each app's status
                console.log(`Updating ${app.name}: status=${app.status}, response_time=${app.response_time}`);
                
                // Find the application card
                const appCard = document.querySelector(`[data-app-id="${app.id}"]`);

                if (appCard) {
                    // Update status dot (case-insensitive comparison)
                    const statusDot = appCard.querySelector('.status-dot');
                    if (statusDot) {
                        const isUp = app.status && app.status.toLowerCase() === 'up';
                        statusDot.className =
                            `status-dot w-2.5 h-2.5 rounded-full ${isUp ? 'bg-green-500' : 'bg-red-500'}`;
                        if (app.is_stale) {
                            statusDot.classList.add('animate-pulse');
                            statusDot.title = app.stale_message || 'Status may be outdated';
                        } else {
                            statusDot.classList.remove('animate-pulse');
                        }
                    }

                    // Update status badge (case-insensitive comparison)
                    const statusBadge = appCard.querySelector('.status-badge');
                    if (statusBadge) {
                        const isUp = app.status && app.status.toLowerCase() === 'up';
                        statusBadge.className =
                            `status-badge px-2 py-0.5 rounded-full text-xs font-medium ${isUp ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
                        statusBadge.textContent = app.status ? app.status.toUpperCase() : 'UNKNOWN';
                        if (app.is_stale) {
                            statusBadge.classList.add('opacity-75');
                            statusBadge.title = app.stale_message || 'Status may be outdated';
                        } else {
                            statusBadge.classList.remove('opacity-75');
                        }
                    }

                    // Update response time
                    const responseTimeEl = appCard.querySelector('[data-response-time]');
                    if (responseTimeEl) {
                        responseTimeEl.textContent = app.response_time ? `${app.response_time}ms` : '—';
                    }

                    const lastCheckedEl = appCard.querySelector('[data-last-checked]');
                    if (lastCheckedEl) {
                        if (app.last_checked_at) {
                            const date = new Date(app.last_checked_at);
                            const now = serverNowIso ? new Date(serverNowIso) : new Date();
                            const diffMs = now - date;
                            const diffMins = Math.floor(diffMs / 60000);
                            const diffHours = Math.floor(diffMs / 3600000);
                            const diffDays = Math.floor(diffMs / 86400000);
                            
                            let displayText;
                            if (diffMins < 1) {
                                displayText = 'Just now';
                            } else if (diffMins < 60) {
                                displayText = `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
                            } else if (diffHours < 24) {
                                displayText = `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
                            } else if (diffDays < 7) {
                                displayText = `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
                            } else {
                                displayText = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            }
                            
                            // Update text and preserve title attribute
                            const span = lastCheckedEl.querySelector('span') || lastCheckedEl;
                            if (span === lastCheckedEl) {
                                lastCheckedEl.textContent = displayText;
                                lastCheckedEl.title = date.toLocaleString() + ' ({{ config("app.timezone") }})';
                            } else {
                                span.textContent = displayText;
                                span.title = date.toLocaleString() + ' ({{ config("app.timezone") }})';
                            }
                        } else {
                            // No last checked time - show "Never"
                            const span = lastCheckedEl.querySelector('span');
                            if (span) {
                                span.textContent = 'Never';
                                span.removeAttribute('title');
                            } else {
                                lastCheckedEl.textContent = 'Never';
                            }
                        }
                    }
                }
            });
        }

        /**
         * Show stale indicator for specific application
         */
        function showStaleIndicator(appId) {
            const indicator = document.getElementById(`stale-indicator-${appId}`);
            if (!indicator) {
                // Could add a visual indicator here if needed
            }
        }

        /**
         * Hide stale indicator for specific application
         */
        function hideStaleIndicator(appId) {
            const indicator = document.getElementById(`stale-indicator-${appId}`);
            if (indicator) {
                indicator.remove();
            }
        }

        /**
         * Update recent incidents table
         */
        function updateRecentIncidents(incidents) {
            const tbody = document.querySelector('table tbody');
            if (!tbody) return;

            tbody.innerHTML = incidents.map(incident => {
                const statusClass = incident.status === 'resolved' ?
                    'bg-green-100 text-green-800' :
                    'bg-red-100 text-red-800';
                const statusText = incident.status === 'resolved' ? 'Resolved' : 'Active';
                const duration = incident.resolved_at && incident.duration ?
                    incident.duration + ' minutes' :
                    (incident.status === 'active' ? 'Ongoing' : 'N/A');

                return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${incident.application_name}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">${incident.started_at}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                ${statusText}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${duration}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        /**
         * Helper to update element text content
         */
        function updateElement(id, text) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = text;
            }
        }

        /**
         * Get availability color class
         */
        function getAvailabilityColorClass(color) {
            const colors = {
                'green': 'bg-green-500',
                'yellow': 'bg-yellow-500',
                'red': 'bg-red-500'
            };
            return colors[color] || 'bg-gray-500';
        }

        /**
         * Update clock display
         */
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            const timezone = '{{ config('app.timezone') }}';

            const clockElement = document.querySelector('[data-clock]');
            if (clockElement) {
                clockElement.textContent = `Last Updated: ${timeString} (${timezone})`;
            }
        }

        /**
         * Update the uptime trend chart with new data
         */
        function updateUptimeChart(chartData) {
            if (!uptimeChart) {
                console.warn('Uptime chart not initialized');
                return;
            }

            if (!chartData || !chartData.labels || !chartData.apps) {
                console.warn('Invalid chart data received');
                return;
            }

            try {
                console.log('Updating uptime chart with new data');
                const colors = ['#27ae60', '#3498db', '#9b59b6', '#e74c3c', '#f39c12', '#1abc9c', '#34495e'];
                const datasets = [];
                let colorIndex = 0;

                // Build new datasets from updated data
                for (const [appName, data] of Object.entries(chartData.apps)) {
                    if (data && data.uptime && Array.isArray(data.uptime)) {
                        datasets.push({
                            label: `${appName} - Uptime %`,
                            data: data.uptime,
                            borderColor: colors[colorIndex % colors.length],
                            backgroundColor: colors[colorIndex % colors.length] + '20',
                            tension: 0.4,
                            fill: true
                        });
                        colorIndex++;
                    }
                }

                // Update chart data
                if (datasets.length > 0) {
                    uptimeChart.data.labels = chartData.labels;
                    uptimeChart.data.datasets = datasets;
                    uptimeChart.update('none'); // 'none' mode = no animation for smoother updates
                    console.log('Uptime chart updated successfully');
                } else {
                    console.warn('No datasets to update chart with');
                }
            } catch (error) {
                console.error('Error updating uptime chart:', error);
            }
        }

        /**
         * Update individual application charts with new data
         */
        function updateApplicationCharts(chartData) {
            if (!chartData || !chartData.apps) {
                console.warn('No chart data for application charts');
                return;
            }

            // Get applications from the page
            const applications = @json($applications ?? []);

            let updatedCount = 0;
            applications.forEach(function(app) {
                const chart = appCharts[app.id];
                if (!chart) {
                    console.warn('Chart not found for app:', app.name);
                    return;
                }

                const appData = chartData.apps[app.name];
                if (!appData || !appData.uptime || !appData.downtime) {
                    console.warn('No data for app chart:', app.name);
                    return;
                }

                try {
                    // Update chart data
                    chart.data.labels = chartData.labels;
                    if (chart.data.datasets && chart.data.datasets.length >= 2) {
                        chart.data.datasets[0].data = appData.uptime;
                        chart.data.datasets[1].data = appData.downtime;
                        chart.update('none'); // No animation for smoother updates
                        updatedCount++;
                    }
                } catch (error) {
                    console.error('Error updating chart for ' + app.name + ':', error);
                }
            });

            if (updatedCount > 0) {
                console.log('Updated ' + updatedCount + ' application chart(s)');
            }
        }
        
        // Auto-reload page every 10 seconds
        setTimeout(function() {
            window.location.reload();
        },10000); // 10 seconds = 10000 milliseconds
    </script>
    </body>

</html>
