@extends('layouts.app')

@section('page_title', 'Uptime Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Uptime Reports</h1>
            <p class="text-gray-600 mt-1">View uptime and availability metrics</p>
        </div>
        <!-- Real-time Clock -->
        <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-clock text-green-600"></i>
                <div>
                    <div class="text-xs text-green-600 font-medium">Current Time</div>
                    <div class="text-sm font-bold text-green-800" id="realtimeClock">
                        {{ now()->format('M d, Y H:i:s') }}
                    </div>
                    <div class="text-xs text-green-600">{{ config('app.timezone') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Last Updated Indicator -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-600"></i>
            <span class="text-sm text-blue-800">
                <strong>Report Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                <span class="mx-2">|</span>
                <strong>Last Updated:</strong> <span id="lastUpdated">{{ now()->format('M d, Y H:i:s') }}</span>
                <span class="text-blue-600">({{ config('app.timezone') }})</span>
            </span>
        </div>
        <button onclick="window.location.reload()" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
            <i class="fas fa-redo"></i> Refresh
        </button>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="flex gap-4 items-end flex-wrap">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    value="{{ $startDate }}">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    value="{{ $endDate }}">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Period Type</label>
                <select name="period_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="daily" {{ $periodType === 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ $periodType === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ $periodType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ route('uptime.generate', ['period_type' => $periodType, 'date' => $endDate]) }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-sync"></i> Generate Reports
            </a>
            <a href="{{ route('uptime.download-pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'period_type' => $periodType]) }}" 
               target="_blank"
               class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Report Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total Checks</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Successful</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Failed</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Downtime</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Avg Response</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Uptime %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reports as $report)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">
                                {{ $report['application']->name ?? 'N/A' }}
                                @if(isset($report['saved']) && $report['saved'])
                                    <span class="ml-2 text-xs text-green-600" title="Report saved in database">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if(isset($report['report_date']))
                                    {{ \Carbon\Carbon::parse($report['report_date'])->format('M d, Y') }}
                                    @if(isset($report['updated_at']))
                                        <div class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($report['updated_at'])->format('H:i:s') }}
                                        </div>
                                    @endif
                                @else
                                    {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $report['total_checks'] ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-green-600 font-medium">{{ $report['successful_checks'] ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-red-600 font-medium">{{ $report['failed_checks'] ?? ($report['incident_count'] ?? 0) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $report['total_downtime_minutes'] ?? 0 }} min
                                @if(isset($report['total_downtime_minutes']) && $report['total_downtime_minutes'] > 0)
                                    <span class="text-xs text-gray-500">({{ round($report['total_downtime_minutes'] / 60, 1) }}h)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if(isset($report['avg_response_time']))
                                    {{ $report['avg_response_time'] }}ms
                                    @if(isset($report['min_response_time']) && isset($report['max_response_time']))
                                        <span class="text-xs text-gray-500">({{ $report['min_response_time'] }}-{{ $report['max_response_time'] }})</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold">
                                @php
                                    $uptime = $report['uptime_percentage'] ?? 0;
                                    $colorClass = ($uptime >= 99.5) ? 'bg-green-100 text-green-800' : (($uptime >= 95) ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm {{ $colorClass }}">
                                    {{ number_format($uptime, 2) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <i class="fas fa-chart-bar text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-600">No reports available for this period</p>
                                <p class="text-sm text-gray-500 mt-2">Click "Generate Reports" to create reports for this period</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time clock update
    const clockElement = document.getElementById('realtimeClock');
    if (clockElement) {
        function updateClock() {
            const now = new Date();
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: false
            };
            const timeString = now.toLocaleString('en-US', options).replace(',', '');
            clockElement.textContent = timeString;
        }
        
        // Update immediately
        updateClock();
        
        // Update every second
        setInterval(updateClock, 1000);
    }
    
    // Update "Last Updated" timestamp
    const lastUpdatedElement = document.getElementById('lastUpdated');
    if (lastUpdatedElement) {
        function updateLastUpdated() {
            const now = new Date();
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: false
            };
            const timeString = now.toLocaleString('en-US', options).replace(',', '');
            lastUpdatedElement.textContent = timeString;
        }
        
        // Update every second
        setInterval(updateLastUpdated, 1000);
    }
});
</script>
@endpush
@endsection
