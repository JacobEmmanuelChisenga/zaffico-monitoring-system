# ============================================
# ZAFFICO Monitoring - Watchdog Task Setup
# ============================================
# Creates a Windows Task Scheduler task to run
# the watchdog script that monitors and restarts
# PHP-CGI and Nginx if they die
# ============================================

$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$basePath = Split-Path -Parent $scriptDir
$watchdogScript = Join-Path $scriptDir "watchdog.ps1"
$taskName = "ZAFFICO - Process Watchdog"

Write-Host "Setting up ZAFFICO Monitoring Watchdog Task..." -ForegroundColor Cyan

# Verify script exists
if (-not (Test-Path $watchdogScript)) {
    Write-Host "ERROR: Watchdog script not found at: $watchdogScript" -ForegroundColor Red
    exit 1
}

# Remove existing task if it exists
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue

# Create trigger: At startup + every hour (as backup)
$startupTrigger = New-ScheduledTaskTrigger -AtStartup
$hourlyTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Days 365)

# Action: Run PowerShell script
$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-ExecutionPolicy Bypass -NoProfile -File `"$watchdogScript`"" `
    -WorkingDirectory $basePath

# Settings: Don't kill long-running processes, allow on battery, etc.
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1)

# Principal: Run as SYSTEM with highest privileges
$principal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

# Register the task
Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger @($startupTrigger, $hourlyTrigger) `
    -Settings $settings `
    -Principal $principal `
    -Description "Monitors and restarts PHP-CGI and Nginx processes if they die" `
    -Force

# Start the task immediately
Start-ScheduledTask -TaskName $taskName

Write-Host "✅ Watchdog task created and started: $taskName" -ForegroundColor Green
Write-Host ""
Write-Host "Task Details:" -ForegroundColor Cyan
Get-ScheduledTask -TaskName $taskName | Format-List
Write-Host ""
Write-Host "The watchdog will:" -ForegroundColor Yellow
Write-Host "  - Start automatically at system boot" -ForegroundColor Yellow
Write-Host "  - Monitor PHP-CGI (ports 9000-9003) and Nginx every 15 seconds" -ForegroundColor Yellow
Write-Host "  - Automatically restart processes if they die" -ForegroundColor Yellow
Write-Host "  - Log all activities to: storage\logs\watchdog.log" -ForegroundColor Yellow
