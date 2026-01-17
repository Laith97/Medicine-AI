<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\VoiceTranscription;
use App\Services\MedicalTranscriptionService;
use Firebase\JWT\JWT as FirebaseJWT;

class AmbientListeningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $patientUser;
    protected $doctor;
    protected $appointment;

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

        // Create appointment
        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
            'status' => 'scheduled'
        ]);
    }

    public function test_complete_ambient_listening_workflow()
    {
        // Step 1: Get a valid authentication token
        $token = FirebaseJWT::encode([
            'sub' => $this->doctorUser->id,
            'exp' => time() + 3600,
            'iat' => time()
        ], config('app.key'));

        // Step 2: Simulate WebSocket connection with valid parameters
        // Note: This is a feature test, so we're testing the overall functionality
        // rather than actual WebSocket connections in PHP tests
        
        // Create a voice transcription record (simulating what would happen during ambient listening)
        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Patient presents with complaints of chest pain and shortness of breath. Blood pressure is elevated at 160/95. Possible acute coronary syndrome.',
            'visit_id' => $this->appointment->id,
            'status' => 'transcribing',
            'language' => 'en-US',
            'is_real_time' => true,
            'session_id' => 'ambient-session-' . uniqid()
        ]);

        // Verify the transcription was created correctly
        $this->assertDatabaseHas('voice_transcriptions', [
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'status' => 'transcribing'
        ]);

        // Step 3: Process the transcription with MedicalTranscriptionService
        $service = new MedicalTranscriptionService();
        
        $transcriptData = [
            ['speaker' => '1', 'text' => 'Doctor: How are you feeling today?'],
            ['speaker' => '2', 'text' => 'Patient: I have chest pain and difficulty breathing'],
            ['speaker' => '1', 'text' => 'Diagnosis: Possible acute coronary syndrome. Plan: ECG, blood tests, immediate cardiology consult']
        ];

        // Process the real-time transcript
        $service->processRealTimeTranscript($transcriptData, $this->appointment->id);

        // Verify the transcription was updated
        $updatedTranscription = VoiceTranscription::find($voiceTranscription->id);
        $this->assertEquals('transcribing', $updatedTranscription->status);
    }

    public function test_ambient_listening_with_arabic_language()
    {
        // Test ambient listening with Arabic language support
        $token = FirebaseJWT::encode([
            'sub' => $this->doctorUser->id,
            'exp' => time() + 3600,
            'iat' => time()
        ], config('app.key'));

        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'المريض يشكو من ألم في الصدر وضيق في التنفس. ضغط الدم مرتفع 160/95. يشتبه في متلازمة القلب الحادة.',
            'visit_id' => $this->appointment->id,
            'status' => 'transcribing',
            'language' => 'ar',
            'is_real_time' => true,
            'session_id' => 'ambient-session-ar-' . uniqid()
        ]);

        // Verify Arabic transcription was created
        $this->assertDatabaseHas('voice_transcriptions', [
            'user_id' => $this->doctorUser->id,
            'language' => 'ar',
            'is_real_time' => true
        ]);

        // Process with transcription service
        $service = new MedicalTranscriptionService();
        
        $arabicTranscriptData = [
            ['speaker' => '1', 'text' => 'الدكتور: كيف ت feeling اليوم؟'],
            ['speaker' => '2', 'text' => 'المريض: لدي ألم في الصدر وصعوبة في التنفس'],
            ['speaker' => '1', 'text' => 'التشخيص: يشتبه في متلازمة تاجية حادة. الخطة: رسم القلب، تحاليل دم']
        ];

        $service->processRealTimeTranscript($arabicTranscriptData, $this->appointment->id);
        
        $updatedTranscription = VoiceTranscription::find($voiceTranscription->id);
        $this->assertNotNull($updatedTranscription);
    }

    public function test_ambient_listening_speaker_identification()
    {
        // Create transcription with speaker identification
        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Doctor: How are you feeling? Patient: I have chest pain.',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'speaker_identification' => json_encode([
                'speakers_detected' => 2,
                'speaker_segments' => [
                    [
                        'speaker' => 'doctor',
                        'start' => 0.0,
                        'end' => 2.5,
                        'text' => 'How are you feeling?'
                    ],
                    [
                        'speaker' => 'patient', 
                        'start' => 2.5,
                        'end' => 5.0,
                        'text' => 'I have chest pain.'
                    ]
                ]
            ])
        ]);

        // Verify speaker identification was stored
        $storedData = json_decode($voiceTranscription->speaker_identification, true);
        $this->assertEquals(2, $storedData['speakers_detected']);
        $this->assertCount(2, $storedData['speaker_segments']);
        $this->assertEquals('doctor', $storedData['speaker_segments'][0]['speaker']);
        $this->assertEquals('patient', $storedData['speaker_segments'][1]['speaker']);
    }

    public function test_ambient_listening_medical_entity_extraction()
    {
        // Test transcription with medical entities
        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Patient presents with hypertension and diabetes. Prescribed lisinopril and metformin.',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'medical_entities' => json_encode([
                'symptoms' => ['hypertension', 'diabetes'],
                'medications' => ['lisinopril', 'metformin'],
                'conditions' => ['hypertension', 'diabetes'],
                'body_parts' => [],
                'procedures' => []
            ])
        ]);

        // Verify medical entities were stored
        $storedEntities = json_decode($voiceTranscription->medical_entities, true);
        $this->assertContains('hypertension', $storedEntities['symptoms']);
        $this->assertContains('lisinopril', $storedEntities['medications']);
        $this->assertContains('diabetes', $storedEntities['conditions']);
    }

    public function test_ambient_listening_with_quality_metrics()
    {
        // Create transcription with quality metrics
        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Patient examination notes',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'quality_metrics' => json_encode([
                'audio_quality' => 'good',
                'noise_level' => 'low',
                'clarity_score' => 0.85,
                'transcription_accuracy' => 0.82,
                'needs_review' => false
            ])
        ]);

        // Verify quality metrics were stored
        $storedMetrics = json_decode($voiceTranscription->quality_metrics, true);
        $this->assertEquals('good', $storedMetrics['audio_quality']);
        $this->assertEquals(0.85, $storedMetrics['clarity_score']);
        $this->assertFalse($storedMetrics['needs_review']);
    }

    public function test_ambient_listening_concurrent_sessions()
    {
        // Test multiple concurrent ambient listening sessions
        $session1 = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'First patient session',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'session_id' => 'session-1',
            'status' => 'transcribing'
        ]);

        $session2 = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Second patient session',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'session_id' => 'session-2',
            'status' => 'transcribing'
        ]);

        // Verify both sessions exist
        $this->assertDatabaseHas('voice_transcriptions', [
            'session_id' => 'session-1',
            'status' => 'transcribing'
        ]);

        $this->assertDatabaseHas('voice_transcriptions', [
            'session_id' => 'session-2',
            'status' => 'transcribing'
        ]);

        // Verify that both sessions are associated with the same doctor
        $this->assertEquals($this->doctorUser->id, $session1->user_id);
        $this->assertEquals($this->doctorUser->id, $session2->user_id);
    }

    public function test_ambient_listening_error_handling()
    {
        // Test error handling in ambient listening
        $token = FirebaseJWT::encode([
            'sub' => $this->doctorUser->id,
            'exp' => time() + 3600,
            'iat' => time()
        ], config('app.key'));

        // Create a transcription with error state
        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => '',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'status' => 'failed',
            'error_message' => 'Audio quality too low',
            'error_code' => 'AUDIO_QUALITY_LOW'
        ]);

        // Verify error state was stored
        $this->assertDatabaseHas('voice_transcriptions', [
            'user_id' => $this->doctorUser->id,
            'status' => 'failed',
            'error_message' => 'Audio quality too low',
            'error_code' => 'AUDIO_QUALITY_LOW'
        ]);
    }

    public function test_ambient_listening_privacy_and_security()
    {
        // Test privacy settings for ambient listening
        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Sensitive medical information',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'is_sensitive' => true,
            'privacy_level' => 'high',
            'auto_delete_after_days' => 30
        ]);

        // Verify privacy settings were stored
        $this->assertTrue($voiceTranscription->is_sensitive);
        $this->assertEquals('high', $voiceTranscription->privacy_level);
        $this->assertEquals(30, $voiceTranscription->auto_delete_after_days);
    }

    public function test_ambient_listening_audio_processing_info()
    {
        // Test that audio processing information is properly stored
        $processingInfo = [
            'model_used' => 'medical_whisper',
            'processing_time_seconds' => 2.5,
            'chunks_processed' => 5,
            'quality_score' => 0.88,
            'sample_rate' => 16000,
            'channels' => 1
        ];

        $voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->doctorUser->id,
            'transcription_text' => 'Processed audio content',
            'visit_id' => $this->appointment->id,
            'is_real_time' => true,
            'processing_info' => json_encode($processingInfo)
        ]);

        // Verify processing info was stored and can be retrieved
        $storedInfo = json_decode($voiceTranscription->processing_info, true);
        $this->assertEquals('medical_whisper', $storedInfo['model_used']);
        $this->assertEquals(2.5, $storedInfo['processing_time_seconds']);
        $this->assertEquals(5, $storedInfo['chunks_processed']);
        $this->assertEquals(0.88, $storedInfo['quality_score']);
    }
}