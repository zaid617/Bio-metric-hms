<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZKTeco Device Configuration
    |--------------------------------------------------------------------------
    */

    'default_port' => env('ZKTECO_DEFAULT_PORT', 4370),
    'connection_timeout' => env('ZKTECO_CONNECTION_TIMEOUT', 30),
    'sync_interval_minutes' => env('ZKTECO_SYNC_INTERVAL', 5),
    'lib_log_path' => env('ZKTECO_LIB_LOG_PATH', storage_path('logs/zkteco/error.log')),

    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    */

    'auto_checkout_hours' => env('ATTENDANCE_AUTO_CHECKOUT_HOURS', 12),
    'overtime_threshold_minutes' => env('ATTENDANCE_OVERTIME_THRESHOLD', 30),
    'default_shift_hours' => env('ATTENDANCE_DEFAULT_SHIFT_HOURS', 8),
    'working_days_per_month' => env('ATTENDANCE_WORKING_DAYS_PER_MONTH', 26),

    /*
    |--------------------------------------------------------------------------
    | Payroll Configuration
    |--------------------------------------------------------------------------
    */

    'overtime_rate_multiplier' => env('PAYROLL_OVERTIME_MULTIPLIER', 1.5),

    /*
    |--------------------------------------------------------------------------
    | Auto-Mapping Configuration
    |--------------------------------------------------------------------------
    */

    'enable_auto_mapping' => env('ZKTECO_ENABLE_AUTO_MAPPING', true),

    /**
     * Strategies for auto-mapping device users to employees
     * Available strategies: 'employee_id', 'name_exact', 'card_number'
     */
    'mapping_strategies' => [
        'employee_id',  // Match by employee ID
        'name_exact',   // Exact name match
        'card_number',  // Match by card number
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */

    'sync_users_on_device_add' => true,
    'sync_logs_retention_days' => 90, // Keep sync logs for 90 days
    'allow_clear_after_fetch' => env('ZKTECO_ALLOW_CLEAR_AFTER_FETCH', false),
];
