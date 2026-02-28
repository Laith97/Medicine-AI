<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverb Server Port
    |--------------------------------------------------------------------------
    |
    | This is the port that Reverb will assign to listen for connections.
    |
    */

    'port' => env('REVERB_PORT', 8080),

    /*
    |--------------------------------------------------------------------------
    | Reverb Host
    |--------------------------------------------------------------------------
    |
    | This is the hostname that Reverb will use to listen for connections.
    |
    */

    'host' => env('REVERB_HOST', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Reverb route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Databases
    |--------------------------------------------------------------------------
    |
    | Reverb uses a Redis database to store information about connections
    | and channels. You may define a single Redis database connection
    | that will be used by all Reverb features.
    |
    */

    'database' => [
        'driver' => 'file',
        'connection' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting is enabled by default with a limit of 100 requests per
    | minute. You can disable rate limiting by setting the "enabled" option
    | to false, or adjust the configuration to suit your needs.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'limit' => 300,
        'decay' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Apps
    |--------------------------------------------------------------------------
    |
    | Here you may define the Reverb applications that will be hosted
    | by the server. These applications will be used to authenticate
    | incoming connections and broadcast events.
    |
    */

    'apps' => [
        [
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
            'allowed_origins' => [
                $_SERVER['SERVER_NAME'] ?? '*',
                env('APP_URL', '*'),
            ],
        ],
    ],

];