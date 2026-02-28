<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of the WebSockets dashboard that
    | can be used to get insight into the current connections.
    |
    */

    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Server Settings
    |--------------------------------------------------------------------------
    |
    | This array contains the host and port on which the WebSocket server
    | should start.
    |
    */

    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => false,
            'enable_statistics' => true,
            'allowed_origins' => ['*'],
            'allowed_ips' => ['*'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Handler Settings
    |--------------------------------------------------------------------------
    |
    | Here you can define custom WebSocket handlers for specific routes.
    |
    */

    'handlers' => [
        '/ws/medical-audio' => \App\WebSockets\MedicalAudioSocket::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Settings
    |--------------------------------------------------------------------------
    |
    | By default, the WebSockets server listens on all IPv4 addresses. You
    | can change the listening address by changing the host below.
    |
    */

    'ssl' => [
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel Manager
    |--------------------------------------------------------------------------
    |
    | This class handles how channel persistence is handled.
    | By default, persistence is stored in an array by the running webserver.
    | The only requirement is that the class should adhere to the
    | \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager contract.
    |
    */

    'channel_manager' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager::class,

    /*
    |--------------------------------------------------------------------------
    | Statistics Settings
    |--------------------------------------------------------------------------
    |
    | Here you can specify the interval in seconds at which statistics should
    | be logged.
    |
    */

    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
        'logger' => BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
        'perform_dns_lookup' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Medical Audio Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to medical audio processing and transcription.
    |
    */

    'medical_audio' => [
        'max_connections_per_user' => 3,
        'max_audio_chunk_size' => 8192,
        'transcription_provider' => env('MEDICAL_TRANSCRIPTION_PROVIDER', 'google'),
        'enable_speaker_diarization' => true,
        'max_speakers' => 3,
        'audio_format' => 'LINEAR16',
        'sample_rate' => 16000,
        'language_code' => 'en-US',
        'alternative_language_codes' => ['ar-SA'],
        'enable_automatic_punctuation' => true,
        'enable_word_time_offsets' => true,
        'model' => 'medical_dictation',
        'use_enhanced' => true,
    ],

];