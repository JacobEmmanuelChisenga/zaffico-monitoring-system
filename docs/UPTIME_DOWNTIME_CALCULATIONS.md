# Uptime and Downtime Calculation Formulas

This document explains how uptime and downtime are calculated in the ZAFFICO Monitoring System.

## Overview

The system uses **two primary methods** to calculate uptime and downtime:

1. **Incident-Based Calculation** (Primary Method) - Uses `DowntimeIncident` records
2. **Check-Based Calculation** (Fallback Method) - Uses `MonitoringCheck` records when incidents are not available

---

## Method 1: Incident-Based Calculation (Primary)

This is the **preferred and most accurate method**. It calculates downtime based on actual incident records.

### Formula

```php
// Step 1: Calculate total downtime from incidents
$totalDowntimeMinutes = 0;
foreach ($incidents as $incident) {
    $start = max($incident->started_at, $periodStart);
    $end = $incident->resolved_at ? min($incident->resolved_at, $periodEnd) : $periodEnd;
    $totalDowntimeMinutes += $start->diffInMinutes($end);
}

// Step 2: Exclude maintenance windows (if configured)
foreach ($maintenanceWindows as $mw) {
    $start = max($mw->start_time, $periodStart);
    $end = min($mw->end_time, $periodEnd);
    $totalDowntimeMinutes -= $start->diffInMinutes($end);
}

// Step 3: Calculate uptime percentage
$totalMinutes = $periodStart->diffInMinutes($periodEnd);
$uptimeMinutes = max(0, $totalMinutes - $totalDowntimeMinutes);
$uptimePercentage = ($totalMinutes > 0) ? ($uptimeMinutes / $totalMinutes) * 100 : 100;
```

### Mathematical Formula

```
Uptime Percentage = (Uptime Minutes / Total Minutes) × 100

Where:
- Uptime Minutes = Total Minutes - Downtime Minutes
- Downtime Minutes = Σ(Incident Duration) - Σ(Maintenance Window Duration)
- Total Minutes = Period End - Period Start (in minutes)
```

### Example

**Scenario**: Calculate uptime for a single day (24 hours = 1,440 minutes)

- **Period**: January 1, 2026 00:00:00 to 23:59:59
- **Incident 1**: Started at 10:00 AM, Resolved at 10:30 AM = 30 minutes
- **Incident 2**: Started at 2:00 PM, Resolved at 3:15 PM = 75 minutes
- **Maintenance Window**: 1:00 PM to 1:30 PM = 30 minutes (excluded from downtime)

**Calculation**:
```
Total Minutes = 1,440 minutes (24 hours)
Downtime Minutes = (30 + 75) - 30 = 75 minutes
Uptime Minutes = 1,440 - 75 = 1,365 minutes
Uptime Percentage = (1,365 / 1,440) × 100 = 94.79%
```

---

## Method 2: Check-Based Calculation (Fallback)

This method is used when **no incidents exist** for the period. It estimates downtime based on the percentage of failed monitoring checks.

### Formula

```php
// Step 1: Count checks
$totalChecks = MonitoringCheck::whereBetween('checked_at', [$startDate, $endDate])->count();
$downChecks = MonitoringCheck::where('status', 'down')->count();

// Step 2: Estimate downtime based on check ratio
if ($totalChecks > 0) {
    // For past days: use full day (1440 minutes)
    // For today: use elapsed minutes
    $totalMinutesInPeriod = ($isToday) ? 
        max(1, $startDate->diffInMinutes($now)) : 
        1440;
    
    $downtimeMinutes = ($downChecks / $totalChecks) * $totalMinutesInPeriod;
}

// Step 3: Calculate uptime percentage
$uptimePercentage = max(0, 100 - min($downtimeMinutes, $totalMinutesInPeriod) / $totalMinutesInPeriod * 100);
```

### Mathematical Formula

```
Downtime Minutes = (Down Checks / Total Checks) × Total Minutes in Period

Uptime Percentage = 100 - (Downtime Minutes / Total Minutes in Period) × 100
```

### Example

**Scenario**: Calculate uptime for a day with no incidents, but monitoring checks show failures

- **Period**: January 1, 2026 (full day = 1,440 minutes)
- **Total Checks**: 1,440 (one check per minute)
- **Down Checks**: 72 (5% of checks failed)

**Calculation**:
```
Downtime Minutes = (72 / 1,440) × 1,440 = 72 minutes
Uptime Percentage = 100 - (72 / 1,440) × 100 = 95%
```

**Note**: This method assumes checks are evenly distributed throughout the period. It's less accurate than incident-based calculation but provides an estimate when incidents aren't available.

---

## Application Stats Calculation

For the **Application Detail Page**, uptime is calculated differently using all historical checks:

### Formula

```php
$totalChecks = $application->checks()->count();
$upChecks = $application->checks()->where('status', 'up')->count();
$uptimePercentage = ($totalChecks > 0) ? ($upChecks / $totalChecks) * 100 : 0;
```

### Mathematical Formula

```
Uptime Percentage = (Up Checks / Total Checks) × 100
```

This gives a **simple ratio** of successful checks to total checks across all time.

---

## System Availability Calculation

The **System Availability** metric on the dashboard/welcome page calculates overall system uptime across all applications for the last 7 days.

### Formula

```php
$sevenDaysAgo = Carbon::now()->subDays(7);
$totalMinutes = Carbon::now()->diffInMinutes($sevenDaysAgo);
$downtimeMinutes = 0;

// Sum all incident durations
foreach ($incidents as $incident) {
    $end = $incident->resolved_at ?: Carbon::now();
    $duration = $incident->duration ?? $incident->started_at->diffInMinutes($end);
    $downtimeMinutes += $duration;
}

$availability = ($totalMinutes > 0) ? (($totalMinutes - $downtimeMinutes) / $totalMinutes) * 100 : 100;
```

### Mathematical Formula

```
System Availability = ((Total Minutes - Total Downtime Minutes) / Total Minutes) × 100

Where:
- Total Minutes = 7 days × 24 hours × 60 minutes = 10,080 minutes
- Total Downtime Minutes = Σ(All Incident Durations across all applications)
```

### Status Classification

- **≥ 99%**: "Excellent" (Green)
- **≥ 95%**: "Good" (Yellow)
- **< 95%**: "At Risk" (Red)

---

## Daily Trend Calculation (7 Days)

For the **Uptime Trend Chart**, the system calculates uptime for each of the last 7 days separately.

### Formula (Per Day)

```php
// For each day in the last 7 days
for ($i = 0; $i < 7; $i++) {
    $startDate = Carbon::now()->subDays($i)->startOfDay();
    $endDate = Carbon::now()->subDays($i)->endOfDay();
    
    // For today: use current time as end
    if ($i === 0) {
        $endDate = Carbon::now();
    }
    
    // Calculate downtime using incident-based method (or fallback)
    // ... (same as Method 1 or Method 2 above)
    
    $uptimePercentage = max(0, 100 - ($downtimeMinutes / $totalMinutesInPeriod) * 100);
}
```

### Special Handling for "Today"

- **Past Days**: Use full 1,440 minutes (24 hours)
- **Today**: Use elapsed minutes from start of day to current time
  ```php
  $totalMinutesInPeriod = max(1, $startDate->diffInMinutes(Carbon::now()));
  ```

This ensures the chart shows **real-time data** for today, updating as time progresses.

---

## Maintenance Window Exclusion

Maintenance windows are **excluded from downtime calculations** if they have `disable_alerts = true`.

### Formula

```php
// After calculating downtime from incidents
foreach ($maintenanceWindows as $mw) {
    if ($mw->disable_alerts) {
        $start = max($mw->start_time, $periodStart);
        $end = min($mw->end_time, $periodEnd);
        $totalDowntimeMinutes -= $start->diffInMinutes($end);
    }
}

// Ensure downtime doesn't go negative
$totalDowntimeMinutes = max(0, $totalDowntimeMinutes);
```

### Example

**Scenario**: 
- Incident: 10:00 AM - 11:00 AM (60 minutes downtime)
- Maintenance Window: 10:15 AM - 10:45 AM (30 minutes, `disable_alerts = true`)

**Calculation**:
```
Initial Downtime = 60 minutes
Maintenance Window = 30 minutes (overlaps with incident)
Adjusted Downtime = 60 - 30 = 30 minutes
```

---

## Period Types

Different period types use different time boundaries:

### Daily
```php
$startDate = $date->copy()->startOfDay();  // 00:00:00
$endDate = $date->copy()->endOfDay();      // 23:59:59
$totalMinutes = 1,440; // 24 hours
```

### Weekly
```php
$startDate = $date->copy()->startOfWeek();  // Monday 00:00:00
$endDate = $date->copy()->endOfWeek();      // Sunday 23:59:59
$totalMinutes = 10,080; // 7 days × 24 hours × 60 minutes
```

### Monthly
```php
$startDate = $date->copy()->startOfMonth();  // 1st of month 00:00:00
$endDate = $date->copy()->endOfMonth();      // Last day 23:59:59
$totalMinutes = varies; // Depends on number of days in month
```

---

## Key Points

1. **Incident-Based is More Accurate**: Always prefer incident-based calculation when incidents exist.

2. **Check-Based is an Estimate**: Used only as fallback when no incidents are available.

3. **Maintenance Windows Reduce Downtime**: Scheduled maintenance is excluded from downtime calculations.

4. **Real-Time Updates**: Today's calculations use elapsed time, not full day, for real-time accuracy.

5. **Active Incidents**: If an incident hasn't been resolved (`resolved_at = null`), it's counted as downtime until the current time.

6. **Overlapping Incidents**: Each incident's duration is calculated independently and summed. Overlapping incidents may result in double-counting downtime (this is intentional - if two apps are down simultaneously, both count).

---

## Code Locations

- **Primary Calculation**: `app/Services/UptimeReportService.php` (lines 34-78)
- **Welcome Page**: `app/Http/Controllers/WelcomeController.php` (lines 97-136, 141-274)
- **Dashboard**: `app/Http/Controllers/DashboardController.php` (lines 89-125, 155-192)
- **Application Detail**: `app/Http/Controllers/ApplicationsController.php` (lines 208-264, 344-369)
- **Uptime Reports**: `app/Http/Controllers/UptimeReportController.php` (lines 77-130)

---

## Example Scenarios

### Scenario 1: Perfect Uptime
- **Period**: 1 day (1,440 minutes)
- **Incidents**: None
- **Result**: 100% uptime, 0 minutes downtime

### Scenario 2: Single Incident
- **Period**: 1 day (1,440 minutes)
- **Incident**: 2 hours (120 minutes)
- **Result**: 91.67% uptime, 120 minutes downtime
- **Calculation**: (1,440 - 120) / 1,440 × 100 = 91.67%

### Scenario 3: Multiple Incidents
- **Period**: 1 day (1,440 minutes)
- **Incident 1**: 30 minutes
- **Incident 2**: 45 minutes
- **Incident 3**: 15 minutes
- **Total Downtime**: 90 minutes
- **Result**: 93.75% uptime, 90 minutes downtime
- **Calculation**: (1,440 - 90) / 1,440 × 100 = 93.75%

### Scenario 4: Active Incident (Not Resolved)
- **Period**: 1 day (1,440 minutes)
- **Incident**: Started at 10:00 AM, still active (current time: 2:00 PM)
- **Downtime**: 4 hours (240 minutes) and counting
- **Result**: 83.33% uptime (decreasing as time passes)

### Scenario 5: With Maintenance Window
- **Period**: 1 day (1,440 minutes)
- **Incident**: 2 hours (120 minutes)
- **Maintenance Window**: 30 minutes (overlaps with incident, `disable_alerts = true`)
- **Adjusted Downtime**: 120 - 30 = 90 minutes
- **Result**: 93.75% uptime, 90 minutes downtime

---

## Summary

The system uses a **hierarchical approach**:

1. **First Priority**: Calculate from `DowntimeIncident` records (most accurate)
2. **Second Priority**: Estimate from `MonitoringCheck` records (fallback)
3. **Always**: Exclude maintenance windows from downtime
4. **Always**: Use real-time elapsed minutes for "today" calculations

This ensures accurate, real-time uptime tracking that reflects the actual availability of monitored applications.
