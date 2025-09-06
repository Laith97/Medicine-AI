<?php

namespace Tests\Unit\Models;

use App\Models\PatientAnalysis;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatientAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $patientAnalysis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com'
        ]);

        $this->patientAnalysis = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'age' => 35,
            'gender' => 'male',
            'symptoms' => 'Headache, fever',
            'preliminary_diagnosis' => 'Possible viral infection',
            'visit_number' => 1
        ]);
    }

    public function test_patient_analysis_can_be_created()
    {
        $this->assertInstanceOf(PatientAnalysis::class, $this->patientAnalysis);
        $this->assertEquals('John Doe', $this->patientAnalysis->name);
        $this->assertEquals(35, $this->patientAnalysis->age);
        $this->assertEquals('male', $this->patientAnalysis->gender);
    }

    public function test_patient_analysis_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'name', 'age', 'gender', 'weight', 'height', 'temperature',
            'blood_pressure', 'blood_sugar', 'symptoms', 'test_results',
            'preliminary_diagnosis', 'ai_response', 'user_id', 'assigned_patient_id',
            'previous_record_id', 'visit_number', 'patient_key',
            // Enhanced medical fields
            'chief_complaint', 'symptom_duration', 'past_medical_history',
            'medication_history', 'allergies', 'family_history', 'social_history',
            'pain_scale', 'visit_type', 'heart_rate', 'respiratory_rate',
            'oxygen_saturation', 'physician_notes', 'additional_notes',
            // Head-to-Toe Assessment fields
            'consciousness_level', 'mood_behavior', 'speech_clarity', 'hygiene_level',
            'scalp_condition', 'pupil_reactivity', 'vision_issues', 'hearing_issues',
            'oral_findings', 'orientation_level', 'limb_strength', 'reflexes',
            'sensation_findings', 'trachea_position', 'jvd_present', 'lung_sounds',
            'heart_sounds', 'capillary_refill_time', 'abdominal_shape', 'bowel_sounds',
            'abdominal_tenderness', 'nausea_or_vomiting', 'appetite_level',
            'urination_issues', 'catheter_present', 'urine_characteristics',
            'range_of_motion', 'gait_stability', 'assistive_devices', 'skin_color',
            'skin_temperature', 'skin_lesions', 'pressure_ulcers', 'pain_description'
        ];

        $this->assertEquals($expectedFillable, $this->patientAnalysis->getFillable());
    }

    public function test_patient_analysis_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->patientAnalysis->user);
        $this->assertEquals($this->user->id, $this->patientAnalysis->user->id);
    }

    public function test_patient_analysis_can_have_assigned_patient()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $analysis = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'assigned_patient_id' => $patient->id
        ]);

        $this->assertInstanceOf(User::class, $analysis->assignedPatient);
        $this->assertEquals($patient->id, $analysis->assignedPatient->id);
    }

    public function test_patient_analysis_can_have_previous_record()
    {
        $previousRecord = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'visit_number' => 1
        ]);

        $currentRecord = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'previous_record_id' => $previousRecord->id,
            'visit_number' => 2
        ]);

        $this->assertInstanceOf(PatientAnalysis::class, $currentRecord->previousRecord);
        $this->assertEquals($previousRecord->id, $currentRecord->previousRecord->id);
    }

    public function test_patient_analysis_can_have_subsequent_records()
    {
        $subsequentRecord = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'previous_record_id' => $this->patientAnalysis->id,
            'visit_number' => 2
        ]);

        $this->assertTrue($this->patientAnalysis->subsequentRecords->contains($subsequentRecord));
    }

    public function test_get_patient_history_by_patient_key()
    {
        $patientKey = 'test-patient-key-123';

        $record1 = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'patient_key' => $patientKey,
            'visit_number' => 1
        ]);

        $record2 = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'patient_key' => $patientKey,
            'visit_number' => 2
        ]);

        $history = $record1->getPatientHistory();

        $this->assertCount(2, $history);
        $this->assertEquals(1, $history->first()->visit_number);
        $this->assertEquals(2, $history->last()->visit_number);
    }

    public function test_get_patient_history_by_demographics()
    {
        $record1 = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Jane Smith',
            'age' => 30,
            'gender' => 'female',
            'patient_key' => null
        ]);

        $record2 = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Jane Smith',
            'age' => 30,
            'gender' => 'female',
            'patient_key' => null
        ]);

        $history = $record1->getPatientHistory();

        $this->assertCount(2, $history);
        $this->assertTrue($history->contains($record1));
        $this->assertTrue($history->contains($record2));
    }

    public function test_generate_patient_key()
    {
        $name = 'John Doe';
        $age = 35;
        $gender = 'male';
        $userId = $this->user->id;

        $expectedKey = md5($name . '-' . $age . '-' . $gender . '-' . $userId);
        $generatedKey = PatientAnalysis::generatePatientKey($name, $age, $gender, $userId);

        $this->assertEquals($expectedKey, $generatedKey);
    }

    public function test_patient_analysis_uses_correct_table()
    {
        $this->assertEquals('patient_data', $this->patientAnalysis->getTable());
    }

    public function test_patient_analysis_with_comprehensive_medical_data()
    {
        $comprehensiveData = [
            'user_id' => $this->user->id,
            'name' => 'Test Patient',
            'age' => 45,
            'gender' => 'female',
            'chief_complaint' => 'Chest pain',
            'symptom_duration' => '2 days',
            'past_medical_history' => 'Hypertension, Diabetes',
            'medication_history' => 'Metformin, Lisinopril',
            'allergies' => 'Penicillin',
            'family_history' => 'Heart disease',
            'social_history' => 'Non-smoker, occasional alcohol',
            'pain_scale' => 7,
            'visit_type' => 'Emergency',
            'heart_rate' => 85,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 98,
            'temperature' => 98.6,
            'blood_pressure' => '140/90',
            'consciousness_level' => 'Alert and oriented',
            'mood_behavior' => 'Anxious',
            'speech_clarity' => 'Clear',
            'hygiene_level' => 'Good',
            'physician_notes' => 'Patient presents with acute chest pain',
            'additional_notes' => 'Recommend cardiac workup'
        ];

        $analysis = PatientAnalysis::create($comprehensiveData);

        $this->assertInstanceOf(PatientAnalysis::class, $analysis);
        $this->assertEquals('Test Patient', $analysis->name);
        $this->assertEquals('Chest pain', $analysis->chief_complaint);
        $this->assertEquals(7, $analysis->pain_scale);
        $this->assertEquals('Emergency', $analysis->visit_type);
        $this->assertEquals(85, $analysis->heart_rate);
        $this->assertEquals('Penicillin', $analysis->allergies);
    }

    public function test_patient_analysis_head_to_toe_assessment_fields()
    {
        $headToToeData = [
            'user_id' => $this->user->id,
            'name' => 'Assessment Patient',
            'age' => 50,
            'gender' => 'male',
            // HEENT
            'scalp_condition' => 'Normal',
            'pupil_reactivity' => 'Reactive to light',
            'vision_issues' => 'None reported',
            'hearing_issues' => 'Mild hearing loss',
            'oral_findings' => 'Good dental hygiene',
            // Neurological
            'orientation_level' => 'Oriented x3',
            'limb_strength' => '5/5 all extremities',
            'reflexes' => 'Normal',
            'sensation_findings' => 'Intact',
            // Chest and cardiovascular
            'lung_sounds' => 'Clear bilaterally',
            'heart_sounds' => 'Regular rate and rhythm',
            'capillary_refill_time' => '<2 seconds',
            // Abdomen
            'abdominal_shape' => 'Soft, non-distended',
            'bowel_sounds' => 'Present in all quadrants',
            'abdominal_tenderness' => 'None',
            // Skin
            'skin_color' => 'Pink',
            'skin_temperature' => 'Warm',
            'skin_lesions' => 'None noted',
            'pressure_ulcers' => 'None'
        ];

        $analysis = PatientAnalysis::create($headToToeData);

        $this->assertEquals('Normal', $analysis->scalp_condition);
        $this->assertEquals('Reactive to light', $analysis->pupil_reactivity);
        $this->assertEquals('Oriented x3', $analysis->orientation_level);
        $this->assertEquals('Clear bilaterally', $analysis->lung_sounds);
        $this->assertEquals('Present in all quadrants', $analysis->bowel_sounds);
        $this->assertEquals('Pink', $analysis->skin_color);
    }
}
