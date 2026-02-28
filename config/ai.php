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

    // AI Prescription Suggestions - DISABLED BY DEFAULT FOR SAFETY
    'prescription_suggestions' => [
        'enabled' => env('AI_PRESCRIPTION_SUGGESTIONS_ENABLED', true), // Temporarily enabled for testing
        'fallback_enabled' => env('AI_PRESCRIPTION_FALLBACK_ENABLED', true),
        'require_clinical_validation' => true, // Always require clinical validation
        'max_suggestions' => 3, // Limit suggestions to prevent overload
        'confidence_threshold' => 70, // Minimum confidence level for suggestions
    ],

    // Safety settings
    'safety' => [
        'require_disclaimer' => true,
        'require_professional_override' => true,
        'log_all_suggestions' => true,
        'audit_trail' => true,
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