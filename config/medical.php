<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Medical Transcription Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls the default transcription provider for the ambient
    | listening feature. Supported: "google", "assemblyai"
    |
    */
    'transcription_provider' => env('MEDICAL_TRANSCRIPTION_PROVIDER', 'assemblyai'),

    /*
    |--------------------------------------------------------------------------
    | AssemblyAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for AssemblyAI integration.
    |
    */
    'assemblyai' => [
        'api_key' => env('ASSEMBLYAI_API_KEY'),
        'sample_rate' => 16000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Retention Policy
    |--------------------------------------------------------------------------
    |
    | How many hours to keep raw audio files before automatic deletion.
    |
    */
    'audio_retention_hours' => env('MEDICAL_AUDIO_RETENTION_HOURS', 72),
];
