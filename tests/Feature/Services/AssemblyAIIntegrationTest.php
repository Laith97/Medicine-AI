<?php

namespace Tests\Feature\Services;

use App\Services\AssemblyAIService;
use App\WebSockets\MedicalAudioSocket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Mockery;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AssemblyAIIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set the API key for testing
        Config::set('services.assemblyai.api_key', 'test-api-key');
    }

    public function test_assemblyai_integration_with_medical_audio_socket()
    {
        // Mock the HTTP request for temporary token
        Http::fake([
            'api.assemblyai.com/v2/realtime/token' => Http::response([
                'token' => 'test-session-token'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        // Create the AssemblyAI service
        $assemblyAIService = new AssemblyAIService();
        
        // Test that we can get a WebSocket URL
        $websocketUrl = $assemblyAIService->getWebSocketUrl();
        
        $this->assertStringContainsString('wss://api.assemblyai.com/v2/realtime/ws', $websocketUrl);
        $this->assertStringContainsString('token=test-session-token', $websocketUrl);
        
        // Verify the token request was made
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.assemblyai.com/v2/realtime/token';
        });
    }

    public function test_medical_transcription_with_assemblyai()
    {
        $testAudioUrl = 'https://example.com/medical-consultation.wav';
        
        // Mock the transcript submission response
        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'id' => 'medical-transcript-123',
                'status' => 'queued'
            ], 200, ['Content-Type' => 'application/json'])
        ]);
        
        // Mock the transcript retrieval response
        Http::fake([
            'api.assemblyai.com/v2/transcript/medical-transcript-123' => Http::response([
                'id' => 'medical-transcript-123',
                'status' => 'completed',
                'text' => 'Patient presents with chest pain and shortness of breath. Blood pressure is elevated.',
                'confidence' => 0.92,
                'utterances' => [
                    [
                        'speaker' => 'A',
                        'text' => 'How are you feeling today?',
                        'start' => 0,
                        'end' => 2000
                    ],
                    [
                        'speaker' => 'B', 
                        'text' => 'I have chest pain and difficulty breathing',
                        'start' => 2500,
                        'end' => 5000
                    ]
                ]
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();
        
        // Submit the transcript
        $submissionResult = $assemblyAIService->processTranscript($testAudioUrl, [
            'speaker_labels' => true,
            'punctuate' => true,
            'format_text' => true
        ]);

        $this->assertEquals('medical-transcript-123', $submissionResult['id']);
        $this->assertEquals('queued', $submissionResult['status']);

        // Retrieve the completed transcript
        $transcriptResult = $assemblyAIService->getTranscript('medical-transcript-123');

        $this->assertEquals('medical-transcript-123', $transcriptResult['id']);
        $this->assertEquals('completed', $transcriptResult['status']);
        $this->assertStringContainsString('chest pain', $transcriptResult['text']);
        $this->assertGreaterThan(0.9, $transcriptResult['confidence']);
        $this->assertArrayHasKey('utterances', $transcriptResult);
        $this->assertCount(2, $transcriptResult['utterances']);
    }

    public function test_arabic_medical_transcription_with_assemblyai()
    {
        $testAudioUrl = 'https://example.com/arabic-medical-consultation.wav';
        
        // Mock the transcript submission for Arabic
        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'id' => 'arabic-transcript-456',
                'status' => 'queued'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();
        
        // Test Arabic language support with medical terms
        $result = $assemblyAIService->processTranscript($testAudioUrl, [
            'language_code' => 'ar',
            'speaker_labels' => true
        ]);

        $this->assertEquals('arabic-transcript-456', $result['id']);
        
        // Verify the request was sent with Arabic language code
        Http::assertSent(function ($request) {
            $data = $request->data();
            return $data['language_code'] === 'ar' &&
                   $data['speaker_labels'] === true;
        });
    }

    public function test_word_boost_configuration_for_medical_terminology()
    {
        $testAudioUrl = 'https://example.com/medical-audio.wav';
        
        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'id' => 'boosted-transcript',
                'status' => 'queued'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();
        
        // Process with medical terminology boosting
        $result = $assemblyAIService->processTranscript($testAudioUrl);
        
        // Verify the request included medical terminology in word_boost
        Http::assertSent(function ($request) {
            $data = $request->data();
            $medicalTerms = [
                'hypertension', 'diabetes', 'prescription', 'symptoms', 'diagnosis',
                'blood pressure', 'heart rate', 'temperature', 'medication',
                'patient', 'doctor', 'examination', 'treatment'
            ];
            
            return $data['word_boost'] !== null && 
                   count(array_intersect($medicalTerms, $data['word_boost'])) >= 5;
        });
    }

    public function test_realtime_session_configuration()
    {
        $assemblyAIService = new AssemblyAIService();
        
        // Test default real-time configuration
        $config = $assemblyAIService->startRealtimeSession();
        
        $this->assertEquals(16000, $config['sample_rate']);
        $this->assertTrue($config['speaker_labels']);
        $this->assertTrue($config['punctuate']);
        $this->assertTrue($config['format_text']);
        $this->assertContains('hypertension', $config['word_boost']);
        
        // Test custom configuration
        $customConfig = $assemblyAIService->startRealtimeSession([
            'language_code' => 'en-US',
            'word_boost' => ['cardiology', 'cardiac', 'ECG']
        ]);
        
        $this->assertEquals('en-US', $customConfig['language_code']);
        $this->assertContains('cardiology', $customConfig['word_boost']);
        $this->assertTrue($customConfig['speaker_labels']); // Should keep default
    }

    public function test_websocket_url_generation()
    {
        // Mock the token generation
        Http::fake([
            'api.assemblyai.com/v2/realtime/token' => Http::response([
                'token' => 'ws-test-token'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();
        
        // Get WebSocket URL with automatic token generation
        $autoUrl = $assemblyAIService->getWebSocketUrl();
        $this->assertStringContainsString('token=ws-test-token', $autoUrl);
        $this->assertStringContainsString('wss://api.assemblyai.com/v2/realtime/ws', $autoUrl);
        
        // Get WebSocket URL with provided token
        $providedUrl = $assemblyAIService->getWebSocketUrl('provided-token');
        $this->assertStringContainsString('token=provided-token', $providedUrl);
        $this->assertStringNotContainsString('ws-test-token', $providedUrl);
    }

    public function test_error_handling_in_assemblyai_service()
    {
        // Test token request error
        Http::fake([
            'api.assemblyai.com/v2/realtime/token' => Http::response([
                'error' => 'Unauthorized'
            ], 401, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get AssemblyAI token: HTTP 401');
        
        $assemblyAIService->getTemporaryToken();
    }

    public function test_transcript_processing_error_handling()
    {
        $testAudioUrl = 'https://example.com/invalid-audio.wav';

        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'error' => 'Invalid audio file'
            ], 422, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit transcript: HTTP 422');
        
        $assemblyAIService->processTranscript($testAudioUrl);
    }

    public function test_transcript_retrieval_error_handling()
    {
        Http::fake([
            'api.assemblyai.com/v2/transcript/invalid-id' => Http::response([
                'error' => 'Transcript not found'
            ], 404, ['Content-Type' => 'application/json'])
        ]);

        $assemblyAIService = new AssemblyAIService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get transcript: HTTP 404');
        
        $assemblyAIService->getTranscript('invalid-id');
    }
}