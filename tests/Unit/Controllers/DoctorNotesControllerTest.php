<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Doctor\DoctorNotesController;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DoctorNote;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DoctorNotesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $patientUser;
    protected $doctor;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a doctor user
        $this->doctorUser = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@test.com'
        ]);

        // Create a patient user
        $this->patientUser = User::factory()->create([
            'role' => 'patient',
            'email' => 'patient@test.com'
        ]);

        // Create doctor profile
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id
        ]);

        $this->controller = new DoctorNotesController();
    }

    /** @test */
    public function test_transcribe_audio_with_auto_language_detection()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        // Mock the OpenAI Whisper API response
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'المريض يعاني من التهاب اللوزتين الحاد مع ارتفاع في درجة الحرارة',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "**الشكوى الرئيسية:**\n• التهاب اللوزتين الحاد\n\n**الأعراض:**\n• ارتفاع في درجة الحرارة\n• ألم في الحلق\n\n**التشخيص:**\n• التهاب اللوزتين الحاد (Acute Tonsillitis)"
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Create a mock base64 audio file
        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake audio data');

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            'audio_file' => $base64Audio
        ]);

        // Act
        $response = $this->controller->transcribeAudio($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertStringContainsString('التهاب اللوزتين الحاد', $responseData['transcript']);
        $this->assertStringContainsString('الشكوى الرئيسية', $responseData['transcript']);

        // Verify that both API calls were made (2 calls total)
        Http::assertSentCount(2);
    }

    /** @test */
    public function test_transcribe_audio_with_english_content()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        // Mock the OpenAI API responses for English content
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'Patient presents with acute tonsillitis and fever',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "**Chief Complaint:**\n• Acute tonsillitis\n\n**History of Present Illness:**\n• Patient presents with fever\n• Sore throat symptoms\n\n**Assessment/Diagnosis:**\n• Acute Tonsillitis"
                        ]
                    ]
                ]
            ], 200)
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake english audio data');

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            'audio_file' => $base64Audio
        ]);

        // Act
        $response = $this->controller->transcribeAudio($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertStringContainsString('Acute tonsillitis', $responseData['transcript']);
        $this->assertStringContainsString('Chief Complaint', $responseData['transcript']);
    }

    /** @test */
    public function test_transcribe_audio_handles_whisper_api_failure()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        // Mock failed Whisper API response
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response([
                'error' => [
                    'message' => 'Invalid audio format'
                ]
            ], 400)
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('invalid audio data');

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            'audio_file' => $base64Audio
        ]);

        // Act
        $response = $this->controller->transcribeAudio($request);

        // Assert
        $this->assertEquals(500, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Transcription failed', $responseData['message']);
    }

    /** @test */
    public function test_transcribe_audio_handles_gpt4_formatting_failure()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        // Mock successful Whisper but failed GPT-4 formatting
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'Patient has acute tonsillitis with fever',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded'
                ]
            ], 429)
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake audio data');

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            'audio_file' => $base64Audio
        ]);

        // Act
        $response = $this->controller->transcribeAudio($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        // Should fallback to basic formatting with bullet points
        $this->assertStringContainsString('•', $responseData['transcript']);
        $this->assertStringContainsString('Patient has acute tonsillitis', $responseData['transcript']);
    }

    /** @test */
    public function test_transcribe_audio_validates_request()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            // Missing audio_file parameter
        ]);

        // Act
        $response = $this->controller->transcribeAudio($request);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('errors', $responseData);
    }

    /** @test */
    public function test_transcribe_audio_handles_empty_audio_data()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            'audio_file' => 'data:audio/webm;base64,' // Empty base64 data
        ]);

        // Act
        $response = $this->controller->transcribeAudio($request);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Empty audio data', $responseData['message']);
    }

    /** @test */
    public function test_format_medical_transcript_preserves_arabic_language()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatMedicalTranscript');
        $method->setAccessible(true);

        // Mock GPT-4 response that preserves Arabic
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "**الشكوى الرئيسية:**\n• التهاب اللوزتين الحاد\n\n**التشخيص:**\n• التهاب اللوزتين الحاد"
                        ]
                    ]
                ]
            ], 200)
        ]);

        $rawTranscript = 'المريض يعاني من التهاب اللوزتين الحاد';

        // Act
        $result = $method->invoke($this->controller, $rawTranscript);

        // Assert
        $this->assertStringContainsString('الشكوى الرئيسية', $result);
        $this->assertStringContainsString('التهاب اللوزتين الحاد', $result);
        $this->assertStringContainsString('•', $result); // Should have bullet points
    }

    /** @test */
    public function test_basic_medical_formatting_fallback()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('basicMedicalFormatting');
        $method->setAccessible(true);

        $transcript = 'Patient has acute tonsillitis. Temperature is elevated. Throat is red and swollen.';

        // Act
        $result = $method->invoke($this->controller, $transcript);

        // Assert
        $this->assertStringContainsString('• Patient has acute tonsillitis', $result);
        $this->assertStringContainsString('• Temperature is elevated', $result);
        $this->assertStringContainsString('• Throat is red and swollen', $result);
    }

    /** @test */
    public function test_store_voice_note_with_transcript()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        Storage::fake('local');

        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
            'status' => 'completed'
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake audio data');
        $transcript = "**Chief Complaint:**\n• Acute tonsillitis\n\n**Assessment:**\n• Patient diagnosed with acute tonsillitis";

        $request = Request::create('/doctor/notes', 'POST', [
            'note_type' => 'voice',
            'patient_id' => $this->patientUser->id,
            'appointment_id' => $appointment->id,
            'title' => 'Voice Note Test',
            'transcript' => $transcript,
            'audio_file' => $base64Audio
        ]);

        // Set the request to expect JSON response
        $request->headers->set('Accept', 'application/json');

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        // Verify note was created in database
        $this->assertDatabaseHas('doctor_notes', [
            'doctor_id' => $this->doctorUser->id,
            'patient_id' => $this->patientUser->id,
            'note_type' => 'voice',
            'transcript' => $transcript
        ]);

        // Verify audio file was saved
        $note = DoctorNote::where('doctor_id', $this->doctorUser->id)->first();
        $this->assertNotNull($note->audio_file_path);
        Storage::assertExists($note->audio_file_path);
    }

    /** @test */
    public function test_transcription_logging()
    {
        // Arrange
        $this->actingAs($this->doctorUser);

        Log::shouldReceive('debug')
            ->with('Transcribing audio file', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('debug')
            ->with('Temporary audio file created', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('debug')
            ->with('Medical transcript formatted successfully', \Mockery::type('array'))
            ->once();

        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response('Test transcript', 200),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '• Test formatted transcript']]]
            ], 200)
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake audio data');

        $request = Request::create('/doctor/notes/transcribe-audio', 'POST', [
            'audio_file' => $base64Audio
        ]);

        // Act
        $this->controller->transcribeAudio($request);

        // Assert - Mockery will verify the expectations
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
