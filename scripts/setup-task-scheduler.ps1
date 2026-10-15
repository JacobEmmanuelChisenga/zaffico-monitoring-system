# Prefer the server path; fall back to this PC's Desktop copy.
$projectPath = 'C:\Users\Administrator\Documents\zaffico-monitoring-system'
if (-not (Test-Path (Join-Path $projectPath 'artisan'))) {
    $projectPath = 'C:\Users\chisengaj\Desktop\LaravelProjects\zaffico-monitoring-system'
}
$phpCli = 'C:\xampp\php\php.exe'
$taskName = 'Zaffico-Monitoring-Scheduler'
$taskDescription = 'Runs Laravel monitoring checks every minute'

if (-not (Test-Path (Join-Path $projectPath 'artisan'))) {
    throw "artisan not found under $projectPath"
}
if (-not (Test-Path $phpCli)) {
    throw "php.exe not found at $phpCli"
}

# Trigger: every 1 minute for a long period (Task Scheduler has no true "forever" on -Once)
$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration (New-TimeSpan -Days 3650)

$action = New-ScheduledTaskAction `
    -Execute $phpCli `
    -Argument 'artisan monitoring:run-checks --no-interaction' `
    -WorkingDirectory $projectPath

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 10) `
    -MultipleInstances IgnoreNew `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1)

Register-ScheduledTask `
    -TaskName $taskName `
    -Trigger $trigger `
    -Action $action `
    -Settings $settings `
    -Description $taskDescription `
    -RunLevel Highest `
    -Force

Start-ScheduledTask -TaskName $taskName

Write-Host "Task created and started: $taskName"
Write-Host "Project path: $projectPath"
Get-ScheduledTask -TaskName $taskName | Format-List TaskName, State
Get-ScheduledTaskInfo -TaskName $taskName | Format-List LastRunTime, LastTaskResult, NextRunTime
