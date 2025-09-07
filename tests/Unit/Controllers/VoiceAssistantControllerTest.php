<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\VoiceAssistantController;
use App\Models\User;
use App\Models\VoiceTranscription;
use App\Services\OpenAIClient;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class VoiceAssistantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $openAIClientMock;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openAIClientMock = Mockery::mock(OpenAIClient::class);
        $this->app->instance(OpenAIClient::class, $this->openAIClientMock);

        $this->controller = new VoiceAssistantController();

        $this->user = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com'
        ]);

        $this->actingAs($this->user);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_voice_assistant_page()
    {
        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('voice-assistant', $response->getContent());
    }

    public function test_transcribe_audio_success()
    {
        $audioFile = UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');
        $transcriptionText = 'Patient presents with chest pain and shortness of breath';

        $this->openAIClientMock
            ->shouldReceive('transcribeAudio')
            ->once()
            ->with(Mockery::type(UploadedFile::class))
            ->andReturn([
                'success' => true,
                'transcription' => $transcriptionText,
                'duration' => 30.5,
                'language' => 'en'
            ]);

        $request = Request::create('/voice-assistant/transcribe', 'POST');
        $request->files->set('audio', $audioFile);

        $response = $this->controller->transcribe($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($transcriptionText, $responseData['transcription']);

        // Verify transcription was saved to database
        $this->assertDatabaseHas('voice_transcriptions', [
            'user_id' => $this->user->id,
            'transcription_text' => $transcriptionText,
            'duration' => 30.5
        ]);
    }

    public function test_transcribe_audio_failure()
    {
        $audioFile = UploadedFile::fake()->create('audio.wav', 1024, 'audio/wav');

        $this->openAIClientMock
            ->shouldReceive('transcribeAudio')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Audio file format not supported'
            ]);

        $request = Request::create('/voice-assistant/transcribe', 'POST');
        $request->files->set('audio', $audioFile);

        $response = $this->controller->transcribe($request);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Audio file format not supported', $responseData['error']);
    }

    public function test_transcribe_without_audio_file()
    {
        $request = Request::create('/voice-assistant/transcribe', 'POST');

        $response = $this->controller->transcribe($request);

        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('audio file', $responseData['error']);
    }

    public function test_analyze_transcription_medical_content()
    {
        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'transcription_text' => 'Patient has fever, headache, and body aches for 3 days',
            'status' => 'completed'
        ]);

        $aiAnalysis = [
            'symptoms_identified' => ['fever', 'headache', 'body aches'],
            'possible_conditions' => ['viral syndrome', 'flu'],
            'urgency_level' => 'medium',
            'recommended_actions' => ['rest', 'fluids', 'monitor temperature'],
            'confidence_score' => 0.85
        ];

        $this->openAIClientMock
            ->shouldReceive('ask')
            ->once()
            ->andReturn(json_encode($aiAnalysis));

        $request = Request::create("/voice-assistant/analyze/{$transcription->id}", 'POST');

        $response = $this->controller->analyzeTranscription($request, $transcription);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('analysis', $responseData);

        $transcription->refresh();
        $this->assertNotNull($transcription->ai_analysis);
        $this->assertEquals('analyzed', $transcription->status);
    }

    public function test_get_transcription_history()
    {
        VoiceTranscription::factory()->count(5)->create(['user_id' => $this->user->id]);
        VoiceTranscription::factory()->count(3)->create(); // Other user's transcriptions

        $response = $this->controller->getTranscriptionHistory();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(5, $responseData['transcriptions']);
    }

    public function test_delete_transcription()
    {
        $transcription = VoiceTranscription::factory()->create(['user_id' => $this->user->id]);

        $response = $this->controller->deleteTranscription($transcription);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseMissing('voice_transcriptions', ['id' => $transcription->id]);
    }

    public function test_export_transcription()
    {
        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'transcription_text' => 'Patient consultation notes',
            'ai_analysis' => json_encode(['symptoms' => ['fever', 'cough']])
        ]);

        $request = Request::create("/voice-assistant/export/{$transcription->id}", 'GET', [
            'format' => 'pdf'
        ]);

        $response = $this->controller->exportTranscription($request, $transcription);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_real_time_transcription_start()
    {
        $request = Request::create('/voice-assistant/real-time/start', 'POST', [
            'session_id' => 'session_123',
            'language' => 'en-US'
        ]);

        $response = $this->controller->startRealTimeTranscription($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('session_id', $responseData);
    }

    public function test_real_time_transcription_process()
    {
        $audioChunk = UploadedFile::fake()->create('chunk.wav', 512, 'audio/wav');

        $this->openAIClientMock
            ->shouldReceive('transcribeAudioChunk')
            ->once()
            ->andReturn([
                'success' => true,
                'partial_transcription' => 'Patient says they have',
                'is_final' => false
            ]);

        $request = Request::create('/voice-assistant/real-time/process', 'POST', [
            'session_id' => 'session_123'
        ]);
        $request->files->set('audio_chunk', $audioChunk);

        $response = $this->controller->processRealTimeAudio($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Patient says they have', $responseData['partial_transcription']);
    }

    public function test_real_time_transcription_stop()
    {
        $request = Request::create('/voice-assistant/real-time/stop', 'POST', [
            'session_id' => 'session_123',
            'final_transcription' => 'Complete patient consultation transcription'
        ]);

        $response = $this->controller->stopRealTimeTranscription($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        // Verify final transcription was saved
        $this->assertDatabaseHas('voice_transcriptions', [
            'user_id' => $this->user->id,
            'transcription_text' => 'Complete patient consultation transcription',
            'session_id' => 'session_123'
        ]);
    }

    public function test_voice_command_processing()
    {
        $command = 'Create new patient record for John Doe age 35';

        $this->openAIClientMock
            ->shouldReceive('processVoiceCommand')
            ->once()
            ->with($command)
            ->andReturn([
                'success' => true,
                'action' => 'create_patient',
                'parameters' => [
                    'name' => 'John Doe',
                    'age' => 35
                ],
                'confidence' => 0.95
            ]);

        $request = Request::create('/voice-assistant/command', 'POST', [
            'command' => $command
        ]);

        $response = $this->controller->processVoiceCommand($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('create_patient', $responseData['action']);
        $this->assertEquals('John Doe', $responseData['parameters']['name']);
    }

    public function test_transcription_search()
    {
        VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'transcription_text' => 'Patient has diabetes and hypertension'
        ]);

        VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'transcription_text' => 'Patient complains of chest pain'
        ]);

        VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'transcription_text' => 'Patient has diabetes type 2'
        ]);

        $request = Request::create('/voice-assistant/search', 'GET', [
            'query' => 'diabetes'
        ]);

        $response = $this->controller->searchTranscriptions($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData['results']);
    }

    public function test_transcription_statistics()
    {
        VoiceTranscription::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5)
        ]);

        VoiceTranscription::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        $response = $this->controller->getTranscriptionStatistics();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('total_transcriptions', $responseData);
        $this->assertArrayHasKey('this_week', $responseData);
        $this->assertArrayHasKey('this_month', $responseData);
        $this->assertEquals(15, $responseData['total_transcriptions']);
    }

    public function test_audio_file_validation()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $request = Request::create('/voice-assistant/transcribe', 'POST');
        $request->files->set('audio', $invalidFile);

        $response = $this->controller->transcribe($request);

        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('audio', $responseData['error']);
    }

    public function test_large_audio_file_handling()
    {
        $largeAudioFile = UploadedFile::fake()->create('large_audio.wav', 50000, 'audio/wav'); // 50MB

        $this->openAIClientMock
            ->shouldReceive('transcribeAudio')
            ->once()
            ->andReturn([
                'success' => true,
                'transcription' => 'Long transcription from large file',
                'duration' => 1800, // 30 minutes
                'chunks_processed' => 10
            ]);

        $request = Request::create('/voice-assistant/transcribe', 'POST');
        $request->files->set('audio', $largeAudioFile);

        $response = $this->controller->transcribe($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(1800, $responseData['duration']);
    }
}
