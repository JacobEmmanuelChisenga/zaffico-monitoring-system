$projectPath = 'C:\Users\chisengaj\Desktop\LaravelProjects\zaffico-monitoring-system'
$phpCli = 'C:\xampp\php\php.exe'
$taskName = 'Zaffico-Monitoring-Scheduler'
$taskDescription = 'Runs Laravel monitoring checks every minute'

# Trigger: every 1 minute (FOREVER)
$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration (New-TimeSpan -Days 365)

# Action
$action = New-ScheduledTaskAction `
    -Execute $phpCli `
    -Argument 'artisan monitoring:run-checks --no-interaction' `
    -WorkingDirectory $projectPath

# Settings: Increased execution time limit, allow restart on failure
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

# Start immediately
Start-ScheduledTask -TaskName $taskName

Write-Host "✅ Task created and started: $taskName"
Get-ScheduledTask -TaskName $taskName
