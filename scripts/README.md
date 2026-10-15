# ZAFFICO Monitoring System - Scripts Documentation

This directory contains scripts for managing the ZAFFICO Monitoring System on Windows.

## Scripts Overview

### Startup Scripts

#### `start-php-cgi.bat`
- **Purpose**: Starts PHP-CGI on ports 9000, 9001, 9002, and 9003
- **Features**:
  - Checks if PHP-CGI is already running before starting
  - Properly detaches process to run in background
  - Logs all activities to `storage\logs\php-cgi.log`
  - Verifies successful startup

#### `start-nginx.bat`
- **Purpose**: Starts Nginx web server
- **Features**:
  - Waits 10 seconds for PHP-CGI to initialize
  - Checks if Nginx is already running before starting
  - Properly detaches process to run in background
  - Logs all activities to `storage\logs\nginx.log`
  - Verifies successful startup

### Setup Scripts

#### `setup-startup-task.ps1`
- **Purpose**: Creates Windows Task Scheduler tasks to start PHP-CGI and Nginx at system boot
- **Tasks Created**:
  - `ZAFFICO - Start PHP-CGI` (runs at startup)
  - `ZAFFICO - Start Nginx` (runs at startup)
- **Features**:
  - Runs as SYSTEM account with highest privileges
  - No execution time limit
  - Auto-restart on failure (up to 3 times)
  - Works even on battery power

#### `setup-task-scheduler.ps1`
- **Purpose**: Creates Windows Task Scheduler task to run monitoring checks
- **Task Created**: `Zaffico-Monitoring-Scheduler`
- **Schedule**: Runs every 1 minute
- **Features**:
  - 10-minute execution time limit
  - Auto-restart on failure
  - Runs even on battery power

#### `setup-watchdog-task.ps1`
- **Purpose**: Creates Windows Task Scheduler task to run the watchdog script
- **Task Created**: `ZAFFICO - Process Watchdog`
- **Schedule**: Runs at startup and every hour (as backup)
- **Features**:
  - Monitors PHP-CGI and Nginx processes
  - Automatically restarts processes if they die
  - No execution time limit
  - Logs all activities

#### `setup-all.ps1`
- **Purpose**: Master setup script that runs all setup scripts in the correct order
- **Usage**: Run this once to set up everything

### Monitoring Scripts

#### `watchdog.ps1`
- **Purpose**: Monitors PHP-CGI and Nginx processes and restarts them if they die
- **Check Interval**: 15 seconds (configurable)
- **Features**:
  - Checks if processes are running every 60 seconds
  - Automatically restarts dead processes
  - Logs all activities to `storage\logs\watchdog.log`
  - Tracks restart counts
  - Runs continuously until stopped

## Common Issues and Solutions

### Problem: Processes Die After Some Time

**Causes:**
1. **Task Scheduler Execution Time Limit**: Tasks were being killed after 5 minutes
   - **Solution**: Updated all tasks to have unlimited execution time (`ExecutionTimeLimit = 0`)

2. **No Process Monitoring**: If a process crashed, it wouldn't restart
   - **Solution**: Added watchdog script that monitors and restarts processes

3. **Processes Not Properly Detached**: Batch scripts were exiting and killing child processes
   - **Solution**: Improved batch scripts to properly detach processes using `start /B /MIN`

4. **No Logging**: Hard to debug why processes died
   - **Solution**: Added comprehensive logging to all scripts

### Problem: Processes Don't Start at Boot

**Solutions:**
1. Verify tasks are registered: `Get-ScheduledTask | Where-Object { $_.TaskName -like '*ZAFFICO*' }`
2. Check task status: `Get-ScheduledTask -TaskName "ZAFFICO - Start PHP-CGI" | Format-List`
3. Check logs: `Get-Content storage\logs\php-cgi.log -Tail 50`
4. Run tasks manually to test: `Start-ScheduledTask -TaskName "ZAFFICO - Start PHP-CGI"`

### Problem: Watchdog Not Restarting Processes

**Solutions:**
1. Check if watchdog is running: `Get-Process powershell | Where-Object { $_.CommandLine -like '*watchdog*' }`
2. Check watchdog logs: `Get-Content storage\logs\watchdog.log -Tail 50`
3. Verify watchdog task is enabled: `Get-ScheduledTask -TaskName "ZAFFICO - Process Watchdog"`

## Setup Instructions

### Initial Setup

1. **Run the master setup script** (as Administrator):
   ```powershell
   cd C:\Users\chisengaj\Desktop\LaravelProjects\zaffico-monitoring-system\scripts
   powershell -ExecutionPolicy Bypass -File .\setup-all.ps1
   ```

2. **Verify tasks are created**:
   ```powershell
   Get-ScheduledTask | Where-Object { $_.TaskName -like '*ZAFFICO*' -or $_.TaskName -like '*Zaffico*' }
   ```

3. **Test startup scripts manually**:
   ```powershell
   .\start-php-cgi.bat
   .\start-nginx.bat
   ```

4. **Test watchdog manually** (in a separate terminal):
   ```powershell
   .\watchdog.ps1
   ```

### Updating Tasks

If you need to update task settings:

1. **Update startup tasks**:
   ```powershell
   .\setup-startup-task.ps1
   ```

2. **Update scheduler task**:
   ```powershell
   .\setup-task-scheduler.ps1
   ```

3. **Update watchdog task**:
   ```powershell
   .\setup-watchdog-task.ps1
   ```

### Manual Process Management

**Start PHP-CGI manually**:
```powershell
.\start-php-cgi.bat
```

**Start Nginx manually**:
```powershell
.\start-nginx.bat
```

**Stop PHP-CGI**:
```powershell
Get-Process php-cgi | Stop-Process -Force
```

**Stop Nginx**:
```powershell
Get-Process nginx | Stop-Process -Force
```

**Check if processes are running**:
```powershell
Get-Process php-cgi -ErrorAction SilentlyContinue
Get-Process nginx -ErrorAction SilentlyContinue
netstat -ano | findstr :9000  # Check PHP-CGI port
```

## Log Files

All scripts log to `storage\logs\`:

- `php-cgi.log` - PHP-CGI startup and status logs
- `nginx.log` - Nginx startup and status logs
- `watchdog.log` - Watchdog monitoring and restart logs
- `scheduler.log` - Laravel scheduler execution logs

## Task Scheduler Settings Explained

### ExecutionTimeLimit
- **0 (unlimited)**: Used for watchdog and startup tasks that need to run indefinitely
- **10 minutes**: Used for scheduler task (monitoring checks should complete quickly)

### RestartCount and RestartInterval
- **RestartCount: 3**: Automatically retry failed tasks up to 3 times
- **RestartInterval: 1 minute**: Wait 1 minute between retries

### MultipleInstances
- **IgnoreNew**: If a task is already running, ignore new triggers (prevents duplicate processes)

### RunLevel
- **Highest**: Tasks run with administrator privileges (required for some operations)

## Troubleshooting

### View Task History
```powershell
Get-WinEvent -LogName Microsoft-Windows-TaskScheduler/Operational | Where-Object { $_.Message -like '*ZAFFICO*' } | Select-Object -First 20
```

### Check Task Status
```powershell
Get-ScheduledTask -TaskName "ZAFFICO - Process Watchdog" | Format-List *
```

### Enable Task History (if not enabled)
1. Open Task Scheduler (taskschd.msc)
2. Right-click "Task Scheduler Library" → Properties
3. Check "Enable All Tasks History"

### View Real-time Logs
```powershell
# Watch watchdog log in real-time
Get-Content storage\logs\watchdog.log -Wait -Tail 20

# Watch PHP-CGI log in real-time
Get-Content storage\logs\php-cgi.log -Wait -Tail 20
```

## Best Practices

1. **Always run setup scripts as Administrator** to ensure proper permissions
2. **Test scripts manually** before relying on Task Scheduler
3. **Monitor logs regularly** to catch issues early
4. **Keep watchdog running** - it's your safety net for process failures
5. **Don't modify Task Scheduler tasks manually** - use the setup scripts instead
