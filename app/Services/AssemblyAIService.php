<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssemblyAIService
{
    private $apiKey;
    private $baseUrl = 'https://api.assemblyai.com/v2';
    private $websocketUrl = 'wss://api.assemblyai.com/v2/realtime/ws';

    public function __construct()
    {
        $this->apiKey = config('services.assemblyai.api_key');
        
        if (empty($this->apiKey)) {
            throw new \Exception('AssemblyAI API key not configured');
        }
    }

    /**
     * Start real-time transcription session
     */
    public function startRealtimeSession($config = [])
    {
        $defaultConfig = [
            'sample_rate' => 16000,
            'word_boost' => ['hypertension', 'diabetes', 'prescription', 'symptoms', 'diagnosis'],
            'speaker_labels' => true,
            'punctuate' => true,
            'format_text' => true
        ];

        return array_merge($defaultConfig, $config);
    }

    /**
     * Get WebSocket URL with authentication
     */
    public function getWebSocketUrl($sessionToken = null)
    {
        $token = $sessionToken ?: $this->getTemporaryToken();
        return $this->websocketUrl . '?token=' . $token;
    }

    /**
     * Get temporary token for WebSocket connection
     */
    public function getTemporaryToken()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/realtime/token', [
                    'expires_in' => 3600
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['token'])) {
                    return $data['token'];
                }
                throw new \Exception('Token not found in response');
            }

            Log::error('AssemblyAI token request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Failed to get AssemblyAI token: HTTP ' . $response->status());
            
        } catch (\Exception $e) {
            Log::error('AssemblyAI token request exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process medical transcription with speaker diarization
     */
    public function processTranscript($audioUrl, $config = [])
    {
        try {
            $transcriptConfig = [
                'audio_url' => $audioUrl,
                'speaker_labels' => true,
                'punctuate' => true,
                'format_text' => true,
                'word_boost' => [
                    'hypertension', 'diabetes', 'prescription', 'symptoms', 'diagnosis',
                    'blood pressure', 'heart rate', 'temperature', 'medication',
                    'patient', 'doctor', 'examination', 'treatment'
                ],
                'boost_param' => 'high'
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/transcript', array_merge($transcriptConfig, $config));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AssemblyAI transcript submission failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'audio_url' => $audioUrl
            ]);
            
            throw new \Exception('Failed to submit transcript: HTTP ' . $response->status());
            
        } catch (\Exception $e) {
            Log::error('AssemblyAI transcript processing exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get transcript result
     */
    public function getTranscript($transcriptId)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey
                ])
                ->get($this->baseUrl . '/transcript/' . $transcriptId);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AssemblyAI transcript retrieval failed', [
                'transcript_id' => $transcriptId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Failed to get transcript: HTTP ' . $response->status());
            
        } catch (\Exception $e) {
            Log::error('AssemblyAI transcript retrieval exception: ' . $e->getMessage());
            throw $e;
        }
    }
}