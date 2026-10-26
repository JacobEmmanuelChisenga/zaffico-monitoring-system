# ============================================
# ZAFFICO Monitoring - Complete Setup Script
# ============================================
# This script sets up all Windows Task Scheduler tasks
# for the ZAFFICO Monitoring System
# ============================================

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "ZAFFICO Monitoring System - Complete Setup" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$basePath = Split-Path -Parent $scriptPath

# Change to scripts directory
Set-Location $scriptPath

Write-Host "Step 1: Setting up startup tasks (PHP-CGI and Nginx)..." -ForegroundColor Yellow
& "$scriptPath\setup-startup-task.ps1"
if (-not $?) {
    Write-Host "ERROR: Failed to setup startup tasks" -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "Step 2: Setting up monitoring scheduler task..." -ForegroundColor Yellow
& "$scriptPath\setup-task-scheduler.ps1"
if (-not $?) {
    Write-Host "ERROR: Failed to setup scheduler task" -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "Step 3: Setting up watchdog task..." -ForegroundColor Yellow
& "$scriptPath\setup-watchdog-task.ps1"
if (-not $?) {
    Write-Host "ERROR: Failed to setup watchdog task" -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "============================================" -ForegroundColor Green
Write-Host "✅ All tasks have been set up successfully!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Summary of configured tasks:" -ForegroundColor Cyan
Write-Host "  1. ZAFFICO - Start PHP-CGI (runs at startup)" -ForegroundColor White
Write-Host "  2. ZAFFICO - Start Nginx (runs at startup)" -ForegroundColor White
Write-Host "  3. Zaffico-Monitoring-Scheduler (runs every minute)" -ForegroundColor White
Write-Host "  4. ZAFFICO - Process Watchdog (runs continuously)" -ForegroundColor White
Write-Host ""
Write-Host "To verify tasks are running:" -ForegroundColor Yellow
Write-Host "  Get-ScheduledTask | Where-Object { `$_.TaskName -like '*ZAFFICO*' -or `$_.TaskName -like '*Zaffico*' }" -ForegroundColor Gray
Write-Host ""
Write-Host "To view watchdog logs:" -ForegroundColor Yellow
Write-Host "  Get-Content $basePath\storage\logs\watchdog.log -Tail 50" -ForegroundColor Gray
Write-Host ""
