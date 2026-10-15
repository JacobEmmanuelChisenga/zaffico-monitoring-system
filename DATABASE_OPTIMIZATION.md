# Database Optimization Guide

## Problem

With high-frequency monitoring (every 15 seconds), your database can grow very quickly:
- **6 applications** × **4 checks/minute** = **24 records/minute**
- **1,440 records/hour** = **34,560 records/day**
- **~12.6 million records/year**

This can cause:
- Database bloat and slow queries
- Increased storage costs
- Potential crashes on low-memory systems
- Slow backups

## Solution
Implemented a comprehensive data retention and optimization strategy:

### 1. **Storage Optimization** ✅

**What Changed:**
- `response_body` is now only stored for **failed checks** (errors/down status)
- Successful checks don't store `response_body`, saving significant space
- Response bodies are truncated to 10KB max to prevent huge records

**Space Savings:**
- Before: ~500 bytes per record (with response_body)
- After: ~100 bytes per record (without response_body for successful checks)
- **~80% reduction** in storage for successful checks

**Configuration:**
```env
# Only store response_body for successful checks (default: false)
STORE_RESPONSE_BODY_ON_SUCCESS=false

# Maximum response body size in bytes (default: 10000 = 10KB)
MAX_RESPONSE_BODY_SIZE=10000
```

### 2. **Data Retention Policy** ✅

**Default Retention Periods:**
- **Monitoring Checks**: 30 days (detailed records)
- **Resolved Incidents**: 90 days
- **Uptime Reports**: 365 days (aggregated data)

**Configuration:**
```env
# Keep detailed monitoring checks for this many days
MONITORING_RETENTION_DAYS=30

# Keep resolved incidents for this many days
INCIDENTS_RETENTION_DAYS=90

# Keep uptime reports for this many days
UPTIME_REPORTS_RETENTION_DAYS=365
```

**Why This Works:**
- Recent data (30 days) is kept for detailed analysis
- Older data is automatically deleted
- Uptime reports (aggregated) are kept longer for historical trends
- Active incidents are **never** deleted

### 3. **Automatic Cleanup** ✅

**Scheduled Cleanup:**
- Runs **daily at 2:00 AM** (configurable)
- Deletes old data in batches to avoid long-running queries
- Logs all cleanup operations

**Manual Cleanup:**
```bash
# See what would be deleted (dry run)
php artisan monitoring:cleanup --dry-run

# Actually delete old data
php artisan monitoring:cleanup --force
```000

### 4. **Database Indexes** ✅

Already optimized with indexes on:
- `(application_id, checked_at)` - Most common queries
- `(status, checked_at)` - Downtime reports
- `response_time` - Slow response alerts

## Expected Results

### Storage Reduction:
- **Before**: ~12.6M records/year × 500 bytes = **~6 GB/year**
- **After**: ~1M records/year × 100 bytes = **~100 MB/year**
- **~98% reduction** in storage

### Database Size:
- **Before**: Growing indefinitely (could reach 10+ GB in a year)
- **After**: Stabilizes around **500 MB - 1 GB** (with 30-day retention)

### Query Performance:
- Faster queries on recent data (smaller dataset)
- Better index efficiency
- Reduced memory usage

## Configuration Examples

### Conservative (More Data):
```env
MONITORING_RETENTION_DAYS=90
INCIDENTS_RETENTION_DAYS=180
UPTIME_REPORTS_RETENTION_DAYS=730
```

### Aggressive (Less Data):
```env
MONITORING_RETENTION_DAYS=7
INCIDENTS_RETENTION_DAYS=30
UPTIME_REPORTS_RETENTION_DAYS=90
```

### Production Recommended:
```env
MONITORING_RETENTION_DAYS=30
INCIDENTS_RETENTION_DAYS=90
UPTIME_REPORTS_RETENTION_DAYS=365
STORE_RESPONSE_BODY_ON_SUCCESS=false
MAX_RESPONSE_BODY_SIZE=10000
AUTO_CLEANUP_ENABLED=true
```

## Monitoring Cleanup

### Check Current Database Size:
```bash
php artisan monitoring:cleanup --dry-run
```

This shows:
- How many records would be deleted
- Current database statistics
- Storage estimates

### View Cleanup Logs:
```bash
tail -f storage/logs/cleanup.log
```

## Best Practices

1. **Start with defaults** (30 days retention)
2. **Monitor database size** weekly
3. **Adjust retention** based on your needs:
   - Need more history? Increase retention days
   - Running out of space? Decrease retention days
4. **Run manual cleanup** before major operations if needed
5. **Backup before cleanup** (though cleanup only deletes old data)

## Troubleshooting

### Cleanup Not Running?
Check if auto-cleanup is enabled:
```env
AUTO_CLEANUP_ENABLED=true
```

### Database Still Growing?
1. Check retention settings are correct
2. Verify cleanup command is running: `php artisan schedule:list`
3. Run manual cleanup: `php artisan monitoring:cleanup --force`

### Need More History?
Increase retention days in `.env`:
```env
MONITORING_RETENTION_DAYS=60  # Keep 60 days instead of 30
```

## Migration Notes

**No migration needed!** The optimization is backward compatible:
- Existing data is preserved
- New checks use optimized storage
- Cleanup only affects old data (based on retention policy)

## Summary

✅ **Storage optimized** - 80% reduction per record
✅ **Automatic cleanup** - Runs daily at 2 AM
✅ **Configurable retention** - Adjust via `.env`
✅ **Safe deletion** - Only old data, active incidents preserved
✅ **Better performance** - Faster queries, less memory usage

Your database will now stay healthy and performant even with high-frequency monitoring!

