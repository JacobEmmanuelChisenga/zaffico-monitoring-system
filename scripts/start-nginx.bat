@echo off
REM ============================================
REM ZAFFICO Monitoring - Nginx Startup Script
REM ============================================
REM This script starts Nginx if it's not already running
REM It properly detaches the process and logs output

setlocal

set "NGINX_HOME=C:\nginx"
set "NGINX_EXE=%NGINX_HOME%\nginx.exe"
set "LOG_DIR=%~dp0..\storage\logs"
set "LOG_FILE=%LOG_DIR%\nginx.log"

REM Create logs directory if it doesn't exist
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

REM Wait for PHP-CGI to initialize (if starting at boot)
echo [%date% %time%] Waiting 10 seconds for PHP-CGI to initialize... >> "%LOG_FILE%"
timeout /t 10 /nobreak >nul 2>&1

REM Reload only when nginx is already listening. Always pass -p so nginx uses
REM C:\nginx\conf and C:\nginx\logs. Without -p it looks for logs under the
REM folder you ran the script from and never opens port 8888.
netstat -ano | findstr "LISTENING" | findstr ":8888 " >nul 2>&1
if %errorlevel% equ 0 (
    echo [%date% %time%] Nginx is already listening on 8888, reloading configuration... >> "%LOG_FILE%"
    "%NGINX_EXE%" -p "%NGINX_HOME%" -s reload >> "%LOG_FILE%" 2>&1
    if %errorlevel% equ 0 (
        echo [%date% %time%] SUCCESS: Nginx configuration reloaded >> "%LOG_FILE%"
        exit /b 0
    ) else (
        echo [%date% %time%] ERROR: Nginx reload failed. Check C:\nginx\logs\error.log >> "%LOG_FILE%"
        exit /b 1
    )
)

REM Check if Nginx executable exists
if not exist "%NGINX_EXE%" (
    echo [%date% %time%] ERROR: Nginx executable not found at %NGINX_EXE% >> "%LOG_FILE%"
    exit /b 1
)

REM A leftover nginx.exe with the wrong folder must be stopped first.
tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | find /I /N "nginx.exe" >nul 2>&1
if %errorlevel% equ 0 (
    echo [%date% %time%] Stopping nginx that is not listening on 8888... >> "%LOG_FILE%"
    taskkill /F /IM nginx.exe >> "%LOG_FILE%" 2>&1
    timeout /t 1 /nobreak >nul 2>&1
)

REM Start Nginx in background from its own folder.
REM Do not redirect nginx into nginx.log. The editor often has that file open,
REM and the lock makes nginx exit before it listens on 8888.
echo [%date% %time%] Starting Nginx... >> "%LOG_FILE%"
cd /d "%NGINX_HOME%"
start "" /B /MIN "%NGINX_EXE%" -p "%NGINX_HOME%"

REM Wait, then confirm port 8888. A running nginx.exe is not enough:
REM the process can exist while the site port is closed.
timeout /t 3 /nobreak >nul 2>&1

netstat -ano | findstr "LISTENING" | findstr ":8888 " >nul 2>&1
if %errorlevel% equ 0 (
    echo [%date% %time%] SUCCESS: Nginx is listening on port 8888 >> "%LOG_FILE%"
    exit /b 0
)

echo [%date% %time%] ERROR: Nginx is not listening on port 8888 >> "%LOG_FILE%"
echo [%date% %time%] --- C:\nginx\logs\error.log --- >> "%LOG_FILE%"
if exist "%NGINX_HOME%\logs\error.log" (
    powershell -NoProfile -Command "Get-Content -Path '%NGINX_HOME%\logs\error.log' -Tail 15" >> "%LOG_FILE%" 2>&1
)
exit /b 1
