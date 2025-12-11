<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for system monitoring, alerting, and metrics collection.
    | This file controls the behavior of the monitoring system.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for health check endpoints and service monitoring.
    |
    */

    'health_checks' => [
        'cache_timeout' => env('HEALTH_CACHE_TIMEOUT', 300), // 5 minutes
        'database_timeout' => env('HEALTH_DB_TIMEOUT', 30), // 30 seconds
        'external_timeout' => env('HEALTH_EXTERNAL_TIMEOUT', 10), // 10 seconds
        'redis_timeout' => env('HEALTH_REDIS_TIMEOUT', 5), // 5 seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configuration for metrics collection and storage.
    |
    */

    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'collection_interval' => env('METRICS_INTERVAL', 60), // seconds
        'retention_days' => env('METRICS_RETENTION_DAYS', 30),
        'histogram_buckets' => [
            'response_time' => [0.1, 0.5, 1.0, 2.0, 5.0, 10.0],
            'memory_usage' => [1048576, 5242880, 10485760, 52428800], // 1MB, 5MB, 10MB, 50MB
            'query_time' => [0.01, 0.1, 1.0, 5.0, 30.0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for alert generation, escalation, and notification.
    |
    */

    'alerts' => [
        'enabled' => env('ALERTS_ENABLED', true),
        'check_interval' => env('ALERT_CHECK_INTERVAL', 300), // 5 minutes
        'max_alerts_per_rule' => env('MAX_ALERTS_PER_RULE', 10),
        'auto_resolve_enabled' => env('AUTO_RESOLVE_ALERTS', true),
        'escalation_enabled' => env('ALERT_ESCALATION_ENABLED', true),

        'severity_levels' => [
            'info' => 1,
            'low' => 2,
            'medium' => 3,
            'high' => 4,
            'critical' => 5,
        ],

        'default_channels' => ['email'],
        'critical_channels' => ['email', 'sms'],

        'escalation_delays' => [
            'info' => [15, 60, 240], // minutes
            'low' => [30, 120, 480],
            'medium' => [10, 60, 240],
            'high' => [5, 30, 120],
            'critical' => [2, 10, 60],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for structured logging and log aggregation.
    |
    */

    'logging' => [
        'structured_enabled' => env('STRUCTURED_LOGGING_ENABLED', true),
        'log_levels' => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
        'max_log_size' => env('MAX_LOG_SIZE', 100 * 1024 * 1024), // 100MB
        'retention_days' => env('LOG_RETENTION_DAYS', 30),

        'channels' => [
            'security' => [
                'enabled' => true,
                'level' => env('LOG_SECURITY_LEVEL', 'info'),
                'path' => storage_path('logs/security.log'),
            ],
            'performance' => [
                'enabled' => true,
                'level' => env('LOG_PERFORMANCE_LEVEL', 'info'),
                'path' => storage_path('logs/performance.log'),
            ],
            'business' => [
                'enabled' => true,
                'level' => env('LOG_BUSINESS_LEVEL', 'info'),
                'path' => storage_path('logs/business.log'),
            ],
            'error' => [
                'enabled' => true,
                'level' => env('LOG_ERROR_LEVEL', 'error'),
                'path' => storage_path('logs/error.log'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configuration for different notification channels.
    |
    */

    'notifications' => [
        'email' => [
            'enabled' => env('NOTIFICATIONS_EMAIL_ENABLED', true),
            'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', 'admin@example.com')),
            'from' => env('ALERT_EMAIL_FROM', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
            'subject_prefix' => env('ALERT_EMAIL_SUBJECT_PREFIX', '[ALERT] '),
        ],

        'sms' => [
            'enabled' => env('NOTIFICATIONS_SMS_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'),
            'critical_only' => env('SMS_CRITICAL_ONLY', true),
        ],

        'slack' => [
            'enabled' => env('NOTIFICATIONS_SLACK_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#alerts'),
            'username' => env('SLACK_USERNAME', 'System Monitor'),
        ],

        'webhook' => [
            'enabled' => env('NOTIFICATIONS_WEBHOOK_ENABLED', false),
            'url' => env('WEBHOOK_URL'),
            'secret' => env('WEBHOOK_SECRET'),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Medicine-AI-Monitor/1.0',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Default thresholds for performance monitoring and alerting.
    |
    */

    'thresholds' => [
        'response_time' => [
            'warning' => env('RESPONSE_TIME_WARNING_MS', 1000), // 1 second
            'critical' => env('RESPONSE_TIME_CRITICAL_MS', 3000), // 3 seconds
        ],

        'error_rate' => [
            'warning' => env('ERROR_RATE_WARNING_PERCENT', 5), // 5%
            'critical' => env('ERROR_RATE_CRITICAL_PERCENT', 10), // 10%
        ],

        'memory_usage' => [
            'warning' => env('MEMORY_USAGE_WARNING_PERCENT', 80), // 80%
            'critical' => env('MEMORY_USAGE_CRITICAL_PERCENT', 95), // 95%
        ],

        'disk_usage' => [
            'warning' => env('DISK_USAGE_WARNING_PERCENT', 85), // 85%
            'critical' => env('DISK_USAGE_CRITICAL_PERCENT', 95), // 95%
        ],

        'cpu_usage' => [
            'warning' => env('CPU_USAGE_WARNING_PERCENT', 80), // 80%
            'critical' => env('CPU_USAGE_CRITICAL_PERCENT', 95), // 95%
        ],

        'database_connections' => [
            'warning' => env('DB_CONNECTIONS_WARNING', 80), // 80% of max
            'critical' => env('DB_CONNECTIONS_CRITICAL', 95), // 95% of max
        ],

        'queue_size' => [
            'warning' => env('QUEUE_SIZE_WARNING', 1000),
            'critical' => env('QUEUE_SIZE_CRITICAL', 5000),
        ],

        'failed_jobs' => [
            'warning' => env('FAILED_JOBS_WARNING_RATE', 0.05), // 5%
            'critical' => env('FAILED_JOBS_CRITICAL_RATE', 0.15), // 15%
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the monitoring dashboard display.
    |
    */

    'dashboard' => [
        'refresh_interval' => env('DASHBOARD_REFRESH_SECONDS', 60), // 1 minute
        'max_alerts_display' => env('DASHBOARD_MAX_ALERTS', 50),
        'chart_points' => env('DASHBOARD_CHART_POINTS', 24), // 24 data points
        'time_ranges' => ['1h', '6h', '24h', '7d', '30d'],

        'widgets' => [
            'system_status' => ['enabled' => true, 'position' => 1],
            'performance_metrics' => ['enabled' => true, 'position' => 2],
            'active_alerts' => ['enabled' => true, 'position' => 3],
            'service_health' => ['enabled' => true, 'position' => 4],
            'recent_logs' => ['enabled' => true, 'position' => 5],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Configuration for maintenance mode monitoring.
    |
    */

    'maintenance' => [
        'enabled' => env('MAINTENANCE_MODE_ENABLED', false),
        'bypass_alerts' => env('MAINTENANCE_BYPASS_ALERTS', true),
        'notification_override' => env('MAINTENANCE_NOTIFICATION_OVERRIDE', 'System is currently in maintenance mode'),
    ],
];
