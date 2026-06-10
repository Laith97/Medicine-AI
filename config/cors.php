<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines which cross-origin domains are allowed to
    | access your resources, and the headers that are exposed to them.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth', 'login', 'logout', 'pusher/auth', 'api/notifications/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];