# Testing Guide - Zaffico Monitoring System

## Quick Start Testing

### Step 1: Start the Laravel Application

Open a terminal and start the Laravel development server:

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Step 2: Start the Scheduler (IMPORTANT!)

Open a **second terminal** and run the scheduler:

```bash
php artisan schedule:work
```

This will run monitoring checks every minute. **Keep this terminal open** while testing.

### Step 3: Add Applications to Monitor

1. Navigate to `http://localhost:8000/applications`
2. Click "Create Application"
3. Fill in the details:
   - **Name**: e.g., "Company Web Server"
   - **IP Address/Domain**: Enter the IP address from your company network (e.g., `192.168.1.100` or `10.0.0.50`)
   - **Protocol**: 
     - `HTTP` or `HTTPS` for web servers
     - `TCP` for port connectivity (e.g., database servers)
     - `ICMP` or `Ping` for basic connectivity
   - **Port**: 
     - `80` for HTTP
     - `443` for HTTPS
     - Leave empty or set specific port for TCP
   - **Endpoint**: Optional (e.g., `/api/health` for health check endpoints)
   - **Check Interval**: Minutes between checks (e.g., `5` for every 5 minutes)
   - **Timeout**: Seconds before timeout (default `10`)
   - **Is Active**: Make sure this is checked ✓

4. Click "Create"

### Step 4: Test Manually (Optional)

Before waiting for the scheduler, you can test immediately:

```bash
# Test all active applications
php artisan monitoring:test --all

# Test a specific application by ID
php artisan monitoring:test --app-id=1
```

### Step 5: View Results

1. **Dashboard** (`/dashboard`):
   - View real-time status of all applications
   - See uptime/downtime graphs (will populate after a few checks)
   - Check SLA metrics and system availability

2. **Monitoring Checks** (`/checks`):
   - See all check records grouped by intervals
   - View response times and status codes
   - Filter by application or status

3. **Applications** (`/applications`):
   - See current status of each application
   - View last checked time
   - See response times

## Testing Different Protocols

### HTTP/HTTPS Testing
- **IP Address**: `192.168.1.100` or domain name
- **Protocol**: `HTTP` or `HTTPS`
- **Port**: `80` (HTTP) or `443` (HTTPS)
- **Endpoint**: Optional (e.g., `/`, `/api/health`, `/status`)

### TCP Testing
- **IP Address**: `192.168.1.100`
- **Protocol**: `TCP`
- **Port**: e.g., `3306` (MySQL), `5432` (PostgreSQL), `22` (SSH), `3389` (RDP)
- **Endpoint**: Leave empty

### ICMP/Ping Testing
- **IP Address**: `192.168.1.100`
- **Protocol**: `ICMP` or `Ping`
- **Port**: Leave empty
- **Endpoint**: Leave empty

## Troubleshooting

### No checks appearing?
1. Make sure the scheduler is running: `php artisan schedule:work`
2. Check that applications are marked as "Active"
3. Check logs: `tail -f storage/logs/laravel.log` (or view in Windows)
4. Manually trigger a test: `php artisan monitoring:test --all`

### Applications showing as "Down"?
1. Verify IP address is correct and reachable from your network
2. Check if firewall is blocking connections
3. Test connectivity manually:
   - For HTTP: Open browser to `http://IP_ADDRESS`
   - For TCP: `telnet IP_ADDRESS PORT` (Windows) or `nc -zv IP_ADDRESS PORT` (Linux)
   - For Ping: `ping IP_ADDRESS`

### Graphs not showing data?
1. Wait a few minutes for checks to accumulate (need at least 7 days of data for full graphs)
2. Refresh the dashboard page
3. Check browser console for JavaScript errors (F12)
4. Verify Chart.js is loading (check Network tab)

### Scheduler not running?
- Make sure you're running `php artisan schedule:work` in a separate terminal
- Check that Laravel can access the database
- Verify no errors in `storage/logs/laravel.log`

## Network Testing Tips

1. **Start with Ping**: Test basic connectivity first with ICMP/Ping protocol
2. **Then TCP**: Test specific ports if ping works
3. **Finally HTTP**: Test web services if TCP works
4. **Check Firewalls**: Make sure your local firewall allows outbound connections
5. **Network Access**: Ensure you're connected to the company network/VPN

## Expected Behavior

- **First Check**: May take a few seconds, status will be "up" or "down"
- **Subsequent Checks**: Will run at the interval you specified
- **Graphs**: Will start showing data after multiple checks (especially for 7-day trends)
- **Dashboard**: Will update in real-time as new checks complete

## Production Deployment

For production, set up a cron job instead of `schedule:work`:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

