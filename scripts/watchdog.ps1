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
    
    # Check by process name
    $process = Get-Process -Name $ProcessName -ErrorAction SilentlyContinue
    if ($process) {
        # If port specified, also verify it's listening
        if ($Port -gt 0) {
            $listening = netstat -ano | Select-String ":$Port\s" | Select-String "LISTENING"
            if ($listening) {
                return $true
            }
            return $false
        }
        return $true
    }
    return $false
}

function Test-PhpPortListening {
    param([int]$Port)
    $listening = netstat -ano | Select-String "127.0.0.1:$Port\s" | Select-String "LISTENING"
    return [bool]$listening
}

function Get-MissingPhpPorts {
    $missing = New-Object System.Collections.Generic.List[int]
    foreach ($port in $script:PhpPorts) {
        if (-not (Test-PhpPortListening -Port $port)) {
            $missing.Add($port)
        }
    }
    # Comma keeps an empty list from being dropped on return.
    return ,$missing.ToArray()
}

function Start-PHPCGI {
    $phpBat = Join-Path $script:BasePath "start-php-cgi.bat"
    if (Test-Path $phpBat) {
        Write-Log "Starting PHP-CGI..." "WATCHDOG"
        $process = Start-Process -FilePath $phpBat -WorkingDirectory $script:BasePath -WindowStyle Hidden -PassThru
        Start-Sleep -Seconds 3
        return $process
    } else {
        Write-Log "ERROR: start-php-cgi.bat not found at $phpBat" "ERROR"
        return $null
    }
}

function Start-Nginx {
    $nginxBat = Join-Path $script:BasePath "start-nginx.bat"
    if (Test-Path $nginxBat) {
        Write-Log "Starting Nginx..." "WATCHDOG"
        $process = Start-Process -FilePath $nginxBat -WorkingDirectory $script:BasePath -WindowStyle Hidden -PassThru
        Start-Sleep -Seconds 3
        return $process
    } else {
        Write-Log "ERROR: start-nginx.bat not found at $nginxBat" "ERROR"
        return $null
    }
}

# Main watchdog loop
Write-Log "============================================" "INFO"
Write-Log "ZAFFICO Monitoring Watchdog Started" "INFO"
Write-Log "Check Interval: $CheckInterval seconds" "INFO"
Write-Log "Log File: $script:LogPath" "INFO"
Write-Log "============================================" "INFO"

$phpRestartCount = 0
$nginxRestartCount = 0
$checkCount = 0

while ($true) {
    try {
        $checkCount++

        # Check every PHP-CGI port. One dead worker is restarted; the others keep serving.
        $missingPorts = @(Get-MissingPhpPorts)
        $phpRunning = ($missingPorts.Count -eq 0)
        if (-not $phpRunning) {
            Write-Log "PHP-CGI is not listening on port(s): $($missingPorts -join ', '). Restarting..." "WARNING"
            $phpRestartCount++
            Start-PHPCGI | Out-Null
            Start-Sleep -Seconds 5

            $missingPorts = @(Get-MissingPhpPorts)
            $phpRunning = ($missingPorts.Count -eq 0)
            if ($phpRunning) {
                Write-Log "PHP-CGI restarted successfully (Total restarts: $phpRestartCount)" "INFO"
            } else {
                Write-Log "FAILED to restart PHP-CGI on port(s): $($missingPorts -join ', ')" "ERROR"
            }
        }
        
        # Check Nginx
        $nginxRunning = Test-ProcessRunning -ProcessName "nginx"
        if (-not $nginxRunning) {
            Write-Log "Nginx is not running! Restarting..." "WARNING"
            $nginxRestartCount++
            Start-Nginx | Out-Null
            Start-Sleep -Seconds 5
            
            # Verify restart
            $nginxRunning = Test-ProcessRunning -ProcessName "nginx"
            if ($nginxRunning) {
                Write-Log "Nginx restarted successfully (Total restarts: $nginxRestartCount)" "INFO"
            } else {
                Write-Log "FAILED to restart Nginx!" "ERROR"
            }
        }
        
        # Log a heartbeat every 5 minutes (20 checks at 15 seconds)
        if ($checkCount % 20 -eq 0) {
            Write-Log "Watchdog check: PHP-CGI=$phpRunning, Nginx=$nginxRunning" "INFO"
        }
        
    } catch {
        Write-Log "Error in watchdog loop: $($_.Exception.Message)" "ERROR"
    }
    
    Start-Sleep -Seconds $CheckInterval
}
