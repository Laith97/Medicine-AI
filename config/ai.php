<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Feature Flags
    |--------------------------------------------------------------------------
    |
    | These flags control the availability of AI-powered features in the application.
    | Set to true to enable, false to disable.
    */

    'enabled' => env('AI_ENABLED', true),

    // Temporary override for testing - force enable AI
    // 'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | AI Prescription Suggestions
    |--------------------------------------------------------------------------
    |
    | Controls whether AI-powered prescription suggestions are available.
    | When disabled, fallback logic will be used instead.
    */

    'prescription_suggestions' => [
        'enabled' => env('AI_PRESCRIPTION_SUGGESTIONS_ENABLED', true),
        'fallback_enabled' => env('AI_PRESCRIPTION_FALLBACK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Additional configuration for AI services.
    */

    'services' => [
        'openai' => [
            'enabled' => env('AI_OPENAI_ENABLED', true),
        ],
    ],

];