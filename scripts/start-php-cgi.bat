@echo off
REM ============================================
REM ZAFFICO Monitoring - PHP-CGI Startup Script
REM ============================================
REM Starts one PHP-CGI listener per port. Nginx spreads requests
REM across them, so one busy or dead process does not take the site down.
REM PHP_FCGI_MAX_REQUESTS stops a worker exiting after the Windows default
REM of 500 requests, which was leaving port 9000 closed and nginx returning
REM "connect() failed (10061)" until the process was started again.

setlocal

set "PHP_EXE=C:\xampp\php\php-cgi.exe"
set "PHP_PORTS=9000 9001 9002 9003"
set "LOG_DIR=%~dp0..\storage\logs"
set "LOG_FILE=%LOG_DIR%\php-cgi.log"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

if not exist "%PHP_EXE%" (
    echo [%date% %time%] ERROR: PHP-CGI executable not found at %PHP_EXE% >> "%LOG_FILE%"
    exit /b 1
)

REM Recycle a worker after many requests so memory does not grow forever.
REM Other workers keep serving while the watchdog starts a replacement.
set PHP_FCGI_MAX_REQUESTS=10000

for %%P in (%PHP_PORTS%) do call :StartPort %%P

timeout /t 2 /nobreak >nul 2>&1

set "FAILED=0"
for %%P in (%PHP_PORTS%) do call :VerifyPort %%P
exit /b %FAILED%

:StartPort
netstat -ano | findstr /C:"127.0.0.1:%1 " | findstr /I "LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo [%date% %time%] PHP-CGI is already listening on port %1 >> "%LOG_FILE%"
    exit /b 0
)

echo [%date% %time%] Starting PHP-CGI on port %1... >> "%LOG_FILE%"
REM Do not redirect the child into the shared log. Four processes writing one file
REM lock it, and the later workers exit before they listen.
start "" /B /MIN "%PHP_EXE%" -b 127.0.0.1:%1
exit /b 0

:VerifyPort
netstat -ano | findstr /C:"127.0.0.1:%1 " | findstr /I "LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo [%date% %time%] SUCCESS: PHP-CGI listening on port %1 >> "%LOG_FILE%"
) else (
    echo [%date% %time%] ERROR: PHP-CGI failed to start on port %1 >> "%LOG_FILE%"
    set "FAILED=1"
)
exit /b 0
