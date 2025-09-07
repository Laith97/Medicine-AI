<?php

namespace Tests\Unit\Models;

use App\Models\VoiceTranscription;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoiceTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $voiceTranscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com'
        ]);

        $this->voiceTranscription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'transcription_text' => 'Patient presents with chest pain and shortness of breath',
            'duration' => 45.5,
            'status' => 'completed',
            'language' => 'en-US'
        ]);
    }

    public function test_voice_transcription_can_be_created()
    {
        $this->assertInstanceOf(VoiceTranscription::class, $this->voiceTranscription);
        $this->assertEquals('Patient presents with chest pain and shortness of breath', $this->voiceTranscription->transcription_text);
        $this->assertEquals(45.5, $this->voiceTranscription->duration);
        $this->assertEquals('completed', $this->voiceTranscription->status);
    }

    public function test_voice_transcription_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->voiceTranscription->user);
        $this->assertEquals($this->user->id, $this->voiceTranscription->user->id);
    }

    public function test_voice_transcription_with_ai_analysis()
    {
        $aiAnalysis = [
            'symptoms_identified' => ['chest pain', 'shortness of breath'],
            'possible_conditions' => ['angina', 'myocardial infarction', 'pulmonary embolism'],
            'urgency_level' => 'high',
            'confidence_score' => 0.85,
            'recommended_actions' => ['immediate medical attention', 'ECG', 'chest X-ray']
        ];

        $this->voiceTranscription->update([
            'ai_analysis' => json_encode($aiAnalysis),
            'status' => 'analyzed'
        ]);

        $storedAnalysis = json_decode($this->voiceTranscription->ai_analysis, true);
        $this->assertEquals('high', $storedAnalysis['urgency_level']);
        $this->assertContains('chest pain', $storedAnalysis['symptoms_identified']);
        $this->assertEquals('analyzed', $this->voiceTranscription->status);
    }

    public function test_voice_transcription_file_metadata()
    {
        $fileMetadata = [
            'original_filename' => 'patient_consultation.wav',
            'file_size' => 2048000,
            'file_format' => 'wav',
            'sample_rate' => 44100,
            'channels' => 1
        ];

        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'file_metadata' => json_encode($fileMetadata)
        ]);

        $storedMetadata = json_decode($transcription->file_metadata, true);
        $this->assertEquals('patient_consultation.wav', $storedMetadata['original_filename']);
        $this->assertEquals(2048000, $storedMetadata['file_size']);
        $this->assertEquals('wav', $storedMetadata['file_format']);
    }

    public function test_voice_transcription_processing_info()
    {
        $processingInfo = [
            'model_used' => 'whisper-1',
            'processing_time_seconds' => 12.3,
            'chunks_processed' => 3,
            'quality_score' => 0.92
        ];

        $this->voiceTranscription->update([
            'processing_info' => json_encode($processingInfo)
        ]);

        $storedInfo = json_decode($this->voiceTranscription->processing_info, true);
        $this->assertEquals('whisper-1', $storedInfo['model_used']);
        $this->assertEquals(12.3, $storedInfo['processing_time_seconds']);
        $this->assertEquals(0.92, $storedInfo['quality_score']);
    }

    public function test_voice_transcription_status_transitions()
    {
        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $this->assertEquals('pending', $transcription->status);

        $transcription->update(['status' => 'processing']);
        $this->assertEquals('processing', $transcription->status);

        $transcription->update(['status' => 'completed']);
        $this->assertEquals('completed', $transcription->status);
    }

    public function test_voice_transcription_error_handling()
    {
        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_message' => 'Audio file format not supported',
            'error_code' => 'UNSUPPORTED_FORMAT'
        ]);

        $this->assertEquals('failed', $transcription->status);
        $this->assertEquals('Audio file format not supported', $transcription->error_message);
        $this->assertEquals('UNSUPPORTED_FORMAT', $transcription->error_code);
    }

    public function test_voice_transcription_session_tracking()
    {
        $sessionId = 'session_' . uniqid();

        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => $sessionId,
            'is_real_time' => true
        ]);

        $this->assertEquals($sessionId, $transcription->session_id);
        $this->assertTrue($transcription->is_real_time);
    }

    public function test_voice_transcription_confidence_scores()
    {
        $confidenceData = [
            'overall_confidence' => 0.89,
            'word_confidences' => [
                'Patient' => 0.95,
                'presents' => 0.92,
                'with' => 0.88,
                'chest' => 0.85,
                'pain' => 0.91
            ],
            'low_confidence_words' => ['with']
        ];

        $this->voiceTranscription->update([
            'confidence_scores' => json_encode($confidenceData)
        ]);

        $storedScores = json_decode($this->voiceTranscription->confidence_scores, true);
        $this->assertEquals(0.89, $storedScores['overall_confidence']);
        $this->assertEquals(0.95, $storedScores['word_confidences']['Patient']);
        $this->assertContains('with', $storedScores['low_confidence_words']);
    }

    public function test_voice_transcription_speaker_identification()
    {
        $speakerData = [
            'speakers_detected' => 2,
            'speaker_segments' => [
                ['speaker' => 'doctor', 'start' => 0.0, 'end' => 15.5, 'text' => 'How are you feeling today?'],
                ['speaker' => 'patient', 'start' => 15.5, 'end' => 30.0, 'text' => 'I have been having chest pain']
            ]
        ];

        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'speaker_identification' => json_encode($speakerData)
        ]);

        $storedSpeakers = json_decode($transcription->speaker_identification, true);
        $this->assertEquals(2, $storedSpeakers['speakers_detected']);
        $this->assertCount(2, $storedSpeakers['speaker_segments']);
        $this->assertEquals('doctor', $storedSpeakers['speaker_segments'][0]['speaker']);
    }

    public function test_voice_transcription_medical_entities()
    {
        $medicalEntities = [
            'symptoms' => ['chest pain', 'shortness of breath', 'fatigue'],
            'medications' => ['aspirin', 'lisinopril'],
            'conditions' => ['hypertension'],
            'body_parts' => ['chest', 'heart'],
            'procedures' => ['ECG', 'blood test']
        ];

        $this->voiceTranscription->update([
            'medical_entities' => json_encode($medicalEntities)
        ]);

        $storedEntities = json_decode($this->voiceTranscription->medical_entities, true);
        $this->assertContains('chest pain', $storedEntities['symptoms']);
        $this->assertContains('aspirin', $storedEntities['medications']);
        $this->assertContains('ECG', $storedEntities['procedures']);
    }

    public function test_voice_transcription_privacy_settings()
    {
        $transcription = VoiceTranscription::factory()->create([
            'user_id' => $this->user->id,
            'is_sensitive' => true,
            'privacy_level' => 'high',
            'auto_delete_after_days' => 30
        ]);

        $this->assertTrue($transcription->is_sensitive);
        $this->assertEquals('high', $transcription->privacy_level);
        $this->assertEquals(30, $transcription->auto_delete_after_days);
    }

    public function test_voice_transcription_search_functionality()
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

        $diabetesTranscriptions = VoiceTranscription::where('user_id', $this->user->id)
            ->where('transcription_text', 'LIKE', '%diabetes%')
            ->get();

        $this->assertCount(2, $diabetesTranscriptions);
    }

    public function test_voice_transcription_export_formats()
    {
        $exportData = [
            'formats_available' => ['txt', 'pdf', 'docx', 'json'],
            'last_exported' => now()->toISOString(),
            'export_count' => 3
        ];

        $this->voiceTranscription->update([
            'export_metadata' => json_encode($exportData)
        ]);

        $storedExportData = json_decode($this->voiceTranscription->export_metadata, true);
        $this->assertContains('pdf', $storedExportData['formats_available']);
        $this->assertEquals(3, $storedExportData['export_count']);
    }

    public function test_voice_transcription_quality_metrics()
    {
        $qualityMetrics = [
            'audio_quality' => 'good',
            'noise_level' => 'low',
            'clarity_score' => 0.88,
            'transcription_accuracy' => 0.92,
            'needs_review' => false
        ];

        $this->voiceTranscription->update([
            'quality_metrics' => json_encode($qualityMetrics)
        ]);

        $storedMetrics = json_decode($this->voiceTranscription->quality_metrics, true);
        $this->assertEquals('good', $storedMetrics['audio_quality']);
        $this->assertEquals(0.88, $storedMetrics['clarity_score']);
        $this->assertFalse($storedMetrics['needs_review']);
    }
}
