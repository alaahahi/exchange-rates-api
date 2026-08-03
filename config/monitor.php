<?php

return [

    'enabled' => env('MONITOR_ENABLED', true),

    'project_name' => env('MONITOR_PROJECT_NAME', env('APP_NAME', 'Laravel')),

    'capture_web' => env('MONITOR_CAPTURE_WEB', true),
    'capture_api' => env('MONITOR_CAPTURE_API', true),
    'capture_console' => env('MONITOR_CAPTURE_CONSOLE', true),
    'capture_queue' => env('MONITOR_CAPTURE_QUEUE', true),

    'log_path' => storage_path('logs/monitor'),

    'slow_query_threshold_ms' => (int) env('MONITOR_SLOW_QUERY_MS', 500),
    'slow_request_threshold_ms' => (int) env('MONITOR_SLOW_REQUEST_MS', 2000),
    'max_request_duration_ms' => (int) env('MONITOR_MAX_REQUEST_MS', 300000),

    'ignore_routes' => [
        'monitor.dashboard',
        'monitor.status',
        'monitor.api.status',
        'monitor.api.metrics',
        'monitor.api.alerts',
        'monitor.api.logs',
        'monitor.api.dates',
        'monitor.api.overview',
        'monitor.api.laravel-logs',
        'monitor.api.laravel-log-files',
        'monitor.api.clear-logs',
    ],

    'ignore_path_prefixes' => [
        '_debugbar',
        'livewire',
        'monitor',
    ],

    'ignore_commands' => [
        'monitor:clean',
        'schedule:run',
    ],

    'alert_thresholds' => [
        'threads_connected' => (int) env('MONITOR_ALERT_THREADS', 100),
        'response_time_ms' => (int) env('MONITOR_ALERT_RESPONSE_MS', 5000),
        'memory_mb' => (int) env('MONITOR_ALERT_MEMORY_MB', 128),
        'query_time_ms' => (int) env('MONITOR_ALERT_QUERY_MS', 3000),
    ],

    'retention_days' => (int) env('MONITOR_RETENTION_DAYS', 30),

    // Token required for destructive Hub actions (clear logs). Leave empty to disable clear API.
    'clear_token' => env('MONITOR_CLEAR_TOKEN', ''),

    // Public API — no auth. Restrict via firewall/VPN or set MONITOR_CORS_ORIGIN in production.
    'cors_origin' => env('MONITOR_CORS_ORIGIN', '*'),

    'api_middleware' => array_filter(explode(',', env('MONITOR_API_MIDDLEWARE', ''))),

    'dashboard_middleware' => array_filter(explode(',', env('MONITOR_DASHBOARD_MIDDLEWARE', ''))),
    'status_middleware' => array_filter(explode(',', env('MONITOR_STATUS_MIDDLEWARE', ''))),

    'snapshot_db_every_request' => env('MONITOR_DB_SNAPSHOT', true),

  /*
    |--------------------------------------------------------------------------
    | Laravel application logs (storage/logs/laravel*.log)
    |--------------------------------------------------------------------------
    */
    'laravel_log' => [
        'enabled' => env('MONITOR_LARAVEL_LOG_ENABLED', true),
        'path' => env('MONITOR_LARAVEL_LOG_PATH', storage_path('logs')),
        'patterns' => array_filter(array_map('trim', explode(',', env(
            'MONITOR_LARAVEL_LOG_PATTERNS',
            'laravel.log,laravel-*.log'
        )))),
        'default_limit' => (int) env('MONITOR_LARAVEL_LOG_LIMIT', 100),
        'max_limit' => (int) env('MONITOR_LARAVEL_LOG_MAX_LIMIT', 500),
        'max_read_bytes' => (int) env('MONITOR_LARAVEL_LOG_MAX_BYTES', 2097152),
        'include_in_overview' => env('MONITOR_LARAVEL_LOG_IN_OVERVIEW', true),
        'overview_limit' => (int) env('MONITOR_LARAVEL_LOG_OVERVIEW_LIMIT', 30),
    ],

];
