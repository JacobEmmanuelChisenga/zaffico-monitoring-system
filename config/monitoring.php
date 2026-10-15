<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Retention Settings
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep monitoring data before cleanup.
    | These settings help prevent database bloat from high-frequency checks.
    |
    */

    'retention' => [
        // Keep detailed monitoring checks for this many days
        // After this period, old checks will be deleted
        // Default: 30 days (recommended: 7-90 days)
        'monitoring_checks_days' => env('MONITORING_RETENTION_DAYS', 30),

        // Keep resolved incidents for this many days
        // Active incidents are never deleted
        'resolved_incidents_days' => env('INCIDENTS_RETENTION_DAYS', 90),

        // Keep old uptime reports for this many days
        // Daily reports older than this will be deleted
        'uptime_reports_days' => env('UPTIME_REPORTS_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Storage Optimization
    |--------------------------------------------------------------------------
    |
    | Optimize what data is stored to reduce database size.
    |
    */

    'storage' => [
        // Only store response_body for failed checks (errors/down status)
        // Successful checks don't need response_body, saving significant space
        'store_response_body_on_success' => env('STORE_RESPONSE_BODY_ON_SUCCESS', false),

        // Maximum size of response_body to store (in bytes)
        // Prevents storing extremely large responses
        'max_response_body_size' => env('MAX_RESPONSE_BODY_SIZE', 10000), // 10KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic cleanup behavior.
    |
    */

    'cleanup' => [
        // Run cleanup in batches to avoid long-running queries
        'batch_size' => env('CLEANUP_BATCH_SIZE', 1000),

        // Enable automatic cleanup via scheduler
        'auto_cleanup_enabled' => env('AUTO_CLEANUP_ENABLED', true),
    ],
];

