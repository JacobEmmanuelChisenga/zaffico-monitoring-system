# ============================================
# ZAFFICO Monitoring - Process Watchdog
# ============================================
# This script monitors PHP-CGI and Nginx processes
# and restarts them if they die
# ============================================

param(
    [int]$CheckInterval = 15,  # Check every 15 seconds
    [string]$LogFile = "storage\logs\watchdog.log"
)

$ErrorActionPreference = "Continue"
$script:BasePath = Split-Path -Parent $MyInvocation.MyCommand.Path
$script:ProjectPath = Split-Path -Parent $script:BasePath
$script:LogPath = Join-Path $script:ProjectPath $LogFile
$script:PhpPorts = @(9000, 9001, 9002, 9003)

# Ensure log directory exists
$logDir = Split-Path -Parent $script:LogPath
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    Add-Content -Path $script:LogPath -Value $logMessage
    Write-Host $logMessage
}

function Test-ProcessRunning {
    param([string]$ProcessName, [int]$Port = 0)

    $process = Get-Process -Name $ProcessName -ErrorAction SilentlyContinue
    if (-not $process) {
        return $false
    }

    if ($Port -gt 0) {
        return (Test-PortListening -Port $Port)
    }

    return $true
}

function Test-PortListening {
    param([int]$Port)

    $lines = netstat -ano | Select-String "LISTENING"
    foreach ($line in $lines) {
        # Match :9000 with word boundary so :9000 does not match :90001
        if ($line.Line -match ":$Port\s") {
            return $true
        }
    }
    return $false
}

function Get-MissingPhpPorts {
    # Build a plain PowerShell array of ints (never nest an int[] inside another array).
    $missing = @()
    foreach ($port in $script:PhpPorts) {
        if (-not (Test-PortListening -Port $port)) {
            $missing += $port
        }
    }
    return $missing
}

function Format-PortList {
    param([object]$Ports)

    if ($null -eq $Ports) {
        return "(none)"
    }

    $flat = @()
    foreach ($item in @($Ports)) {
        if ($item -is [Array]) {
            foreach ($inner in $item) {
                $flat += [string]$inner
            }
        } else {
            $flat += [string]$item
        }
    }

    if ($flat.Count -eq 0) {
        return "(none)"
    }

    return ($flat -join ", ")
}

function Start-PHPCGI {
    $phpBat = Join-Path $script:BasePath "start-php-cgi.bat"
    if (-not (Test-Path $phpBat)) {
        Write-Log "ERROR: start-php-cgi.bat not found at $phpBat" "ERROR"
        return $null
    }

    Write-Log "Starting PHP-CGI..." "WATCHDOG"
    # Run through cmd so the batch file environment (PHP_FCGI_MAX_REQUESTS) is applied.
    $process = Start-Process -FilePath "cmd.exe" `
        -ArgumentList "/c `"$phpBat`"" `
        -WorkingDirectory $script:BasePath `
        -WindowStyle Hidden `
        -PassThru `
        -Wait
    Start-Sleep -Seconds 2
    return $process
}

function Start-Nginx {
    $nginxBat = Join-Path $script:BasePath "start-nginx.bat"
    if (-not (Test-Path $nginxBat)) {
        Write-Log "ERROR: start-nginx.bat not found at $nginxBat" "ERROR"
        return $null
    }

    Write-Log "Starting Nginx..." "WATCHDOG"
    $process = Start-Process -FilePath "cmd.exe" `
        -ArgumentList "/c `"$nginxBat`"" `
        -WorkingDirectory $script:BasePath `
        -WindowStyle Hidden `
        -PassThru `
        -Wait
    Start-Sleep -Seconds 2
    return $process
}

# Main watchdog loop
Write-Log "============================================" "INFO"
Write-Log "ZAFFICO Monitoring Watchdog Started" "INFO"
Write-Log "Check Interval: $CheckInterval seconds" "INFO"
Write-Log "PHP ports: $($script:PhpPorts -join ', ')" "INFO"
Write-Log "Log File: $script:LogPath" "INFO"
Write-Log "============================================" "INFO"

$phpRestartCount = 0
$nginxRestartCount = 0
$checkCount = 0

while ($true) {
    try {
        $checkCount++

        $missingPorts = @(Get-MissingPhpPorts)
        $phpRunning = ($missingPorts.Count -eq 0)

        if (-not $phpRunning) {
            Write-Log "PHP-CGI is not listening on port(s): $(Format-PortList $missingPorts). Restarting..." "WARNING"
            $phpRestartCount++
            Start-PHPCGI | Out-Null
            Start-Sleep -Seconds 3

            $missingPorts = @(Get-MissingPhpPorts)
            $phpRunning = ($missingPorts.Count -eq 0)
            if ($phpRunning) {
                Write-Log "PHP-CGI restarted successfully (Total restarts: $phpRestartCount)" "INFO"
            } else {
                Write-Log "FAILED to restart PHP-CGI on port(s): $(Format-PortList $missingPorts)" "ERROR"
                Write-Log "Check php-cgi.log and that C:\xampp\php\php-cgi.exe exists" "ERROR"
            }
        }

        $nginxRunning = Test-ProcessRunning -ProcessName "nginx"
        if (-not $nginxRunning) {
            Write-Log "Nginx is not running! Restarting..." "WARNING"
            $nginxRestartCount++
            Start-Nginx | Out-Null
            Start-Sleep -Seconds 3

            $nginxRunning = Test-ProcessRunning -ProcessName "nginx"
            if ($nginxRunning) {
                Write-Log "Nginx restarted successfully (Total restarts: $nginxRestartCount)" "INFO"
            } else {
                Write-Log "FAILED to restart Nginx! Check C:\nginx\logs\error.log" "ERROR"
            }
        }

        if ($checkCount % 20 -eq 0) {
            Write-Log "Watchdog check: PHP-CGI=$phpRunning, Nginx=$nginxRunning" "INFO"
        }

    } catch {
        Write-Log "Error in watchdog loop: $($_.Exception.Message)" "ERROR"
    }

    Start-Sleep -Seconds $CheckInterval
}
