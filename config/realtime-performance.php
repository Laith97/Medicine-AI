<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Real-time Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time appointment broadcasting performance optimizations
    | including connection pooling, load balancing, compression, and monitoring.
    |
    */

    'connection_pool' => [
        'max_connections' => env('REALTIME_MAX_CONNECTIONS', 10),
        'connection_timeout' => env('REALTIME_CONNECTION_TIMEOUT', 30), // seconds
        'idle_timeout' => env('REALTIME_IDLE_TIMEOUT', 300), // 5 minutes
        'health_check_interval' => env('REALTIME_HEALTH_CHECK_INTERVAL', 30), // seconds
    ],

    'load_balancer' => [
        'enabled' => env('REALTIME_LOAD_BALANCER_ENABLED', true),
        'servers' => [
            [
                'id' => 'primary',
                'host' => env('PUSHER_HOST', 'api.pusherapp.com'),
                'weight' => env('REALTIME_PRIMARY_WEIGHT', 100),
                'enabled' => true,
                'max_connections' => env('REALTIME_MAX_CONNECTIONS', 1000),
            ],
        ],
        'additional_servers' => env('REALTIME_ADDITIONAL_SERVERS', []),
    ],

    'compression' => [
        'enabled' => env('REALTIME_COMPRESSION_ENABLED', true),
        'threshold' => env('REALTIME_COMPRESSION_THRESHOLD', 1024), // bytes
        'level' => env('REALTIME_COMPRESSION_LEVEL', 6), // 1-9, 6 is good balance
        'algorithm' => env('REALTIME_COMPRESSION_ALGORITHM', 'gzip'),
    ],

    'rate_limiting' => [
        'max_broadcasts_per_minute' => env('REALTIME_MAX_BROADCASTS_MINUTE', 60),
        'max_broadcasts_per_hour' => env('REALTIME_MAX_BROADCASTS_HOUR', 1000),
        'burst_limit' => env('REALTIME_BURST_LIMIT', 10),
        'throttle_enabled' => env('REALTIME_THROTTLE_ENABLED', true),
    ],

    'caching' => [
        'enabled' => env('REALTIME_CACHING_ENABLED', true),
        'ttl' => env('REALTIME_CACHE_TTL', 300), // 5 minutes
        'long_ttl' => env('REALTIME_LONG_CACHE_TTL', 3600), // 1 hour
        'warmup_enabled' => env('REALTIME_CACHE_WARMUP_ENABLED', true),
        'warmup_interval' => env('REALTIME_CACHE_WARMUP_INTERVAL', 600), // 10 minutes
    ],

    'monitoring' => [
        'enabled' => env('REALTIME_MONITORING_ENABLED', true),
        'metrics_ttl' => env('REALTIME_METRICS_TTL', 3600), // 1 hour
        'analytics_ttl' => env('REALTIME_ANALYTICS_TTL', 86400), // 24 hours
        'alerts_enabled' => env('REALTIME_ALERTS_ENABLED', true),

        'thresholds' => [
            'broadcast_latency' => env('REALTIME_LATENCY_THRESHOLD', 1000), // ms
            'connection_pool_utilization' => env('REALTIME_POOL_UTILIZATION_THRESHOLD', 0.8), // 80%
            'compression_ratio' => env('REALTIME_COMPRESSION_RATIO_THRESHOLD', 0.7), // 70%
            'error_rate' => env('REALTIME_ERROR_RATE_THRESHOLD', 0.05), // 5%
        ],
    ],

    'optimization' => [
        'batch_broadcasting' => env('REALTIME_BATCH_BROADCASTING', true),
        'lazy_loading' => env('REALTIME_LAZY_LOADING', true),
        'memory_optimization' => env('REALTIME_MEMORY_OPTIMIZATION', true),
        'query_optimization' => env('REALTIME_QUERY_OPTIMIZATION', true),
    ],

    'channels' => [
        'appointment_updates' => env('REALTIME_APPOINTMENT_CHANNEL', 'appointments.today'),
        'user_notifications' => env('REALTIME_USER_CHANNEL_PREFIX', 'user.'),
        'doctor_updates' => env('REALTIME_DOCTOR_CHANNEL_PREFIX', 'doctor.'),
        'admin_updates' => env('REALTIME_ADMIN_CHANNEL', 'admin'),
        'clinic_staff' => env('REALTIME_CLINIC_CHANNEL', 'clinic-staff'),
    ],

    'features' => [
        'on_deck_view' => env('REALTIME_ON_DECK_ENABLED', true),
        'appointment_boards' => env('REALTIME_BOARDS_ENABLED', true),
        'multi_device_sync' => env('REALTIME_MULTI_DEVICE_ENABLED', true),
        'real_time_notifications' => env('REALTIME_NOTIFICATIONS_ENABLED', true),
    ],
];
