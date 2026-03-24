<?php

namespace Tests\Unit\Services;

use App\Services\AssemblyAIService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class AssemblyAIServiceTest extends TestCase
{
    protected $assemblyAIService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set the API key for testing
        Config::set('services.assemblyai.api_key', 'test-api-key');
        
        $this->assemblyAIService = new AssemblyAIService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_fails_without_api_key()
    {
        // Unset the API key to test the exception
        Config::set('services.assemblyai.api_key', null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AssemblyAI API key not configured');

        new AssemblyAIService();
    }

    public function test_constructor_succeeds_with_api_key()
    {
        Config::set('services.assemblyai.api_key', 'test-api-key');
        
        $service = new AssemblyAIService();
        
        $this->assertInstanceOf(AssemblyAIService::class, $service);
    }

    public function test_get_websocket_url()
    {
        // Mock the Http facade for token request
        Http::fake([
            'api.assemblyai.com/v2/realtime/token' => Http::response([
                'token' => 'test-session-token'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $url = $this->assemblyAIService->getWebSocketUrl();
        
        $this->assertStringContainsString('wss://api.assemblyai.com/v2/realtime/ws', $url);
        $this->assertStringContainsString('token=test-session-token', $url);
    }

    public function test_get_websocket_url_with_provided_token()
    {
        $token = 'provided-token';
        $url = $this->assemblyAIService->getWebSocketUrl($token);
        
        $this->assertStringContainsString('wss://api.assemblyai.com/v2/realtime/ws', $url);
        $this->assertStringContainsString('token=' . $token, $url);
    }

    public function test_start_realtime_session_with_defaults()
    {
        $config = $this->assemblyAIService->startRealtimeSession();
        
        $expectedConfig = [
            'sample_rate' => 16000,
            'word_boost' => ['hypertension', 'diabetes', 'prescription', 'symptoms', 'diagnosis'],
            'speaker_labels' => true,
            'punctuate' => true,
            'format_text' => true
        ];

        $this->assertEquals($expectedConfig, $config);
    }

    public function test_start_realtime_session_with_custom_config()
    {
        $customConfig = [
            'language_code' => 'en-US',
            'word_boost' => ['heart', 'pulse', 'pressure']
        ];

        $result = $this->assemblyAIService->startRealtimeSession($customConfig);
        
        // Custom config should override defaults where applicable
        $this->assertEquals('en-US', $result['language_code']);
        $this->assertEquals(['heart', 'pulse', 'pressure'], $result['word_boost']);
        $this->assertTrue($result['speaker_labels']);
        $this->assertTrue($result['punctuate']);
    }

    public function test_get_temporary_token_success()
    {
        // Mock the HTTP request to AssemblyAI
        Http::fake([
            'api.assemblyai.com/v2/realtime/token' => Http::response([
                'token' => 'test-temp-token-123'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $token = $this->assemblyAIService->getTemporaryToken();
        
        $this->assertEquals('test-temp-token-123', $token);

        // Verify the HTTP request was made correctly
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.assemblyai.com/v2/realtime/token' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key') &&
                   $request->method() === 'POST';
        });
    }

    public function test_get_temporary_token_failure()
    {
        // Mock a failed HTTP request
        Http::fake([
            'api.assemblyai.com/v2/realtime/token' => Http::response([
                'error' => 'Unauthorized'
            ], 401, ['Content-Type' => 'application/json'])
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get AssemblyAI token: HTTP 401');

        $this->assemblyAIService->getTemporaryToken();
    }

    public function test_get_temporary_token_exception()
    {
        // Mock an HTTP timeout or connection error
        Http::fake(function ($request) {
            throw new \Exception('Connection failed');
        });

        $this->expectException(\Exception::class);
        
        $this->assemblyAIService->getTemporaryToken();
    }

    public function test_process_transcript_success()
    {
        $testAudioUrl = 'https://example.com/test-audio.wav';
        
        // Mock the successful response
        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'id' => 'test-transcript-id',
                'status' => 'queued'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $result = $this->assemblyAIService->processTranscript($testAudioUrl);

        $this->assertEquals('test-transcript-id', $result['id']);
        $this->assertEquals('queued', $result['status']);

        // Verify the HTTP request was made with correct headers and body
        Http::assertSent(function ($request) use ($testAudioUrl) {
            $data = $request->data();
            return $request->url() === 'https://api.assemblyai.com/v2/transcript' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key') &&
                   $data['audio_url'] === $testAudioUrl &&
                   $data['speaker_labels'] === true;
        });
    }

    public function test_process_transcript_with_custom_config()
    {
        $testAudioUrl = 'https://example.com/test-audio.wav';
        $customConfig = [
            'language_code' => 'ar',
            'redact_pii' => true
        ];
        
        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'id' => 'test-transcript-id-ar',
                'status' => 'processing'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $result = $this->assemblyAIService->processTranscript($testAudioUrl, $customConfig);

        $this->assertEquals('test-transcript-id-ar', $result['id']);
        $this->assertEquals('processing', $result['status']);

        // Verify the custom config was merged
        Http::assertSent(function ($request) use ($customConfig) {
            $data = $request->data();
            return $data['language_code'] === $customConfig['language_code'] &&
                   $data['redact_pii'] === $customConfig['redact_pii'];
        });
    }

    public function test_process_transcript_failure()
    {
        $testAudioUrl = 'https://example.com/test-audio.wav';

        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'error' => 'Invalid audio format'
            ], 422, ['Content-Type' => 'application/json'])
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit transcript: HTTP 422');

        $this->assemblyAIService->processTranscript($testAudioUrl);
    }

    public function test_get_transcript_success()
    {
        $testTranscriptId = 'test-transcript-id';
        
        $expectedResponse = [
            'id' => $testTranscriptId,
            'status' => 'completed',
            'text' => 'This is the transcribed text',
            'confidence' => 0.95
        ];

        Http::fake([
            "api.assemblyai.com/v2/transcript/{$testTranscriptId}" => Http::response(
                $expectedResponse, 
                200, 
                ['Content-Type' => 'application/json']
            )
        ]);

        $result = $this->assemblyAIService->getTranscript($testTranscriptId);

        $this->assertEquals($expectedResponse, $result);

        Http::assertSent(function ($request) use ($testTranscriptId) {
            return $request->url() === "https://api.assemblyai.com/v2/transcript/{$testTranscriptId}" &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_get_transcript_failure()
    {
        $testTranscriptId = 'non-existent-id';

        Http::fake([
            "api.assemblyai.com/v2/transcript/{$testTranscriptId}" => Http::response([
                'error' => 'Transcript not found'
            ], 404, ['Content-Type' => 'application/json'])
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get transcript: HTTP 404');

        $this->assemblyAIService->getTranscript($testTranscriptId);
    }

    public function test_medical_terminology_in_default_config()
    {
        $config = $this->assemblyAIService->startRealtimeSession();
        
        // Check that medical terms are in the word_boost
        $this->assertContains('hypertension', $config['word_boost']);
        $this->assertContains('diabetes', $config['word_boost']);
        $this->assertContains('prescription', $config['word_boost']);
        $this->assertContains('symptoms', $config['word_boost']);
        $this->assertContains('diagnosis', $config['word_boost']);
    }

    public function test_process_transcript_includes_medical_terminology()
    {
        $testAudioUrl = 'https://example.com/test-audio.wav';

        Http::fake([
            'api.assemblyai.com/v2/transcript' => Http::response([
                'id' => 'test-transcript-id',
                'status' => 'queued'
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $this->assemblyAIService->processTranscript($testAudioUrl);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $medicalTerms = $data['word_boost'];
            
            return in_array('hypertension', $medicalTerms) &&
                   in_array('diabetes', $medicalTerms) &&
                   in_array('prescription', $medicalTerms) &&
                   in_array('symptoms', $medicalTerms) &&
                   in_array('diagnosis', $medicalTerms) &&
                   in_array('blood pressure', $medicalTerms) &&
                   in_array('heart rate', $medicalTerms);
        });
    }
}