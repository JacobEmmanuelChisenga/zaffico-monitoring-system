<?php

namespace App\Console\Commands;

use App\Services\UptimeReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateUptimeReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:generate-reports {--type=daily : Report type (daily, weekly, monthly)} {--date= : Specific date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate uptime reports for all active applications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        
        $this->info("Generating {$type} uptime reports for {$date->format('Y-m-d')}");
        Log::info("Generating {$type} uptime reports for {$date->format('Y-m-d')}");
        
        $service = new UptimeReportService();
        $service->generateReportsForAllApplications($type, $date);
        
        $this->info("✅ Uptime reports generated successfully");
        Log::info("✅ Uptime reports generated successfully");
        
        return 0;
    }
}

