<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssemblyAIService
{
    private $apiKey;
    private $streamingBaseUrl = 'https://streaming.assemblyai.com/v3';
    private $apiBaseUrl = 'https://api.assemblyai.com/v2';
    private $websocketUrl = 'wss://streaming.assemblyai.com/v3/ws';

    public function __construct()
    {
        $this->apiKey = config('services.assemblyai.api_key');

        if (empty($this->apiKey)) {
            \Log::warning('AssemblyAI API key not configured');
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
     * Get WebSocket URL with authentication and parameters for v3
     */
    public function getWebSocketUrl($sessionToken, $params = [])
    {
        $url = $this->websocketUrl . '?token=' . $sessionToken;
        
        // v3 uses query parameters for configuration
        foreach ($params as $key => $value) {
            if ($key !== 'expires_in' && $key !== 'expires_in_seconds') {
                // v3 expects specific formats for different types
                if (is_bool($value)) {
                    $encodedValue = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $encodedValue = json_encode($value);
                } else {
                    $encodedValue = (string)$value;
                }
                
                $url .= '&' . urlencode($key) . '=' . urlencode($encodedValue);
            }
        }
        
        return $url;
    }

    /**
     * Get temporary token for WebSocket connection (v3 GET request)
     * v3 endpoint: GET https://streaming.assemblyai.com/v3/token?expires_in_seconds=600
     */
    public function getTemporaryToken($expiresIn = 600)
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('AssemblyAI API key not configured, cannot generate token');
            return null;
        }

        try {
            Log::info('AssemblyAI v3 - Requesting token', [
                'url' => $this->streamingBaseUrl . '/token',
                'api_key_length' => strlen($this->apiKey),
                'expires_in_seconds' => $expiresIn
            ]);

            $response = Http::timeout(30) // Increased timeout for resolution stability
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
                    ]
                ])
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                ])
                ->get($this->streamingBaseUrl . '/token', [
                    'expires_in_seconds' => $expiresIn
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['token'])) {
                    return $data['token'];
                }
                throw new \Exception('Token not found in response');
            }

            Log::error('AssemblyAI v3 token request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Failed to get AssemblyAI v3 token: HTTP ' . $response->status());

        } catch (\Exception $e) {
            Log::error('AssemblyAI v3 token request exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload local file to AssemblyAI
     */
    public function uploadFile($filePath)
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('AssemblyAI API key not configured, cannot upload file');
            return null;
        }

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }

            $response = Http::timeout(60)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
                    ]
                ])
                ->withHeaders([
                    'Authorization' => $this->apiKey
                ])
                ->withBody(fopen($filePath, 'r'), 'application/octet-stream')
                ->post($this->apiBaseUrl . '/upload');

            if ($response->successful()) {
                $data = $response->json();
                return $data['upload_url'] ?? null;
            }

            Log::error('AssemblyAI file upload failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Failed to upload file to AssemblyAI: HTTP ' . $response->status());

        } catch (\Exception $e) {
            Log::error('AssemblyAI file upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process medical transcription with speaker diarization
     */
    public function processTranscript($audioUrl, $config = [])
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('AssemblyAI API key not configured, cannot process transcript');
            return null;
        }

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
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
                    ]
                ])
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiBaseUrl . '/transcript', array_merge($transcriptConfig, $config));

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
            return null;
        }
    }

    /**
     * Get transcript result
     */
    public function getTranscript($transcriptId)
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('AssemblyAI API key not configured, cannot get transcript');
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
                    ]
                ])
                ->withHeaders([
                    'Authorization' => $this->apiKey
                ])
                ->get($this->apiBaseUrl . '/transcript/' . $transcriptId);

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
            return null;
        }
    }
}