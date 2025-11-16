<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\HEPSafetyService;
use App\Models\User;
use App\Models\HepAssignment;
use App\Models\HepExercise;
use App\Models\HepProgram;
use App\Models\HepProgress;
use App\Models\PatientData;
use App\Models\Exercise;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\HEPSafetyAlert;

class HEPSafetyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $safetyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->safetyService = new HEPSafetyService();
    }

    /** @test */
    public function check_contraindications_returns_empty_array_when_no_patient_data()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exercise = HepExercise::factory()->create();

        $result = $this->safetyService->checkContraindications($patient, $exercise);

        $this->assertCount(1, $result);
        $this->assertEquals('no_medical_data', $result[0]['type']);
        $this->assertEquals('warning', $result[0]['severity']);
    }

    /** @test */
    public function check_contraindications_returns_empty_array_when_no_contraindications_defined()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create(['contraindications' => null]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'past_medical_history' => ['diabetes'],
            'allergies' => ['penicillin'],
            'symptoms' => ['headache']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);

        $this->assertEmpty($result);
    }

    /** @test */
    public function check_contraindications_detects_medical_history_matches()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create([
            'contraindications' => ['heart disease', 'hypertension']
        ]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'past_medical_history' => ['coronary artery disease', 'diabetes']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);

        $this->assertCount(1, $result);
        $this->assertEquals('medical_history', $result[0]['type']);
        $this->assertEquals('high', $result[0]['severity']);
        $this->assertStringContains('coronary artery disease', $result[0]['message']);
    }

    /** @test */
    public function check_contraindications_detects_allergy_matches()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create([
            'contraindications' => ['latex allergy', 'shellfish']
        ]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'allergies' => ['latex', 'peanuts']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);

        $this->assertCount(1, $result);
        $this->assertEquals('allergy', $result[0]['type']);
        $this->assertEquals('critical', $result[0]['severity']);
        $this->assertStringContains('latex', $result[0]['message']);
    }

    /** @test */
    public function check_contraindications_detects_symptom_matches()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create([
            'contraindications' => ['chest pain', 'shortness of breath']
        ]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'symptoms' => ['chest pain', 'fatigue']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);

        $this->assertCount(1, $result);
        $this->assertEquals('current_symptom', $result[0]['type']);
        $this->assertEquals('high', $result[0]['severity']);
        $this->assertStringContains('chest pain', $result[0]['message']);
    }

    /** @test */
    public function check_pain_threshold_returns_no_alerts_for_low_pain()
    {
        $assignment = HepAssignment::factory()->create();

        $result = $this->safetyService->checkPainThreshold($assignment, 2);

        $this->assertEmpty($result);
    }

    /** @test */
    public function check_pain_threshold_returns_moderate_alert_for_level_5()
    {
        $assignment = HepAssignment::factory()->create();

        $result = $this->safetyService->checkPainThreshold($assignment, 5);

        $this->assertCount(1, $result);
        $this->assertEquals('moderate_pain', $result[0]['type']);
        $this->assertEquals('medium', $result[0]['severity']);
        $this->assertEquals('monitor', $result[0]['action_required']);
    }

    /** @test */
    public function check_pain_threshold_returns_severe_alert_for_level_8()
    {
        $assignment = HepAssignment::factory()->create();

        $result = $this->safetyService->checkPainThreshold($assignment, 8);

        $this->assertCount(1, $result);
        $this->assertEquals('severe_pain', $result[0]['type']);
        $this->assertEquals('high', $result[0]['severity']);
        $this->assertEquals('pause_program', $result[0]['action_required']);
    }

    /** @test */
    public function check_pain_threshold_returns_critical_alert_for_level_10()
    {
        $assignment = HepAssignment::factory()->create();

        $result = $this->safetyService->checkPainThreshold($assignment, 10);

        $this->assertCount(1, $result);
        $this->assertEquals('critical_pain', $result[0]['type']);
        $this->assertEquals('critical', $result[0]['severity']);
        $this->assertEquals('emergency_contact', $result[0]['action_required']);
    }

    /** @test */
    public function check_pain_threshold_detects_increasing_trend()
    {
        $assignment = HepAssignment::factory()->create();

        // Create progress with increasing pain levels
        HepProgress::factory()->create([
            'hep_assignment_id' => $assignment->id,
            'pain_level' => 8,
            'date' => now()->subDays(2)
        ]);
        HepProgress::factory()->create([
            'hep_assignment_id' => $assignment->id,
            'pain_level' => 7,
            'date' => now()->subDays(1)
        ]);
        HepProgress::factory()->create([
            'hep_assignment_id' => $assignment->id,
            'pain_level' => 6,
            'date' => now()
        ]);

        $result = $this->safetyService->checkPainThreshold($assignment, 6);

        $this->assertCount(1, $result);
        $this->assertEquals('increasing_pain_trend', $result[0]['type']);
        $this->assertEquals('high', $result[0]['severity']);
    }

    /** @test */
    public function handle_safety_concerns_pauses_program_for_critical_alerts()
    {
        Notification::fake();
        $assignment = HepAssignment::factory()->create();
        $program = HepProgram::factory()->create();
        $assignment->hep_program_id = $program->id;
        $assignment->save();

        $alerts = [
            [
                'severity' => 'critical',
                'message' => 'Critical pain level',
                'action_required' => 'emergency_contact'
            ]
        ];

        $result = $this->safetyService->handleSafetyConcerns($assignment, $alerts);

        $this->assertTrue($result);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hep_safety',
            'event_type' => 'program_paused'
        ]);
    }

    /** @test */
    public function handle_safety_concerns_pauses_program_for_high_severity_pause_alerts()
    {
        $assignment = HepAssignment::factory()->create();
        $program = HepProgram::factory()->create();
        $assignment->hep_program_id = $program->id;
        $assignment->save();

        $alerts = [
            [
                'severity' => 'high',
                'message' => 'Severe pain',
                'action_required' => 'pause_program'
            ]
        ];

        $result = $this->safetyService->handleSafetyConcerns($assignment, $alerts);

        $this->assertTrue($result);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hep_safety',
            'event_type' => 'program_paused'
        ]);
    }

    /** @test */
    public function handle_safety_concerns_does_not_pause_for_medium_severity_alerts()
    {
        $assignment = HepAssignment::factory()->create();

        $alerts = [
            [
                'severity' => 'medium',
                'message' => 'Moderate pain',
                'action_required' => 'monitor'
            ]
        ];

        $result = $this->safetyService->handleSafetyConcerns($assignment, $alerts);

        $this->assertFalse($result);
    }

    /** @test */
    public function get_emergency_contact_returns_null_when_no_contact_info()
    {
        $patient = User::factory()->create([
            'role' => 'patient',
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null
        ]);

        $result = $this->safetyService->getEmergencyContact($patient);

        $this->assertNull($result);
    }

    /** @test */
    public function get_emergency_contact_returns_contact_info_when_available()
    {
        $patient = User::factory()->create([
            'role' => 'patient',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '+1234567890'
        ]);

        $result = $this->safetyService->getEmergencyContact($patient);

        $this->assertEquals([
            'name' => 'John Doe',
            'phone' => '+1234567890'
        ], $result);
    }

    /** @test */
    public function notify_emergency_contact_logs_warning_when_no_emergency_contact()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Emergency contact notification failed - no emergency contact information', [
                'patient_id' => 1
            ]);

        $patient = User::factory()->create(['role' => 'patient']);
        $alerts = [['message' => 'Test alert']];

        $this->safetyService->notifyEmergencyContact($patient, $alerts);
    }

    /** @test */
    public function notify_emergency_contact_sends_notifications_and_logs_critical()
    {
        Log::shouldReceive('critical')
            ->once()
            ->with('Emergency contact notification sent', [
                'patient_id' => 1,
                'emergency_contact' => ['name' => 'John Doe', 'phone' => '+1234567890'],
                'alerts' => [['message' => 'Test alert']]
            ]);

        Notification::fake();

        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create([
            'role' => 'patient',
            'primary_doctor_id' => $doctor->id,
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '+1234567890'
        ]);

        $alerts = [['message' => 'Test alert']];

        $this->safetyService->notifyEmergencyContact($patient, $alerts);

        Notification::assertSentTo($doctor, HEPSafetyAlert::class);
    }

    /** @test */
    public function pause_program_updates_program_status_and_logs_event()
    {
        $assignment = HepAssignment::factory()->create();
        $program = HepProgram::factory()->create(['status' => 'active']);
        $assignment->hep_program_id = $program->id;
        $assignment->save();

        $this->safetyService->pauseProgram($assignment, 'Safety concern');

        $this->assertDatabaseHas('hep_programs', [
            'id' => $program->id,
            'status' => 'paused'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hep_safety',
            'event_type' => 'program_paused'
        ]);
    }

    /** @test */
    public function resume_program_updates_program_status_and_logs_event()
    {
        $assignment = HepAssignment::factory()->create();
        $program = HepProgram::factory()->create(['status' => 'paused']);
        $assignment->hep_program_id = $program->id;
        $assignment->save();

        $this->safetyService->resumeProgram($assignment, 'Safety concern resolved');

        $this->assertDatabaseHas('hep_programs', [
            'id' => $program->id,
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hep_safety',
            'event_type' => 'program_resumed'
        ]);
    }

    /** @test */
    public function log_safety_event_creates_audit_log_entry()
    {
        $assignment = HepAssignment::factory()->create();

        $this->safetyService->logSafetyEvent($assignment, 'test_event', [
            'custom_data' => 'test_value'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hep_safety',
            'patient_id' => $assignment->patient_id,
            'event_type' => 'test_event',
            'custom_data' => 'test_value'
        ]);
    }

    /** @test */
    public function matches_condition_performs_case_insensitive_exact_match()
    {
        $reflection = new \ReflectionClass($this->safetyService);
        $method = $reflection->getMethod('matchesCondition');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->safetyService, 'HEART DISEASE', 'heart disease'));
        $this->assertTrue($method->invoke($this->safetyService, '  heart disease  ', '  HEART DISEASE  '));
    }

    /** @test */
    public function matches_condition_performs_partial_match()
    {
        $reflection = new \ReflectionClass($this->safetyService);
        $method = $reflection->getMethod('matchesCondition');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->safetyService, 'heart disease', 'heart'));
        $this->assertTrue($method->invoke($this->safetyService, 'heart', 'heart disease'));
    }

    /** @test */
    public function is_increasing_trend_returns_false_for_insufficient_data()
    {
        $reflection = new \ReflectionClass($this->safetyService);
        $method = $reflection->getMethod('isIncreasingTrend');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->safetyService, [5, 6]));
        $this->assertFalse($method->invoke($this->safetyService, [5]));
    }

    /** @test */
    public function is_increasing_trend_detects_increasing_pain_levels()
    {
        $reflection = new \ReflectionClass($this->safetyService);
        $method = $reflection->getMethod('isIncreasingTrend');
        $method->setAccessible(true);

        // Increasing trend: 3, 5, 7 (most recent is highest)
        $this->assertTrue($method->invoke($this->safetyService, [7, 5, 3]));

        // Not increasing: 7, 5, 3 (most recent is lowest)
        $this->assertFalse($method->invoke($this->safetyService, [3, 5, 7]));

        // Not increasing: 5, 5, 5 (stable)
        $this->assertFalse($method->invoke($this->safetyService, [5, 5, 5]));
    }

    /** @test */
    public function check_contraindications_handles_array_and_string_medical_history()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create([
            'contraindications' => ['diabetes']
        ]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        // Test with array
        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'past_medical_history' => ['diabetes', 'hypertension']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);
        $this->assertCount(1, $result);

        // Test with string
        $patient->patientData()->delete();
        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'past_medical_history' => 'diabetes'
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function check_contraindications_handles_array_and_string_allergies()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create([
            'contraindications' => ['penicillin']
        ]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        // Test with array
        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'allergies' => ['penicillin', 'sulfa']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);
        $this->assertCount(1, $result);

        // Test with string
        $patient->patientData()->delete();
        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'allergies' => 'penicillin'
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function check_contraindications_handles_array_and_string_symptoms()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $exerciseModel = Exercise::factory()->create([
            'contraindications' => ['dizziness']
        ]);
        $exercise = HepExercise::factory()->create();
        $exercise->exercise = $exerciseModel;

        // Test with array
        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'symptoms' => ['dizziness', 'nausea']
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);
        $this->assertCount(1, $result);

        // Test with string
        $patient->patientData()->delete();
        PatientData::factory()->create([
            'patient_id' => $patient->id,
            'symptoms' => 'dizziness'
        ]);

        $result = $this->safetyService->checkContraindications($patient, $exercise);
        $this->assertCount(1, $result);
    }
}
