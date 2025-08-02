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
    | Supported: "twilio", "plivo", "messagebird", "unifonic", "smsgatewayhub", "log"
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

        'plivo' => [
            'auth_id' => env('PLIVO_AUTH_ID'),
            'auth_token' => env('PLIVO_AUTH_TOKEN'),
            'from_number' => env('PLIVO_FROM_NUMBER'),
        ],

        'messagebird' => [
            'access_key' => env('MESSAGEBIRD_ACCESS_KEY'),
            'from_number' => env('MESSAGEBIRD_FROM_NUMBER'),
        ],

        'unifonic' => [
            'app_sid' => env('UNIFONIC_APP_SID'),
            'sender_id' => env('UNIFONIC_SENDER_ID'),
        ],

        'smsgatewayhub' => [
            'email' => env('SMSGATEWAYHUB_EMAIL'),
            'password' => env('SMSGATEWAYHUB_PASSWORD'),
            'device' => env('SMSGATEWAYHUB_DEVICE'),
        ],

        'log' => [
            // No configuration needed for log provider
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Providers
    |--------------------------------------------------------------------------
    |
    | List of available SMS providers for admin selection
    |
    */

    'available_providers' => [
        'twilio' => 'Twilio',
        'plivo' => 'Plivo',
        'messagebird' => 'MessageBird',
        'unifonic' => 'Unifonic',
        'smsgatewayhub' => 'SMS Gateway Hub',
        'log' => 'Log Only (Testing)',
    ],
];
