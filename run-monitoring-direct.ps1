# Direct Monitoring Runner for Windows
# This script runs monitoring checks directly every minute
# Bypasses Laravel scheduler timing issues

Write-Host "Starting Direct Monitoring Runner..." -ForegroundColor Green
Write-Host "This will run monitoring checks every minute" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

# Function to wait until the start of the next minute (at :00 seconds)
function Wait-ForNextMinute {
    $now = Get-Date
    $seconds = $now.Second
    $milliseconds = $now.Millisecond
    
    # Calculate milliseconds until next :00 second
    $waitMilliseconds = (60 - $seconds) * 1000 - $milliseconds
    
    if ($waitMilliseconds -gt 0) {
        $waitSeconds = [math]::Round($waitMilliseconds / 1000, 1)
        $nextMinute = $now.AddSeconds($waitSeconds)
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
                Start-Sleep -Milliseconds 50
            }
        } while ((Get-Date).Second -ne 0)
    }
}

# Wait for the start of the next minute before starting
Wait-ForNextMinute

Write-Host "Starting monitoring loop..." -ForegroundColor Green
Write-Host ""

while ($true) {
    # Wait until exactly :00 seconds
    do {
        $now = Get-Date
        $seconds = $now.Second
        if ($seconds -ne 0) {
            Start-Sleep -Milliseconds 50
        }
    } while ((Get-Date).Second -ne 0)
    
    $now = Get-Date
    $timestamp = $now.ToString("yyyy-MM-dd HH:mm:ss")
    
    Write-Host "[$timestamp] Running monitoring checks..." -ForegroundColor Cyan
    
    # Run the monitoring command directly (bypasses scheduler)
    $output = php artisan monitoring:run-checks 2>&1
    
    # Show output
    if ($output) {
        Write-Host $output
    } else {
        Write-Host "  Monitoring check completed" -ForegroundColor Green
    }
    
    Write-Host ""
    
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

