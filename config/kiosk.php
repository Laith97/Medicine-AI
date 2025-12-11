<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kiosk Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the kiosk system including
    | software versions, update settings, and hardware capabilities.
    |
    */

    'software_version' => env('KIOSK_SOFTWARE_VERSION', '1.0.0'),

    'update' => [
        'enabled' => env('KIOSK_AUTO_UPDATE', true),
        'check_interval_minutes' => env('KIOSK_UPDATE_CHECK_INTERVAL', 60),
        'force_update' => env('KIOSK_FORCE_UPDATE', false),
        'update_url' => env('KIOSK_UPDATE_URL', null),
    ],

    'hardware' => [
        'default_resolution' => env('KIOSK_DEFAULT_RESOLUTION', '1920x1080'),
        'touch_enabled' => env('KIOSK_TOUCH_ENABLED', true),
        'printer_support' => env('KIOSK_PRINTER_SUPPORT', false),
        'card_reader_support' => env('KIOSK_CARD_READER_SUPPORT', true),
        'biometric_support' => env('KIOSK_BIOMETRIC_SUPPORT', false),
        'voice_assistant' => env('KIOSK_VOICE_ASSISTANT', true),
    ],

    'security' => [
        'session_timeout_minutes' => env('KIOSK_SESSION_TIMEOUT', 30),
        'rate_limit_requests' => env('KIOSK_RATE_LIMIT_REQUESTS', 100),
        'rate_limit_window_minutes' => env('KIOSK_RATE_LIMIT_WINDOW', 15),
        'max_failed_attempts' => env('KIOSK_MAX_FAILED_ATTEMPTS', 5),
        'lockout_duration_minutes' => env('KIOSK_LOCKOUT_DURATION', 15),
    ],

    'monitoring' => [
        'ping_interval_seconds' => env('KIOSK_PING_INTERVAL', 60),
        'offline_threshold_minutes' => env('KIOSK_OFFLINE_THRESHOLD', 5),
        'health_check_enabled' => env('KIOSK_HEALTH_CHECK', true),
        'performance_monitoring' => env('KIOSK_PERFORMANCE_MONITORING', true),
    ],

    'ui' => [
        'high_contrast_mode' => env('KIOSK_HIGH_CONTRAST', false),
        'large_text_mode' => env('KIOSK_LARGE_TEXT', false),
        'voice_guidance' => env('KIOSK_VOICE_GUIDANCE', true),
        'language' => env('KIOSK_LANGUAGE', 'en'),
        'theme' => env('KIOSK_THEME', 'default'),
    ],

    'commands' => [
        'allowed_commands' => [
            'restart',
            'shutdown',
            'update',
            'diagnostics',
            'status',
            'reboot',
            'clear_cache',
            'update_config',
        ],
        'dangerous_commands' => [
            'shutdown',
            'reboot',
        ],
        'command_timeout_seconds' => env('KIOSK_COMMAND_TIMEOUT', 300),
    ],

    'deployment' => [
        'auto_register' => env('KIOSK_AUTO_REGISTER', true),
        'default_status' => env('KIOSK_DEFAULT_STATUS', 'inactive'),
        'require_approval' => env('KIOSK_REQUIRE_APPROVAL', false),
        'bulk_operations' => env('KIOSK_BULK_OPERATIONS', true),
    ],

    'notifications' => [
        'offline_alerts' => env('KIOSK_OFFLINE_ALERTS', true),
        'update_available' => env('KIOSK_UPDATE_NOTIFICATIONS', true),
        'security_events' => env('KIOSK_SECURITY_NOTIFICATIONS', true),
        'performance_alerts' => env('KIOSK_PERFORMANCE_ALERTS', true),
    ],

    'api' => [
        'base_url' => env('KIOSK_API_BASE_URL', env('APP_URL') . '/api'),
        'timeout_seconds' => env('KIOSK_API_TIMEOUT', 30),
        'retry_attempts' => env('KIOSK_API_RETRY_ATTEMPTS', 3),
        'retry_delay_seconds' => env('KIOSK_API_RETRY_DELAY', 5),
    ],

    'logging' => [
        'level' => env('KIOSK_LOG_LEVEL', 'info'),
        'max_files' => env('KIOSK_LOG_MAX_FILES', 30),
        'max_size_mb' => env('KIOSK_LOG_MAX_SIZE_MB', 100),
        'remote_logging' => env('KIOSK_REMOTE_LOGGING', false),
    ],

];
