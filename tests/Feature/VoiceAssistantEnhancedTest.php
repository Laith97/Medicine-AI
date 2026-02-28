<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VoiceTranscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceAssistantEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a doctor user
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@test.com'
        ]);

        // Create doctor profile
        $this->doctor->doctor()->create([
            'specialization' => 'Internal Medicine',
            'license_number' => 'DOC123456',
            'years_of_experience' => 10
        ]);

        // Create a patient
        $this->patient = User::factory()->create([
            'role' => 'patient',
            'primary_doctor_id' => $this->doctor->id
        ]);
    }

    /** @test */
    public function it_can_access_enhanced_voice_assistant_page()
    {
        $response = $this->actingAs($this->doctor)
            ->get('/voice-assistant');

        $response->assertStatus(200);
        $response->assertSee('Enhanced hands-free mode');
        $response->assertSee('Hands-Free Mode');
        $response->assertSee('enhancedStatusContainer');
    }

    /** @test */
    public function it_can_start_voice_session_with_enhanced_features()
    {
        $response = $this->actingAs($this->doctor)
            ->post('/voice-assistant/start-session', [
                'selectedPatient' => $this->patient->id
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Session started successfully.'
        ]);

        $this->assertDatabaseHas('voice_transcriptions', [
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_handle_transcription_updates()
    {
        // Start a session first
        $sessionResponse = $this->actingAs($this->doctor)
            ->post('/voice-assistant/start-session', [
                'selectedPatient' => $this->patient->id
            ]);

        $sessionId = $sessionResponse->json('sessionId');

        // Handle transcription
        $response = $this->actingAs($this->doctor)
            ->post('/voice-assistant/handle-transcription', [
                'sessionId' => $sessionId,
                'text' => 'Patient complains of chest pain and shortness of breath'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Transcription updated successfully.'
        ]);

        $this->assertDatabaseHas('voice_transcriptions', [
            'session_id' => $sessionId,
            'raw_transcription' => 'Patient complains of chest pain and shortness of breath'
        ]);
    }

    /** @test */
    public function it_can_process_transcription_with_ai()
    {
        // Start a session and add transcription
        $sessionResponse = $this->actingAs($this->doctor)
            ->post('/voice-assistant/start-session', [
                'selectedPatient' => $this->patient->id
            ]);

        $sessionId = $sessionResponse->json('sessionId');

        $this->actingAs($this->doctor)
            ->post('/voice-assistant/handle-transcription', [
                'sessionId' => $sessionId,
                'text' => 'Patient has chest pain, blood pressure 140/90, heart rate 95'
            ]);

        // Mock OpenAI response
        $this->mock(\OpenAI\Laravel\Facades\OpenAI::class, function ($mock) {
            $mock->shouldReceive('chat->create')
                ->andReturn([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'symptoms' => 'Chest pain',
                                    'vital_signs' => 'BP: 140/90, HR: 95',
                                    'medical_history' => '',
                                    'physical_findings' => '',
                                    'medications' => '',
                                    'diagnosis' => '',
                                    'care_plan' => ''
                                ])
                            ]
                        ]
                    ]
                ]);
        });

        $response = $this->actingAs($this->doctor)
            ->post('/voice-assistant/process-with-ai', [
                'sessionId' => $sessionId,
                'transcription' => 'Patient has chest pain, blood pressure 140/90, heart rate 95'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Medical data extracted successfully.'
        ]);
    }

    /** @test */
    public function it_can_stop_session_properly()
    {
        // Start a session
        $sessionResponse = $this->actingAs($this->doctor)
            ->post('/voice-assistant/start-session', [
                'selectedPatient' => $this->patient->id
            ]);

        $sessionId = $sessionResponse->json('sessionId');

        // Stop the session
        $response = $this->actingAs($this->doctor)
            ->post('/voice-assistant/stop-session', [
                'sessionId' => $sessionId
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Session stopped successfully.'
        ]);

        $this->assertDatabaseHas('voice_transcriptions', [
            'session_id' => $sessionId,
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function it_can_reset_session()
    {
        $response = $this->actingAs($this->doctor)
            ->post('/ai/voice-assistant/reset-session');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Session reset successfully.'
        ]);
    }

    /** @test */
    public function it_requires_patient_selection_for_session_start()
    {
        $response = $this->actingAs($this->doctor)
            ->post('/voice-assistant/start-session', [
                'selectedPatient' => null
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Please select a patient first.'
        ]);
    }

    /** @test */
    public function it_validates_transcription_length_for_ai_processing()
    {
        $sessionResponse = $this->actingAs($this->doctor)
            ->post('/voice-assistant/start-session', [
                'selectedPatient' => $this->patient->id
            ]);

        $sessionId = $sessionResponse->json('sessionId');

        $response = $this->actingAs($this->doctor)
            ->post('/voice-assistant/process-with-ai', [
                'sessionId' => $sessionId,
                'transcription' => 'short'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Transcription too short for processing.'
        ]);
    }
}
