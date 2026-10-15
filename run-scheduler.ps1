# Laravel Scheduler Runner for Windows
# This script runs the Laravel scheduler every minute
# It synchronizes with the minute boundary to ensure commands run on time

Write-Host "Starting Laravel Scheduler..." -ForegroundColor Green
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

# Function to wait until the start of the next minute (at :00 seconds)
function Wait-ForNextMinute {
    $now = Get-Date
    $seconds = $now.Second
    $milliseconds = $now.Millisecond
    
    # Calculate milliseconds until next :00 second
    # Subtract 500ms to account for execution time, so we run slightly before :00
    $waitMilliseconds = (60 - $seconds) * 1000 - $milliseconds - 500
    
    if ($waitMilliseconds -gt 0) {
        $waitSeconds = [math]::Round($waitMilliseconds / 1000, 1)
        $nextMinute = $now.AddSeconds($waitSeconds + 0.5)
        Write-Host "Waiting $waitSeconds seconds until $($nextMinute.ToString('HH:mm:ss'))..." -ForegroundColor Gray
        
        # Wait with small increments to be more precise
        while ($waitMilliseconds -gt 0) {
            if ($waitMilliseconds -gt 1000) {
                Start-Sleep -Milliseconds 1000
                $waitMilliseconds -= 1000
            } else {
                Start-Sleep -Milliseconds $waitMilliseconds
                $waitMilliseconds = 0
            }
        }
        
        # Fine-tune: wait until exactly :00 seconds
        do {
            $current = Get-Date
            $currentSeconds = $current.Second
            if ($currentSeconds -ne 0) {
                Start-Sleep -Milliseconds 100
            }
        } while ((Get-Date).Second -ne 0)
    }
}

# Wait for the start of the next minute before starting
Wait-ForNextMinute

while ($true) {
    # Wait until exactly :00 seconds
    do {
        $now = Get-Date
        $seconds = $now.Second
        if ($seconds -ne 0) {
            Start-Sleep -Milliseconds 100
        }
    } while ((Get-Date).Second -ne 0)
    
    $now = Get-Date
    $timestamp = $now.ToString("yyyy-MM-dd HH:mm:ss")
    $seconds = $now.Second
    
    Write-Host "[$timestamp] Running scheduled tasks (at :$($seconds.ToString('00')) seconds)..." -ForegroundColor Cyan
    
    # Run the scheduler immediately at :00
    $output = php artisan schedule:run 2>&1
    
    # Check output
    if ($output -match "No scheduled commands are ready to run") {
        Write-Host "  WARNING: No commands due at this time" -ForegroundColor Yellow
        Write-Host "  INFO: This might be a timing issue. Next run will be at $($now.AddMinutes(1).ToString('HH:mm:00'))" -ForegroundColor Gray
    } elseif ($output -match "monitoring:run-checks" -or $output -match "Scheduler executed" -or $output -match "Health check") {
        Write-Host "  SUCCESS: Command executed successfully" -ForegroundColor Green
        Write-Host $output
    } else {
        Write-Host $output
    }
    
    # Wait until the next :00 second (approximately 60 seconds, but we'll sync precisely)
    Write-Host "  Waiting until next minute..." -ForegroundColor Gray
    
    # Wait 59 seconds, then fine-tune to :00
    Start-Sleep -Seconds 59
    
    # Fine-tune: wait until exactly :00 seconds
    do {
        $current = Get-Date
        $currentSeconds = $current.Second
        if ($currentSeconds -ne 0) {
            Start-Sleep -Milliseconds 50
        }
    } while ((Get-Date).Second -ne 0)
}

