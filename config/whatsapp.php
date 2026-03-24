<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default WhatsApp provider that will be used to send
    | WhatsApp messages. You may set this to any of the providers defined in the
    | "providers" array below.
    |
    | Supported: "twilio", "graph_api", "log"
    |
    */

    'default_provider' => env('WHATSAPP_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the WhatsApp providers for your application. Each
    | provider has its own configuration options.
    |
    */

    'providers' => [
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_WHATSAPP_FROM'), // e.g., whatsapp:+1234567890
        ],

        'graph_api' => [
            'access_token' => env('WHATSAPP_BUSINESS_ACCESS_TOKEN'),
            'phone_number_id' => env('WHATSAPP_BUSINESS_PHONE_NUMBER_ID'),
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
    | List of available WhatsApp providers for admin selection
    |
    */

    'available_providers' => [
        'twilio' => 'Twilio WhatsApp',
        'graph_api' => 'WhatsApp Business API',
        'log' => 'Log Only (Testing)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp webhook verification and handling.
    |
    */

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'medcura-webhook-verify'),

    /*
    |--------------------------------------------------------------------------
    | Message Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of WhatsApp messages and status updates.
    |
    */

    'log_messages' => env('WHATSAPP_LOG_MESSAGES', true),
];