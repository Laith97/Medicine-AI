<?php

namespace App\WebSockets;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\StreamingRecognitionConfig;
use Google\Cloud\Speech\V1\AudioEncoding;
use Google\Cloud\Speech\V1\SpeakerDiarizationConfig;
use Google\Cloud\Speech\V1\SpeechContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\MedicalTranscriptionService;

class MedicalAudioSocket implements MessageComponentInterface
{
    protected $activeConnections = [];
    protected $transcriptionService;

    public function __construct()
    {
        // Services will be resolved when needed to avoid DI issues
        $this->transcriptionService = null;
    }

    /**
     * Get transcription service instance
     */
    private function getTranscriptionService()
    {
        if (!$this->transcriptionService) {
            $this->transcriptionService = app(MedicalTranscriptionService::class);
        }
        return $this->transcriptionService;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        // Validate medical visit context
        $query = $connection->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        
        if (!$this->validateMedicalContext($params)) {
            $connection->close();
            return;
        }

        // Store connection with medical context
        $this->activeConnections[$connection->resourceId] = [
            'connection' => $connection,
            'visit_id' => $params['visit_id'] ?? null,
            'token' => $params['token'] ?? null,
            'language' => $params['language'] ?? 'en',
            'speech_client' => null,
            'stream' => null,
            'is_streaming' => false,
            'created_at' => time(),
            'last_activity' => time(),
            'provider' => null
        ];

        Log::info("Medical Audio Socket connected: {$connection->resourceId}", $params);
    }

    public function onMessage(ConnectionInterface $connection, $msg)
    {
        $payload = json_decode($msg, true);
        
        if (!isset($this->activeConnections[$connection->resourceId])) {
            return;
        }

        $session = &$this->activeConnections[$connection->resourceId];

        if ($payload['type'] === 'audio_chunk') {
            $this->processAudioChunk($session, $payload['data']);
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        if (isset($this->activeConnections[$connection->resourceId])) {
            $this->closeStream($this->activeConnections[$connection->resourceId]);
            unset($this->activeConnections[$connection->resourceId]);
        }
        Log::info("Medical Audio Socket disconnected: {$connection->resourceId}");
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        Log::error("Medical Audio Socket error: " . $e->getMessage(), [
            'connection_id' => $connection->resourceId,
            'error_type' => get_class($e),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Send error to client before closing
        try {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Connection error occurred',
                'code' => 'WEBSOCKET_ERROR'
            ]));
        } catch (\Exception $sendError) {
            // Ignore send errors on closing connection
        }
        
        $connection->close();
    }

    protected function validateMedicalContext($params)
    {
        if (empty($params['token']) || empty($params['visit_id'])) {
            Log::warning('Medical Audio Socket: Missing required parameters', $params);
            return false;
        }

        try {
            // Validate token using Laravel's built-in authentication
            // First, try to authenticate using Laravel Sanctum if it's a Sanctum token
            $user = null;
            $userId = null;

            // Try to validate as a Sanctum token first (Laravel Sanctum tokens start differently)
            try {
                // If this is a Sanctum token, we'll try to authenticate it
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($params['token']);

                if ($user) {
                    $userId = $user->tokenable_id; // This gets the user ID from the tokenable relationship
                } else {
                    // If not a Sanctum token, try JWT decoding
                    $decoded = \Firebase\JWT\JWT::decode($params['token'], new \Firebase\JWT\Key(config('app.key'), 'HS256'));

                    // Validate token structure and required claims
                    if (!isset($decoded->sub) || !isset($decoded->exp) || !isset($decoded->iat)) {
                        Log::warning('Medical Audio Socket: Invalid token structure', [
                            'user_id' => $decoded->sub ?? null,
                            'exp' => $decoded->exp ?? null,
                            'iat' => $decoded->iat ?? null
                        ]);
                        return false;
                    }

                    // Check if token is expired
                    $now = time();
                    if ($decoded->exp < $now) {
                        Log::warning('Medical Audio Socket: Token expired', [
                            'user_id' => $decoded->sub,
                            'exp' => $decoded->exp,
                            'current_time' => $now
                        ]);
                        return false;
                    }

                    // Check if token was issued in the future (possible clock drift)
                    if ($decoded->iat > $now) {
                        Log::warning('Medical Audio Socket: Token issued in the future', [
                            'user_id' => $decoded->sub,
                            'iat' => $decoded->iat,
                            'current_time' => $now
                        ]);
                        return false;
                    }

                    $userId = $decoded->sub;
                }
            } catch (\Exception $jwtException) {
                // If JWT decoding fails, try to see if it's an API token
                // Check if the token exists in the users table as an API token
                $userModel = \App\Models\User::where('api_token', $params['token'])->first();
                if ($userModel) {
                    $userId = $userModel->id;
                } else {
                    throw $jwtException; // Re-throw if it's not a recognized token type
                }
            }

            $visitId = $params['visit_id'];

            // Get user and validate access to the specific visit
            $authenticatedUser = \App\Models\User::find($userId);
            if (!$authenticatedUser) {
                Log::warning('Medical Audio Socket: User not found', [
                    'user_id' => $userId
                ]);
                return false;
            }

            // Verify the user has proper access to the appointment/visit
            $appointment = \App\Models\Appointment::find($visitId);
            if (!$appointment) {
                Log::warning('Medical Audio Socket: Appointment not found', [
                    'visit_id' => $visitId,  // This might actually be an appointment ID
                    'user_id' => $userId
                ]);
                return false;
            }

            // Check if the user is the doctor for this appointment or has appropriate permissions
            $hasAccess = false;

            // Check if user is the doctor who created this appointment
            if ($appointment->doctor_id == $userId) {
                $hasAccess = true;
            }
            // Check if user is a sub-user of the doctor who created this appointment
            elseif ($authenticatedUser->parent_user_id) {
                $parentUser = \App\Models\User::find($authenticatedUser->parent_user_id);
                if ($parentUser && $appointment->doctor_id == $parentUser->id) {
                    $hasAccess = true;
                }
            }
            // Check if user is the patient of this appointment
            elseif ($appointment->patient_id == $userId) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                Log::warning('Medical Audio Socket: Unauthorized access to appointment', [
                    'user_id' => $userId,
                    'appointment_id' => $visitId,
                    'appointment_doctor_id' => $appointment->doctor_id ?? null,
                    'appointment_patient_id' => $appointment->patient_id ?? null,
                    'authenticated_user_id' => $authenticatedUser->id
                ]);
                return false;
            }

            return true;

        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::error('Medical Audio Socket: Token has expired', [
                'error' => $e->getMessage(),
                'token' => substr($params['token'], 0, 20) . '...'
            ]);
            return false;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::error('Medical Audio Socket: Invalid token signature', [
                'error' => $e->getMessage(),
                'token' => substr($params['token'], 0, 20) . '...'
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Medical Audio Socket: Token validation failed', [
                'error' => $e->getMessage(),
                'token' => substr($params['token'], 0, 20) . '...',
                'error_type' => get_class($e)
            ]);
            return false;
        }
    }



    protected function startStream(&$session)
    {
        try {
            // Default provider from config
            $provider = config('medical.transcription_provider', 'assemblyai');
            
            // NOTE: We no longer force Google for Arabic as AssemblyAI supports it now
            // The admin can still switch the default provider in the config/env
            
            Log::info('Starting transcription stream', [
                'connection_id' => $session['connection']->resourceId,
                'provider' => $provider,
                'visit_id' => $session['visit_id'],
                'language' => $session['language'] ?? 'auto'
            ]);
            
            if ($provider === 'assemblyai') {
                $this->startAssemblyAIStream($session);
            } else {
                $this->startGoogleStream($session);
            }
            
            $session['is_streaming'] = true;
            $session['provider'] = $provider;
            
        } catch (\Exception $e) {
            Log::error("Failed to start transcription stream: " . $e->getMessage(), [
                'connection_id' => $session['connection']->resourceId,
                'provider' => $provider ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            $session['connection']->send(json_encode([
                'type' => 'error',
                'message' => 'Transcription service unavailable',
                'code' => 'TRANSCRIPTION_SERVICE_ERROR'
            ]));
        }
    }

    protected function startGoogleStream(&$session)
    {
        $speechClient = new SpeechClient([
            'credentials' => config('services.google.cloud.credentials'),
        ]);

        $languageCode = $this->getGoogleLanguageCode($session['language'] ?? 'en');
        $streamingConfig = $this->initializeGoogleStreaming($languageCode);
        
        $stream = $speechClient->streamingRecognize();
        $stream->write(['streaming_config' => $streamingConfig]);

        $session['speech_client'] = $speechClient;
        $session['stream'] = $stream;
        $session['provider'] = 'google';
        
        // Send configuration to client to let them know we are using Google
        $session['connection']->send(json_encode([
            'type' => 'config',
            'provider' => 'google',
            'language' => $languageCode
        ]));
    }

    protected function startAssemblyAIStream(&$session)
    {
        $service = app(\App\Services\AssemblyAIService::class);
        
        // Configure language detection or specific language
        $language = $session['language'] ?? 'auto';
        $config = [];
        
        if ($language === 'auto') {
            $config['language_detection'] = true;
        } else {
            $config['language_code'] = $language;
        }
        
        $config = $service->startRealtimeSession($config);
        $token = $service->getTemporaryToken($config);
        
        $session['assembly_service'] = $service;
        $session['assembly_token'] = $token;
        $session['assembly_config'] = $config;
        $session['provider'] = 'assemblyai';
        
        // Send configuration to client
        $session['connection']->send(json_encode([
            'type' => 'config',
            'provider' => 'assemblyai',
            'websocket_url' => $service->getWebSocketUrl($token, $config),
            'config' => $config
        ]));
        
        Log::info("Started AssemblyAI stream for session", ['config' => $config]);
    }

    protected function processAudioChunk(&$session, $audioData)
    {
        try {
            if (!$session['is_streaming']) {
                $this->startStream($session);
            }

            // Validate audio data
            if (!is_array($audioData) || empty($audioData)) {
                Log::warning('Invalid audio data received', [
                    'connection_id' => $session['connection']->resourceId,
                    'data_type' => gettype($audioData)
                ]);
                return;
            }

            if ($session['provider'] === 'google' && $session['stream']) {
                $binaryData = call_user_func_array('pack', array_merge(['s*'], $audioData));
                $session['stream']->write($binaryData);
                
                // Read results from Google stream if available
                // Note: In a real async WebSocket server, reading should be non-blocking or in a loop
                // For this implementation, we rely on the Google client's handling
                // Ideally we would have a separate process reading from the stream
                
            } elseif ($session['provider'] === 'assemblyai') {
                $this->processAssemblyAIAudio($session, $audioData);
            }
            
            // Update last activity timestamp
            $session['last_activity'] = time();
            
        } catch (\Exception $e) {
            Log::error("Error processing audio chunk: " . $e->getMessage(), [
                'connection_id' => $session['connection']->resourceId,
                'provider' => $session['provider'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            // Send error to client
            $session['connection']->send(json_encode([
                'type' => 'error',
                'message' => 'Audio processing error',
                'code' => 'AUDIO_PROCESSING_ERROR'
            ]));
            
            $this->closeStream($session);
        }
    }

    protected function closeStream(&$session)
    {
        if ($session['stream']) {
            try {
                $session['stream']->close();
            } catch (\Exception $e) {
                // Ignore close errors
            }
            $session['stream'] = null;
        }
        if ($session['speech_client']) {
            $session['speech_client']->close();
            $session['speech_client'] = null;
        }
        if (isset($session['assembly_service'])) {
            $session['assembly_service'] = null;
        }
        $session['is_streaming'] = false;
    }



    private function getGoogleLanguageCode($shortCode)

    {
        $map = [
            'ar' => 'ar-SA',
            'en' => 'en-US',
            'fr' => 'fr-FR',
            'es' => 'es-ES',
            'de' => 'de-DE',
        ];
        return $map[$shortCode] ?? 'en-US';
    }

    private function initializeGoogleStreaming($languageCode = 'en-US')
    {
        $config = (new RecognitionConfig())
            ->setEncoding(AudioEncoding::LINEAR16)
            ->setSampleRateHertz(16000)
            ->setLanguageCode($languageCode)
            ->setEnableSpeakerDiarization(true)
            ->setDiarizationSpeakerCount(2)
            ->setDiarizationConfig(
                (new SpeakerDiarizationConfig())
                    ->setMinSpeakerCount(2)
                    ->setMaxSpeakerCount(2)
            )
            ->setUseEnhanced(true)
            ->setEnableAutomaticPunctuation(true);
            
        // Use medical model only for English as it might not be available for other languages
        if (strpos($languageCode, 'en-') === 0) {
            $config->setModel('medical_dictation');
            $config->setSpeechContexts([
                (new SpeechContext())
                    ->setPhrases([
                        'hypertension', 'blood pressure', 'PRN', 'STAT',
                        'follow up', 'differential diagnosis', 'SOAP note',
                        'patient', 'doctor', 'prescription', 'symptoms'
                    ])
            ]);
        } else {
            $config->setModel('default');
        }

        return (new StreamingRecognitionConfig())
            ->setConfig($config)
            ->setInterimResults(true)
            ->setSingleUtterance(false);
    }

    protected function processAssemblyAIAudio(&$session, $audioData)
    {
        try {
            // Process audio chunk for medical transcription
            $chunkSize = count($audioData);
            $timestamp = microtime(true);
            
            // Log audio metrics for monitoring
            if (rand(1, 100) === 1) { // Log 1% of chunks to avoid spam
                Log::info('AssemblyAI audio processing', [
                    'connection_id' => $session['connection']->resourceId,
                    'chunk_size' => $chunkSize,
                    'visit_id' => $session['visit_id']
                ]);
            }
            
            // Send processed audio info back to client
            $session['connection']->send(json_encode([
                'type' => 'audio_processed',
                'chunk_size' => $chunkSize,
                'timestamp' => $timestamp,
                'status' => 'processing'
            ]));
            
        } catch (\Exception $e) {
            Log::error('AssemblyAI audio processing error: ' . $e->getMessage(), [
                'connection_id' => $session['connection']->resourceId
            ]);
        }
    }
}
