# Zaffico Monitoring System - Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Technology Stack](#technology-stack)
4. [Database Schema](#database-schema)
5. [Core Features](#core-features)
6. [Installation & Setup](#installation--setup)
7. [Usage Guide](#usage-guide)
8. [API & Services](#api--services)
9. [Monitoring Engine](#monitoring-engine)
10. [Future Enhancements](#future-enhancements)

---

## Project Overview

**Zaffico Monitoring System** is a comprehensive application monitoring and alerting platform built with Laravel 11. It provides real-time monitoring of applications using multiple protocols (HTTP/HTTPS, TCP, ICMP/Ping), automatic incident detection, alert rule evaluation, and multi-channel notifications (Email, Slack).

### Key Objectives
- Monitor multiple applications with configurable check intervals
- Track application uptime and downtime with detailed incident reports
- Automatic alert rule evaluation and incident creation
- Multi-channel notifications (Email, Slack)
- Executive dashboard with SLA metrics, MTTR, and system availability gauges
- Comprehensive incident management and reporting

---

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Monitoring Scheduler                      │
│              (Laravel Console - Every Minute)               │
└──────────────────────────┬──────────────────────────────────┘
                           │
        ┌──────────────────▼───────────────────┐
        │   ExecuteMonitoringChecks Job        │
        │  (Process all active applications)   │
        └──────────────────┬────────────────────┘
                           │
    ┌──────────────────────▼────────────────────────┐
    │         HealthCheckService                     │
    │  ┌─────────────────────────────────────────┐  │
    │  │ • HTTP/HTTPS Checks                     │  │
    │  │ • TCP Connection Checks                 │  │
    │  │ • ICMP/Ping Checks                      │  │
    │  │ • Platform-specific Commands            │  │
    │  └─────────────────────────────────────────┘  │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │      MonitoringCheck Record Created         │
    │  (Status, Response Time, Error Message)     │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │       AlertRuleService                      │
    │  (Evaluate alert rules against result)      │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │    DowntimeIncident Creation/Resolution     │
    │           (If condition triggered)           │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │     NotificationService                     │
    │  ┌──────────────────────────────────────┐   │
    │  │ • Email Notifications                │   │
    │  │ • Slack Webhook Integration          │   │
    │  │ • Notification Logging               │   │
    │  └──────────────────────────────────────┘   │
    └──────────────────────────────────────────────┘
```

---

## Technology Stack

### Backend
- **Framework**: Laravel 11
- **Language**: PHP 8.2+
- **Database**: PostgreSQL
- **Queue System**: Laravel Queues
- **Task Scheduling**: Laravel Scheduler
- **HTTP Client**: Laravel HTTP (Guzzle)

### Frontend
- **CSS Framework**: Tailwind CSS
- **Icons**: Font Awesome 6.4
- **JavaScript**: Vanilla JS + Chart.js
- **Build Tool**: Vite
- **Asset Bundling**: Vite + Tailwind

### Authentication
- **System**: Laravel Breeze
- **Guards**: Web authentication

---

## Database Schema

### Tables Overview

#### `applications` - Monitored applications
```sql
- id (PK)
- name (string)
- ip_address (string)
- port (integer, nullable)
- protocol (enum: http, https, tcp, icmp)
- endpoint (string, nullable)
- description (text, nullable)
- check_interval (integer) - Minutes between checks
- timeout (integer) - Seconds before timeout
- status (enum: up, down)
- response_time (integer) - In milliseconds
- is_active (boolean)
- created_by (FK -> users)
- last_checked_at (timestamp)
- created_at, updated_at
```

#### `monitoring_checks` - Historical check records
```sql
- id (PK)
- application_id (FK)
- status (enum: up, down)
- response_time (integer) - Milliseconds
- status_code (integer, nullable)
- error_message (text, nullable)
- response_body (longtext, nullable)
- checked_at (timestamp)
- created_at, updated_at
```

#### `downtime_incidents` - Outage records
```sql
- id (PK)
- application_id (FK)
- alert_rule_id (FK, nullable)
- started_at (timestamp)
- resolved_at (timestamp, nullable)
- duration (integer, nullable) - Minutes
- severity (enum: low, medium, high, critical)
- error_message (text, nullable)
- resolution_notes (text, nullable)
- resolved_by (FK -> users, nullable)
- notification_sent (boolean)
- created_at, updated_at
```

#### `alert_rules` - Alert configuration
```sql
- id (PK)
- application_id (FK)
- trigger_type (enum: downtime, slow_response, status_code, consecutive_failures)
- condition_type (string)
- condition_value (integer, nullable)
- threshold_value (integer, nullable)
- consecutive_failures (integer, default: 3)
- severity (enum: low, medium, high, critical)
- notification_email (string, nullable)
- notification_slack_webhook (string, nullable)
- notification_channels (json)
- recipients (json)
- is_active (boolean)
- created_at, updated_at
```

#### `notifications` - Notification history
```sql
- id (PK)
- application_id (FK)
- incident_id (FK, nullable)
- alert_rule_id (FK, nullable)
- message (text)
- sent_at (timestamp)
- created_at, updated_at
```

#### `maintenance_windows` - Planned maintenance
```sql
- id (PK)
- application_id (FK)
- start_time (timestamp)
- end_time (timestamp)
- reason (text)
- disable_alerts (boolean)
- created_at, updated_at
```

#### `uptime_reports` - Uptime statistics
```sql
- id (PK)
- application_id (FK)
- uptime_percentage (float)
- downtime_hours (float)
- total_incidents (integer)
- report_date (date)
- created_at, updated_at
```

#### `users` - System users
```sql
- id (PK)
- name (string)
- email (string, unique)
- password (hashed)
- created_at, updated_at
```

#### `tags` - Application tags/categories
```sql
- id (PK)
- name (string, unique)
- description (text, nullable)
- created_at, updated_at
```

#### `application_tag` - Many-to-many relationship
```sql
- application_id (FK)
- tag_id (FK)
```

---

## Core Features

### 1. Application Management
- **Add/Edit/Delete Applications**: Configure monitoring targets
- **Protocol Support**: HTTP, HTTPS, TCP, ICMP/Ping
- **Configuration**: Port, endpoint, timeout, check interval
- **Status Tracking**: Real-time status and response time
- **Tagging System**: Organize applications by tags

### 2. Health Checking
- **HTTP/HTTPS Checks**: Full request/response validation
- **TCP Connection Checks**: Port connectivity testing
- **ICMP/Ping Checks**: Host reachability testing
- **Response Time Tracking**: Millisecond precision
- **Error Logging**: Detailed error messages captured

### 3. Alert Rules
- **Multiple Conditions**:
  - Status down detection
  - Response time thresholds
  - Status code validation
  - Consecutive failures
- **Severity Levels**: Critical, High, Medium, Low
- **Multi-Channel Notifications**: Email and Slack

### 4. Incident Management
- **Automatic Incident Creation**: When alert rules trigger
- **Incident Tracking**: Start time, resolution time, duration
- **Manual Resolution**: Mark incidents as resolved
- **Resolution Notes**: Document resolution steps
- **Incident History**: Full audit trail

### 5. Notifications
- **Email Alerts**: Customizable recipient emails
- **Slack Integration**: Webhook-based notifications
- **Status Messages**: Incident and resolution notifications
- **Notification History**: Track all sent notifications

### 6. Executive Dashboard
- **Key Metrics**:
  - SLA Compliance (30-day, target: 99.9%)
  - MTTR (Mean Time To Recovery)
  - System Availability (7-day gauge)
  - Incident Severity Distribution
- **Visualizations**:
  - Application status trends (7-day chart)
  - Top problem applications ranking
  - Recent incidents table
- **Real-time Data**: All metrics calculated from database

### 7. Monitoring Pages
- **Applications**: List, create, edit, delete, view details
- **Monitoring Checks**: Historical check records and status
- **Incidents**: Track downtime with detailed timelines
- **Alert Rules**: Configure and manage alert conditions
- **Maintenance Windows**: Plan scheduled downtime
- **Uptime Reports**: Historical uptime statistics
- **Notifications**: View notification history

---

## Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- PostgreSQL 12+
- Composer
- Node.js 18+ (for frontend assets)

### Step 1: Clone & Install
```bash
git clone <repository-url>
cd zaffico-monitoring-system
composer install
npm install
```

### Step 2: Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Database Configuration
Update `.env` with PostgreSQL credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=zaffico_monitoring
DB_USERNAME=postgres
DB_PASSWORD=password
```

### Step 4: Run Migrations
```bash
php artisan migrate
php artisan db:seed  # Optional: seed sample data
```

### Step 5: Build Assets
```bash
npm run build
# Or for development with watch:
npm run dev
```

### Step 6: Start Services
```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Vite development server (frontend assets)
npm run dev

# Terminal 3: Laravel Scheduler (monitoring)
php artisan schedule:work

# Terminal 4 (Optional): Queue worker
php artisan queue:work
```

### Step 7: Create Admin User
```bash
php artisan tinker
# Then:
User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')])
```

### Step 8: Access Application
- URL: `http://localhost:8000`
- Email: `admin@example.com`
- Password: `password`

---

## Usage Guide

### Creating an Application to Monitor

1. Navigate to **Applications** → **Create Application**
2. Fill in the form:
   - **Name**: Descriptive name
   - **IP Address/Domain**: Target host
   - **Protocol**: Select check type (HTTP/HTTPS/TCP/ICMP)
   - **Port**: If applicable (default: 80 for HTTP, 443 for HTTPS)
   - **Endpoint**: API path (e.g., `/api/health`)
   - **Check Interval**: Minutes between checks (1-60)
   - **Timeout**: Seconds before timeout (5-60)
3. Click **Create**

### Setting Up Alert Rules

1. Go to **Alerts** → **Create Alert Rule**
2. Select Application to monitor
3. Configure Trigger:
   - **Downtime**: Trigger when application goes down
   - **Slow Response**: Trigger if response time exceeds threshold
   - **Status Code**: Trigger on specific HTTP status codes
   - **Consecutive Failures**: Trigger after N failures
4. Set Severity Level
5. Configure Notifications:
   - Email: Comma-separated recipient addresses
   - Slack: Webhook URL from your Slack workspace
6. Click **Create**

### Viewing Dashboard

1. Navigate to **Dashboard**
2. View real-time metrics:
   - Application status (up/down count)
   - SLA compliance percentage
   - Mean Time To Recovery
   - System availability gauge
   - Top problem applications
3. Click on application in chart to view detailed trend

### Managing Incidents

1. Go to **Incidents** to view all downtime events
2. Click on incident to see details:
   - Timeline with start/resolution times
   - Error message and resolution notes
   - Incident severity
3. Manually resolve by clicking **Resolve Incident**

### Scheduling Maintenance

1. Navigate to **Maintenance** → **Create Maintenance Window**
2. Select application and date/time range
3. Toggle **Disable Alerts** to silence notifications
4. Add reason for maintenance
5. Click **Create**

---

## API & Services

### HealthCheckService

Handles all protocol-specific health checks.

**Methods:**
```php
// Execute a health check and return result array
executeCheck(Applications $application): array

// Record check result to database
recordCheck(Applications $application, array $checkResult): MonitoringCheck

// Execute check and record in one call
executeAndRecord(Applications $application): MonitoringCheck
```

**Return Array:**
```php
[
    'status' => 'up|down',
    'response_time' => 1234, // milliseconds
    'status_code' => 200, // nullable
    'error_message' => 'Connection timeout', // nullable
    'response_body' => '...', // nullable
]
```

### AlertRuleService

Evaluates alert rules and creates incidents.

**Methods:**
```php
// Evaluate all rules for an application
evaluateRules(Applications $application, MonitoringCheck $check): void

// Handle triggered rule (create/resolve incidents)
handleTriggeredRule(Applications $application, MonitoringCheck $check, AlertRule $rule): void

// Check for consecutive failures
checkConsecutiveFailures(Applications $application, int $threshold): bool
```

### NotificationService

Sends notifications via multiple channels.

**Methods:**
```php
// Send incident notification
notifyIncident(DowntimeIncident $incident, AlertRule $rule): void

// Send resolution notification
notifyResolution(DowntimeIncident $incident): void

// Send email notification
sendEmailNotification(DowntimeIncident $incident, AlertRule $rule, string $type): void

// Send Slack notification
sendSlackNotification(DowntimeIncident $incident, AlertRule $rule, string $type): void
```

---

## Monitoring Engine

### How It Works

1. **Scheduling**: Laravel Scheduler runs every minute
2. **Job Dispatch**: `ExecuteMonitoringChecks` job processes active applications
3. **Health Check**: `HealthCheckService` executes protocol-specific check
4. **Result Recording**: `MonitoringCheck` record created with results
5. **Rule Evaluation**: `AlertRuleService` evaluates all alert rules
6. **Incident Management**:
   - If condition triggered: Create `DowntimeIncident`
   - If status returns to normal: Resolve existing incident
7. **Notifications**: Send Email/Slack via `NotificationService`

### Console Commands

**Test Monitoring**
```bash
# Test all active applications
php artisan monitoring:test --all

# Test specific application
php artisan monitoring:test --app-id=1

# Options show:
# ✓ Status (up/down)
# ⏱ Response time in milliseconds
# • Status code
# ✗ Error messages
```

**Cleanup Old Checks** (Scheduled daily at 2:00 AM)
```bash
php artisan monitoring:cleanup
```

### Scheduling Options

**Development** (Run in terminal):
```bash
php artisan schedule:work
```

**Production** (Add to crontab):
```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## Frontend Pages

### Public Pages
- `/login` - User authentication
- `/register` - User registration

### Authenticated Pages

#### Dashboard (`/dashboard`)
- Real-time metrics and statistics
- Application status trends
- Executive summary cards
- Recent incidents table

#### Applications (`/applications`)
- List all applications with status
- Create new application
- Edit application settings
- Delete application
- View application details

#### Monitoring Checks (`/checks`)
- View historical check records
- Filter by application and status
- See response times and errors
- View response bodies

#### Downtime Incidents (`/incidents`)
- List all incidents
- View incident timeline
- Create manual incident
- Resolve incident
- View resolution notes

#### Alert Rules (`/alerts`)
- Manage alert configurations
- Create alert rules
- Edit rule conditions
- Disable/enable rules
- View alert history

#### Maintenance Windows (`/maintenance`)
- Schedule maintenance periods
- Disable alerts during maintenance
- View maintenance history
- Edit maintenance details

#### Uptime Reports (`/uptime-reports`)
- View historical uptime statistics
- Filter by date range
- Export report data
- Analyze trends

---

## Directory Structure

```
zaffico-monitoring-system/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   └── TestMonitoring.php
│   │   └── Kernel.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ApplicationController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── MonitoringCheckController.php
│   │   │   ├── DowntimeIncidentController.php
│   │   │   ├── AlertRuleController.php
│   │   │   ├── MaintenanceWindowController.php
│   │   │   ├── UptimeReportController.php
│   │   │   └── NotificationController.php
│   │   └── Requests/
│   ├── Jobs/
│   │   └── ExecuteMonitoringChecks.php
│   ├── Models/
│   │   ├── Applications.php
│   │   ├── MonitoringCheck.php
│   │   ├── DowntimeIncident.php
│   │   ├── AlertRule.php
│   │   ├── Notification.php
│   │   ├── MaintenanceWindow.php
│   │   ├── UptimeReport.php
│   │   ├── Tags.php
│   │   └── User.php
│   ├── Services/
│   │   ├── HealthCheckService.php
│   │   ├── AlertRuleService.php
│   │   └── NotificationService.php
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── dashboard.blade.php
│       ├── applications/
│       ├── checks/
│       ├── incidents/
│       ├── alerts/
│       ├── maintenance/
│       ├── uptime-reports/
│       └── auth/
├── routes/
│   ├── web.php
│   ├── auth.php
│   └── console.php
├── storage/
├── tests/
├── vendor/
├── .env.example
├── artisan
├── composer.json
├── package.json
├── tailwind.config.js
├── vite.config.js
└── README.md
```

---

## Future Enhancements

### Phase 2: Advanced Monitoring
- [ ] Custom health check endpoints with JSON response validation
- [ ] Performance metrics collection (CPU, Memory, Disk)
- [ ] Database connectivity monitoring
- [ ] SSL certificate expiration alerts
- [ ] DNS resolution monitoring

### Phase 3: Notifications & Integration
- [ ] SMS alerts via Twilio
- [ ] PagerDuty integration
- [ ] Microsoft Teams webhooks
- [ ] Discord integration
- [ ] Webhook callbacks for third-party systems
- [ ] On-call escalation policies

### Phase 4: Reporting & Analytics
- [ ] Custom report generation (PDF/CSV)
- [ ] Availability statistics by time period
- [ ] Cost impact analysis
- [ ] Performance trending
- [ ] SLA compliance reporting
- [ ] Export to BI tools

### Phase 5: Advanced Features
- [ ] Multi-step health checks (dependency chains)
- [ ] Synthetic transactions/user flows
- [ ] Geographic monitoring locations
- [ ] Private agent deployment
- [ ] Custom dashboards
- [ ] Role-based access control (RBAC)
- [ ] Audit logging
- [ ] API authentication with tokens

### Phase 6: Real-time Features
- [ ] WebSocket live updates
- [ ] Real-time incident notifications
- [ ] Live metric streaming
- [ ] Browser notifications

---

## Troubleshooting

### Monitoring checks not running
1. Verify scheduler is running: `php artisan schedule:work`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Test manually: `php artisan monitoring:test --all`

### Notifications not sending
1. Verify mail configuration in `.env`
2. Check Slack webhook URL is valid
3. Review logs for mail errors
4. Test with: `php artisan tinker` → `Mail::raw(...)`

### Applications showing as down incorrectly
1. Check IP address and port configuration
2. Verify protocol is correct
3. Test connectivity manually: `ping <ip>` or `curl <url>`
4. Increase timeout value if network is slow

### Database connection errors
1. Verify PostgreSQL is running
2. Check `.env` database credentials
3. Ensure database exists
4. Run migrations: `php artisan migrate`

---

## Support & Contact

For issues, feature requests, or contributions, please refer to the project repository.

**Project Status**: Active Development (Phase 1 Complete)
**Last Updated**: November 27, 2025
**Version**: 1.0.0

---

