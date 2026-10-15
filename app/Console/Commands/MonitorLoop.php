<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorLoop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:loop {--interval=15 : Interval in seconds between checks (default: 15 for faster detection)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run monitoring checks in a continuous loop (simpler than scheduler)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $interval = (int) $this->option('interval');
        
        $this->info("Starting continuous monitoring loop (every {$interval} seconds)");
        $this->info("Press Ctrl+C to stop");
        $this->newLine();
        
        // Run monitoring checks immediately on start
        $this->runMonitoringChecks();
        
        // Then run every N seconds
        while (true) {
            sleep($interval);
            $this->runMonitoringChecks();
        }
        
        return 0;
    }
    
    /**
     * Run the monitoring checks command
     */
    private function runMonitoringChecks(): void
    {
        $this->info("[" . now()->format('Y-m-d H:i:s') . "] Running monitoring checks...");
        
        // Call the monitoring command
        $this->call('monitoring:run-checks');
        
        $this->newLine();
    }
}

