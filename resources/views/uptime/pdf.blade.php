<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Uptime Report - {{ $startDate }} to {{ $endDate }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #27ae60;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #27ae60;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #27ae60;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .uptime-excellent {
            color: #27ae60;
            font-weight: bold;
        }
        .uptime-good {
            color: #f39c12;
            font-weight: bold;
        }
        .uptime-poor {
            color: #e74c3c;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .summary {
            background-color: #f0f9f4;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary h2 {
            color: #27ae60;
            margin-top: 0;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Zaffico Monitoring System</h1>
        <h2>Uptime Report</h2>
        <p><strong>Period:</strong> {{ ucfirst($periodType) }} Report</p>
        <p><strong>Date Range:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
        <p><strong>Generated:</strong> {{ $generatedAt->format('M d, Y H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>Summary</h2>
        <p><strong>Total Applications:</strong> {{ count($reports) }}</p>
        <p><strong>Average Uptime:</strong> {{ count($reports) > 0 ? number_format(collect($reports)->avg('uptime_percentage'), 2) : 0 }}%</p>
        <p><strong>Total Incidents:</strong> {{ collect($reports)->sum('incident_count') }}</p>
        <p><strong>Total Downtime:</strong> {{ round(collect($reports)->sum('total_downtime_minutes') / 60, 2) }} hours</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Application</th>
                <th>Uptime %</th>
                <th>Total Checks</th>
                <th>Successful</th>
                <th>Failed</th>
                <th>Downtime</th>
                <th>Incidents</th>
                <th>Avg Response Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $report)
                <tr>
                    <td><strong>{{ $report['application']->name }}</strong></td>
                    <td class="{{ ($report['uptime_percentage'] ?? 0) >= 99.5 ? 'uptime-excellent' : (($report['uptime_percentage'] ?? 0) >= 95 ? 'uptime-good' : 'uptime-poor') }}">
                        {{ number_format($report['uptime_percentage'] ?? 0, 2) }}%
                    </td>
                    <td>{{ $report['total_checks'] ?? 'N/A' }}</td>
                    <td style="color: #27ae60;">{{ $report['successful_checks'] ?? 'N/A' }}</td>
                    <td style="color: #e74c3c;">{{ $report['failed_checks'] ?? ($report['incident_count'] ?? 0) }}</td>
                    <td>{{ $report['total_downtime_minutes'] ?? 0 }} min ({{ round(($report['total_downtime_minutes'] ?? 0) / 60, 2) }}h)</td>
                    <td>{{ $report['incident_count'] ?? 0 }}</td>
                    <td>
                        @if(isset($report['avg_response_time']))
                            {{ $report['avg_response_time'] }}ms
                            @if(isset($report['min_response_time']) && isset($report['max_response_time']))
                                <br><small>({{ $report['min_response_time'] }}-{{ $report['max_response_time'] }})</small>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #999;">
                        No data available for this period
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by Zaffico Monitoring System</p>
        <p>For questions or support, please contact your system administrator</p>
    </div>
</body>
</html>

