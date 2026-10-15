<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Applications;
use App\Models\UptimeReport;
use App\Models\DowntimeIncident;
use App\Models\MaintenanceWindow;
use App\Services\UptimeReportService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class UptimeReportController extends Controller
{
    protected $uptimeReportService;

    public function __construct(UptimeReportService $uptimeReportService)
    {
        $this->uptimeReportService = $uptimeReportService;
    }

    public function index(Request $request)
    {
        $applications = Applications::where('is_active', true)->get();

        // Get filter dates or default to last 30 days
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $periodType = $request->input('period_type', 'daily');

        // Auto-generate reports for today if they don't exist (for daily reports)
        if ($periodType === 'daily' && $endDate === Carbon::now()->toDateString()) {
            $todayReport = UptimeReport::where('report_date', Carbon::now()->toDateString())
                ->where('period_type', 'daily')
                ->first();
            
            if (!$todayReport) {
                // Generate today's reports if they don't exist
                $this->uptimeReportService->generateReportsForAllApplications('daily', Carbon::now());
            }
        }

        // Get saved reports from database
        $savedReports = UptimeReport::whereBetween('report_date', [$startDate, $endDate])
            ->where('period_type', $periodType)
            ->with('application')
            ->get()
            ->groupBy('application_id');

        $reports = [];

        foreach ($applications as $app) {
            // Try to get saved report, otherwise calculate on the fly
            $savedReport = $savedReports->get($app->id)?->first();
            
            if ($savedReport) {
                $reports[] = [
                    'application' => $app,
                    'uptime_percentage' => $savedReport->uptime_percentage,
                    'total_downtime_minutes' => $savedReport->total_downtime_minutes,
                    'incident_count' => $savedReport->incident_count,
                    'total_checks' => $savedReport->total_checks,
                    'successful_checks' => $savedReport->successful_checks,
                    'failed_checks' => $savedReport->failed_checks,
                    'avg_response_time' => $savedReport->avg_response_time,
                    'min_response_time' => $savedReport->min_response_time,
                    'max_response_time' => $savedReport->max_response_time,
                    'report_date' => $savedReport->report_date,
                    'updated_at' => $savedReport->updated_at,
                    'saved' => true,
                ];
            } else {
                // Calculate on the fly if no saved report
                $periodStart = Carbon::parse($startDate);
                $periodEnd = Carbon::parse($endDate)->endOfDay();
                $totalSeconds = $periodEnd->diffInSeconds($periodStart);

                // Calculate total downtime
                $downtimes = DowntimeIncident::where('application_id', $app->id)
                    ->where(function ($q) use ($periodStart, $periodEnd) {
                        $q->whereBetween('started_at', [$periodStart, $periodEnd])
                          ->orWhereBetween('resolved_at', [$periodStart, $periodEnd])
                          ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                              $q2->where('started_at', '<', $periodStart)
                                 ->where(function ($q3) use ($periodEnd) {
                                     $q3->whereNull('resolved_at')
                                        ->orWhere('resolved_at', '>', $periodEnd);
                                 });
                          });
                    })->get();

                $downtimeSeconds = 0;

                foreach ($downtimes as $dt) {
                    $start = Carbon::parse($dt->started_at)->max($periodStart);
                    $end = $dt->resolved_at ? Carbon::parse($dt->resolved_at)->min($periodEnd) : $periodEnd;
                    $downtimeSeconds += $end->diffInSeconds($start);
                }

                // Exclude maintenance windows from downtime
                $maintenanceWindows = MaintenanceWindow::where('application_id', $app->id)
                    ->where('disable_alerts', true)
                    ->where(function ($q) use ($periodStart, $periodEnd) {
                        $q->whereBetween('start_time', [$periodStart, $periodEnd])
                          ->orWhereBetween('end_time', [$periodStart, $periodEnd])
                          ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                              $q2->where('start_time', '<', $periodStart)
                                 ->where('end_time', '>', $periodEnd);
                          });
                    })->get();

                foreach ($maintenanceWindows as $mw) {
                    $start = Carbon::parse($mw->start_time)->max($periodStart);
                    $end = Carbon::parse($mw->end_time)->min($periodEnd);
                    $downtimeSeconds -= $end->diffInSeconds($start);
                }

                $downtimeSeconds = max(0, $downtimeSeconds);
                $uptimeSeconds = max($totalSeconds - $downtimeSeconds, 0);
                $uptimePercentage = ($totalSeconds > 0) ? round(($uptimeSeconds / $totalSeconds) * 100, 2) : 100;

                $reports[] = [
                    'application' => $app,
                    'uptime_percentage' => $uptimePercentage,
                    'total_downtime_minutes' => round($downtimeSeconds / 60),
                    'incident_count' => $downtimes->count(),
                    'saved' => false,
                ];
            }
        }

        return view('uptime.index', compact('reports', 'startDate', 'endDate', 'periodType'));
    }

    /**
     * Generate and save reports for the selected period
     */
    public function generate(Request $request)
    {
        $periodType = $request->input('period_type', 'daily');
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        $this->uptimeReportService->generateReportsForAllApplications($periodType, $date);

        return redirect()->route('uptime.index', [
            'period_type' => $periodType,
            'start_date' => $date->copy()->startOfDay()->toDateString(),
            'end_date' => $date->copy()->endOfDay()->toDateString(),
        ])->with('success', 'Uptime reports generated successfully');
    }

    /**
     * Download PDF report
     */
    public function downloadPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $periodType = $request->input('period_type', 'daily');
        $applicationId = $request->input('application_id');

        $applications = Applications::where('is_active', true);
        if ($applicationId) {
            $applications->where('id', $applicationId);
        }
        $applications = $applications->get();

        // Generate reports for the date range if they don't exist
        // For daily reports, generate for each day in the range
        if ($periodType === 'daily') {
            $currentDate = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            while ($currentDate->lte($end)) {
                $existingReport = UptimeReport::where('report_date', $currentDate->toDateString())
                    ->where('period_type', 'daily')
                    ->whereIn('application_id', $applications->pluck('id'))
                    ->exists();
                
                if (!$existingReport) {
                    $this->uptimeReportService->generateReportsForAllApplications('daily', $currentDate);
                }
                
                $currentDate->addDay();
            }
        }

        // Get saved reports
        $savedReports = UptimeReport::whereBetween('report_date', [$startDate, $endDate])
            ->where('period_type', $periodType)
            ->with('application')
            ->when($applicationId, function ($q) use ($applicationId) {
                $q->where('application_id', $applicationId);
            })
            ->get()
            ->groupBy('application_id');

        $reports = [];
        foreach ($applications as $app) {
            // Get all reports for this application in the date range
            $appReports = $savedReports->get($app->id);
            
            if ($appReports && $appReports->isNotEmpty()) {
                // Aggregate data from all reports in the range
                $totalChecks = $appReports->sum('total_checks');
                $successfulChecks = $appReports->sum('successful_checks');
                $failedChecks = $appReports->sum('failed_checks');
                $totalDowntime = $appReports->sum('total_downtime_minutes');
                $totalIncidents = $appReports->sum('incident_count');
                
                // Calculate average uptime
                $avgUptime = $appReports->avg('uptime_percentage');
                
                // Calculate average response time
                $responseTimes = $appReports->whereNotNull('avg_response_time')->pluck('avg_response_time')->filter();
                $avgResponseTime = $responseTimes->isNotEmpty() ? (int) round($responseTimes->avg()) : null;
                $minResponseTime = $appReports->whereNotNull('min_response_time')->min('min_response_time');
                $maxResponseTime = $appReports->whereNotNull('max_response_time')->max('max_response_time');
                
                $reports[] = [
                    'application' => $app,
                    'uptime_percentage' => round($avgUptime, 2),
                    'total_downtime_minutes' => $totalDowntime,
                    'incident_count' => $totalIncidents,
                    'total_checks' => $totalChecks,
                    'successful_checks' => $successfulChecks,
                    'failed_checks' => $failedChecks,
                    'avg_response_time' => $avgResponseTime,
                    'min_response_time' => $minResponseTime ? (int) $minResponseTime : null,
                    'max_response_time' => $maxResponseTime ? (int) $maxResponseTime : null,
                ];
            }
        }

        $pdf = Pdf::loadView('uptime.pdf', [
            'reports' => $reports,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodType' => $periodType,
            'generatedAt' => Carbon::now(),
        ]);

        $filename = 'uptime-report-' . $startDate . '-to-' . $endDate . '.pdf';
        
        return $pdf->download($filename);
    }

    public function show(string $id)
    {
        // Detailed report view
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
