Write-Host "Setting up ZAFFICO Monitoring startup tasks..." -ForegroundColor Cyan

$basePath = "C:\Users\chisengaj\Desktop\LaravelProjects\zaffico-monitoring-system\scripts"
$phpBat   = "$basePath\start-php-cgi.bat"
$nginxBat = "$basePath\start-nginx.bat"

# Settings: No execution time limit, allow restart on failure
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1)
    
$principal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

# -----------------------------
# PHP-CGI TASK
# -----------------------------
$phpTaskName = "ZAFFICO - Start PHP-CGI"

Unregister-ScheduledTask -TaskName $phpTaskName -Confirm:$false -ErrorAction SilentlyContinue

Register-ScheduledTask `
    -TaskName $phpTaskName `
    -Action (New-ScheduledTaskAction -Execute $phpBat -WorkingDirectory $basePath) `
    -Trigger (New-ScheduledTaskTrigger -AtStartup) `
    -Principal $principal `
    -Settings $settings `
    -Description "Starts PHP-CGI for ZAFFICO Monitoring System"

Write-Host "PHP-CGI startup task created." -ForegroundColor Green

# -----------------------------
# NGINX TASK
# -----------------------------
$nginxTaskName = "ZAFFICO - Start Nginx"

Unregister-ScheduledTask -TaskName $nginxTaskName -Confirm:$false -ErrorAction SilentlyContinue

Register-ScheduledTask `
    -TaskName $nginxTaskName `
    -Action (New-ScheduledTaskAction -Execute $nginxBat -WorkingDirectory $basePath) `
    -Trigger (New-ScheduledTaskTrigger -AtStartup) `
    -Principal $principal `
    -Settings $settings `
    -Description "Starts Nginx on port 8888 for ZAFFICO Monitoring"

Write-Host "Nginx startup task created." -ForegroundColor Green

Write-Host "ZAFFICO Monitoring System will start automatically at boot." -ForegroundColor Cyan
