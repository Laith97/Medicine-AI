<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DoctorNote;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VoiceTranscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $patientUser;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->doctorUser = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@test.com'
        ]);

        $this->patientUser = User::factory()->create([
            'role' => 'patient',
            'email' => 'patient@test.com'
        ]);

        // Create doctor profile
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id
        ]);

        // Set OpenAI API key for testing
        config(['services.openai.key' => 'test-api-key']);
    }

    /** @test */
    public function test_doctor_can_access_notes_create_page()
    {
        // Act
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.notes.create'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('doctor.notes.create');
        $response->assertSee('Voice Recording');
        $response->assertSee('Transcribe & Format');
    }

    /** @test */
    public function test_complete_voice_note_workflow_arabic()
    {
        // Arrange
        Storage::fake('local');

        // Mock OpenAI API responses for Arabic transcription
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'المريض يعاني من التهاب اللوزتين الحاد مع ارتفاع في درجة الحرارة والم في الحلق',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "**الشكوى الرئيسية:**\n• التهاب اللوزتين الحاد\n\n**الأعراض الحالية:**\n• ارتفاع في درجة الحرارة\n• ألم في الحلق\n\n**التشخيص:**\n• التهاب اللوزتين الحاد (Acute Tonsillitis)\n\n**الخطة العلاجية:**\n• مضادات حيوية\n• مسكنات للألم\n• راحة تامة"
                        ]
                    ]
                ]
            ], 200)
        ]);

        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
            'status' => 'completed'
        ]);

        // Step 1: Transcribe audio
        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake arabic audio data');

        $transcriptionResponse = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.transcribe-audio'), [
                'audio_file' => $base64Audio
            ]);

        // Assert transcription was successful
        $transcriptionResponse->assertStatus(200);
        $transcriptionData = $transcriptionResponse->json();

        $this->assertTrue($transcriptionData['success']);
        $this->assertStringContainsString('التهاب اللوزتين الحاد', $transcriptionData['transcript']);
        $this->assertStringContainsString('الشكوى الرئيسية', $transcriptionData['transcript']);
        $this->assertStringContainsString('•', $transcriptionData['transcript']); // Has bullet points

        // Step 2: Save the voice note
        $noteResponse = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.store'), [
                'note_type' => 'voice',
                'patient_id' => $this->patientUser->id,
                'appointment_id' => $appointment->id,
                'title' => 'Arabic Voice Note Test',
                'transcript' => $transcriptionData['transcript'],
                'audio_file' => $base64Audio
            ]);

        // Assert note was saved successfully
        $noteResponse->assertStatus(200);
        $noteData = $noteResponse->json();

        $this->assertTrue($noteData['success']);
        $this->assertEquals('Note created successfully', $noteData['message']);

        // Verify database record
        $this->assertDatabaseHas('doctor_notes', [
            'doctor_id' => $this->doctorUser->id,
            'patient_id' => $this->patientUser->id,
            'note_type' => 'voice',
            'title' => 'Arabic Voice Note Test'
        ]);

        $note = DoctorNote::where('doctor_id', $this->doctorUser->id)->first();
        $this->assertStringContainsString('التهاب اللوزتين الحاد', $note->transcript);
        $this->assertNotNull($note->audio_file_path);
        Storage::assertExists($note->audio_file_path);

        // Verify API calls were made correctly
        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === 'https://api.openai.com/v1/audio/transcriptions' &&
                   isset($data['model']) && $data['model'] === 'whisper-1' &&
                   !isset($data['language']) && // Auto-detection
                   isset($data['prompt']) && str_contains($data['prompt'], 'medical consultation');
        });

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === 'https://api.openai.com/v1/chat/completions' &&
                   isset($data['model']) && $data['model'] === 'gpt-4' &&
                   isset($data['messages']) && str_contains($data['messages'][0]['content'], 'PRESERVE THE ORIGINAL LANGUAGE');
        });
    }

    /** @test */
    public function test_complete_voice_note_workflow_english()
    {
        // Arrange
        Storage::fake('local');

        // Mock OpenAI API responses for English transcription
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'Patient presents with acute tonsillitis, fever, and sore throat symptoms',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "**Chief Complaint:**\n• Acute tonsillitis\n\n**History of Present Illness:**\n• Patient presents with fever\n• Sore throat symptoms\n• Difficulty swallowing\n\n**Assessment/Diagnosis:**\n• Acute Tonsillitis\n\n**Plan/Treatment:**\n• Antibiotics prescribed\n• Pain management\n• Follow-up in 3 days"
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Step 1: Transcribe audio
        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fake english audio data');

        $transcriptionResponse = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.transcribe-audio'), [
                'audio_file' => $base64Audio
            ]);

        // Assert transcription was successful
        $transcriptionResponse->assertStatus(200);
        $transcriptionData = $transcriptionResponse->json();

        $this->assertTrue($transcriptionData['success']);
        $this->assertStringContainsString('Acute tonsillitis', $transcriptionData['transcript']);
        $this->assertStringContainsString('Chief Complaint', $transcriptionData['transcript']);
        $this->assertStringContainsString('Assessment/Diagnosis', $transcriptionData['transcript']);

        // Step 2: Save the voice note
        $noteResponse = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.store'), [
                'note_type' => 'voice',
                'patient_id' => $this->patientUser->id,
                'title' => 'English Voice Note Test',
                'transcript' => $transcriptionData['transcript'],
                'audio_file' => $base64Audio
            ]);

        // Assert note was saved successfully
        $noteResponse->assertStatus(200);
        $this->assertTrue($noteResponse->json()['success']);

        // Verify the note contains proper English medical formatting
        $note = DoctorNote::where('doctor_id', $this->doctorUser->id)->first();
        $this->assertStringContainsString('Chief Complaint', $note->transcript);
        $this->assertStringContainsString('Acute tonsillitis', $note->transcript);
        $this->assertStringNotContainsString('الشكوى الرئيسية', $note->transcript); // Should not contain Arabic
    }

    /** @test */
    public function test_transcription_handles_medical_terminology_accurately()
    {
        // Arrange
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'Patient diagnosed with acute tonsillitis not acute inflammation in both lungs',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "**Assessment/Diagnosis:**\n• Acute Tonsillitis\n• Not acute inflammation in both lungs\n\n**Clinical Notes:**\n• Proper diagnosis confirmed as tonsillitis\n• Differential diagnosis ruled out pneumonia"
                        ]
                    ]
                ]
            ], 200)
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('medical terminology audio');

        // Act
        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.transcribe-audio'), [
                'audio_file' => $base64Audio
            ]);

        // Assert
        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertStringContainsString('Acute Tonsillitis', $data['transcript']);
        $this->assertStringContainsString('Not acute inflammation in both lungs', $data['transcript']);

        // Verify that the medical context prompt was used
        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === 'https://api.openai.com/v1/audio/transcriptions' &&
                   isset($data['prompt']) &&
                   str_contains($data['prompt'], 'medical consultation recording') &&
                   str_contains($data['prompt'], 'medical terminology');
        });
    }

    /** @test */
    public function test_transcription_fallback_when_gpt4_fails()
    {
        // Arrange
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(
                'Patient has acute tonsillitis. Fever present. Throat is inflamed.',
                200
            ),
            'api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Rate limit exceeded']
            ], 429)
        ]);

        $base64Audio = 'data:audio/webm;base64,' . base64_encode('fallback test audio');

        // Act
        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.transcribe-audio'), [
                'audio_file' => $base64Audio
            ]);

        // Assert
        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);

        // Should fallback to basic formatting with bullet points
        $this->assertStringContainsString('•', $data['transcript']);
        $this->assertStringContainsString('Patient has acute tonsillitis', $data['transcript']);
        $this->assertStringContainsString('Fever present', $data['transcript']);
    }

    /** @test */
    public function test_unauthorized_user_cannot_transcribe_audio()
    {
        // Arrange
        $patientUser = User::factory()->create(['role' => 'patient']);
        $base64Audio = 'data:audio/webm;base64,' . base64_encode('unauthorized audio');

        // Act
        $response = $this->actingAs($patientUser)
            ->postJson(route('doctor.notes.transcribe-audio'), [
                'audio_file' => $base64Audio
            ]);

        // Assert
        $response->assertStatus(403); // Forbidden - only doctors can transcribe
    }

    /** @test */
    public function test_transcription_with_invalid_audio_format()
    {
        // Act
        $response = $this->actingAs($this->doctorUser)
            ->postJson(route('doctor.notes.transcribe-audio'), [
                'audio_file' => 'invalid-audio-data'
            ]);

        // Assert
        $response->assertStatus(422);
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Empty audio data', $data['message']);
    }

    /** @test */
    public function test_voice_note_creation_page_has_enhanced_ui_elements()
    {
        // Act
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.notes.create'));

        // Assert
        $response->assertStatus(200);

        // Check for enhanced UI elements
        $response->assertSee('Transcribe & Format');
        $response->assertSee('Auto-detecting language and formatting medical content');
        $response->assertSee('Formatted medical transcription will appear here');
        $response->assertSee('preserve the original language');

        // Check for CSS classes
        $response->assertSee('transcription-enhanced');
        $response->assertSee('transcription-processing');
    }

    /** @test */
    public function test_notes_index_displays_voice_notes_correctly()
    {
        // Arrange
        $voiceNote = DoctorNote::factory()->create([
            'doctor_id' => $this->doctorUser->id,
            'patient_id' => $this->patientUser->id,
            'note_type' => 'voice',
            'title' => 'Test Voice Note',
            'transcript' => "**الشكوى الرئيسية:**\n• التهاب اللوزتين الحاد",
            'audio_file_path' => 'doctor-notes/test-audio.webm'
        ]);

        // Act
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.notes.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Test Voice Note');
        $response->assertSee('voice'); // Note type
        $response->assertSee($this->patientUser->name);
    }
}
