<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default SMS provider that will be used to send
    | SMS messages. You may set this to any of the providers defined in the
    | "providers" array below.
    |
    | Supported: "twilio", "nexmo", "log"
    |
    */

    'default_provider' => env('SMS_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | SMS Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the SMS providers for your application. Each
    | provider has its own configuration options.
    |
    */

    'providers' => [
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from_number' => env('TWILIO_FROM_NUMBER'),
        ],

        'nexmo' => [
            'api_key' => env('NEXMO_API_KEY'),
            'api_secret' => env('NEXMO_API_SECRET'),
            'from_number' => env('NEXMO_FROM_NUMBER'),
        ],

        'log' => [
            // No configuration needed for log provider
        ],
    ],
];
