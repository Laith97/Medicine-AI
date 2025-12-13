<?php

namespace App\WebSockets;

use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\StreamingRecognitionConfig;
use Google\Cloud\Speech\V1\AudioEncoding;
use Google\Cloud\Speech\V1\SpeakerDiarizationConfig;
use Google\Cloud\Speech\V1\SpeechContext;
use Illuminate\Support\Facades\Log;
use App\Services\MedicalTranscriptionService;

class MedicalAudioSocket extends WebSocketHandler
{
    protected $activeConnections = [];
    protected $transcriptionService;

    public function __construct()
    {
        // We'll resolve the service lazily or via container if needed
        // but WebSocketHandler instantiation might be tricky with DI depending on setup
        $this->transcriptionService = app(MedicalTranscriptionService::class);
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
            'speech_client' => null,
            'stream' => null,
            'is_streaming' => false
        ];

        Log::info("Medical Audio Socket connected: {$connection->resourceId}", $params);
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $msg)
    {
        $payload = json_decode($msg->getPayload(), true);
        
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
        Log::error("Medical Audio Socket error: " . $e->getMessage());
        $connection->close();
    }

    protected function validateMedicalContext($params)
    {
        // In a real app, validate the JWT token and check if the user has access to the visit_id
        if (empty($params['token']) || empty($params['visit_id'])) {
            return false;
        }
        
        // TODO: Implement actual token validation logic here
        // For now, returning true to allow connection for testing
        return true;
    }



    protected function startStream(&$session)
    {
        try {
            $provider = config('medical.transcription_provider', 'google'); // 'google' or 'assemblyai'
            
            if ($provider === 'assemblyai') {
                $this->startAssemblyAIStream($session);
            } else {
                $this->startGoogleStream($session);
            }
            
            $session['is_streaming'] = true;
            
        } catch (\Exception $e) {
            Log::error("Failed to start transcription stream: " . $e->getMessage());
            $session['connection']->send(json_encode([
                'type' => 'error',
                'message' => 'Transcription service unavailable'
            ]));
        }
    }

    protected function startGoogleStream(&$session)
    {
        $speechClient = new SpeechClient([
            'credentials' => config('services.google.cloud.credentials'),
        ]);

        $streamingConfig = $this->initializeGoogleStreaming();
        
        $stream = $speechClient->streamingRecognize();
        $stream->write(['streaming_config' => $streamingConfig]);

        $session['speech_client'] = $speechClient;
        $session['stream'] = $stream;
        $session['provider'] = 'google';
    }

    protected function startAssemblyAIStream(&$session)
    {
        // In a real implementation, we would use the AssemblyAIService
        // For now, we'll simulate the connection or use the service if available
        // $service = new \App\Services\AssemblyAIService();
        // $service->connect();
        // $session['assembly_service'] = $service;
        $session['provider'] = 'assemblyai';
        
        Log::info("Started AssemblyAI stream for session");
    }

    protected function processAudioChunk(&$session, $audioData)
    {
        try {
            if (!$session['is_streaming']) {
                $this->startStream($session);
            }

            if ($session['provider'] === 'google' && $session['stream']) {
                $binaryData = call_user_func_array('pack', array_merge(['s*'], $audioData));
                $session['stream']->write($binaryData);
            } elseif ($session['provider'] === 'assemblyai') {
                // $session['assembly_service']->sendAudio($audioData);
                // Mocking AssemblyAI behavior for now
            }
        } catch (\Exception $e) {
            Log::error("Error processing audio chunk: " . $e->getMessage());
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
        $session['is_streaming'] = false;
    }

    private function initializeGoogleStreaming()
    {
        $config = (new RecognitionConfig())
            ->setEncoding(AudioEncoding::LINEAR16)
            ->setSampleRateHertz(16000)
            ->setLanguageCode('en-US')
            ->setEnableSpeakerDiarization(true)
            ->setDiarizationSpeakerCount(2)
            ->setDiarizationConfig(
                (new SpeakerDiarizationConfig())
                    ->setMinSpeakerCount(2)
                    ->setMaxSpeakerCount(2)
            )
            ->setModel('medical_dictation')
            ->setUseEnhanced(true)
            ->setEnableAutomaticPunctuation(true)
            ->setSpeechContexts([
                (new SpeechContext())
                    ->setPhrases([
                        'hypertension', 'blood pressure', 'PRN', 'STAT',
                        'follow up', 'differential diagnosis', 'SOAP note',
                        'patient', 'doctor', 'prescription', 'symptoms'
                    ])
            ]);

        return (new StreamingRecognitionConfig())
            ->setConfig($config)
            ->setInterimResults(true)
            ->setSingleUtterance(false);
    }
}
