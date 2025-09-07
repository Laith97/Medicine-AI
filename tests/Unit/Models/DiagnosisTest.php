<?php

namespace Tests\Unit\Models;

use App\Models\Diagnosis;
use App\Models\User;
use App\Models\DiagnosisFollowUp;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiagnosisTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $diagnosis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com'
        ]);

        $this->diagnosis = Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'patient_name' => 'John Doe',
            'patient_age' => 35,
            'patient_gender' => 'male',
            'symptoms' => 'Fever, headache, body aches',
            'diagnosis_text' => 'Viral syndrome',
            'confidence_level' => 85,
            'status' => 'active'
        ]);
    }

    public function test_diagnosis_can_be_created()
    {
        $this->assertInstanceOf(Diagnosis::class, $this->diagnosis);
        $this->assertEquals('John Doe', $this->diagnosis->patient_name);
        $this->assertEquals(35, $this->diagnosis->patient_age);
        $this->assertEquals('Viral syndrome', $this->diagnosis->diagnosis_text);
        $this->assertEquals(85, $this->diagnosis->confidence_level);
    }

    public function test_diagnosis_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->diagnosis->user);
        $this->assertEquals($this->user->id, $this->diagnosis->user->id);
    }

    public function test_diagnosis_has_follow_ups_relationship()
    {
        $followUp = DiagnosisFollowUp::factory()->create([
            'diagnosis_id' => $this->diagnosis->id,
            'follow_up_date' => now()->addWeek(),
            'notes' => 'Schedule follow-up appointment'
        ]);

        $this->assertTrue($this->diagnosis->followUps->contains($followUp));
    }

    public function test_diagnosis_status_can_be_updated()
    {
        $this->assertEquals('active', $this->diagnosis->status);

        $this->diagnosis->update(['status' => 'resolved']);
        $this->diagnosis->refresh();

        $this->assertEquals('resolved', $this->diagnosis->status);
    }

    public function test_diagnosis_confidence_level_validation()
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'confidence_level' => 95
        ]);

        $this->assertEquals(95, $diagnosis->confidence_level);
        $this->assertTrue($diagnosis->confidence_level >= 0 && $diagnosis->confidence_level <= 100);
    }

    public function test_diagnosis_with_comprehensive_data()
    {
        $comprehensiveData = [
            'user_id' => $this->user->id,
            'patient_name' => 'Jane Smith',
            'patient_age' => 42,
            'patient_gender' => 'female',
            'symptoms' => 'Chest pain, shortness of breath',
            'vital_signs' => json_encode([
                'blood_pressure' => '140/90',
                'heart_rate' => 95,
                'temperature' => 98.6,
                'respiratory_rate' => 20
            ]),
            'diagnosis_text' => 'Possible angina pectoris',
            'differential_diagnosis' => json_encode([
                'Myocardial infarction',
                'Pulmonary embolism',
                'Anxiety disorder'
            ]),
            'recommended_tests' => json_encode([
                'ECG',
                'Chest X-ray',
                'Cardiac enzymes'
            ]),
            'treatment_plan' => 'Nitroglycerin PRN, cardiology consult',
            'confidence_level' => 75,
            'urgency_level' => 'high',
            'status' => 'active'
        ];

        $diagnosis = Diagnosis::create($comprehensiveData);

        $this->assertEquals('Jane Smith', $diagnosis->patient_name);
        $this->assertEquals('Possible angina pectoris', $diagnosis->diagnosis_text);
        $this->assertEquals(75, $diagnosis->confidence_level);
        $this->assertEquals('high', $diagnosis->urgency_level);

        $vitalSigns = json_decode($diagnosis->vital_signs, true);
        $this->assertEquals('140/90', $vitalSigns['blood_pressure']);

        $differentialDx = json_decode($diagnosis->differential_diagnosis, true);
        $this->assertContains('Myocardial infarction', $differentialDx);
    }

    public function test_diagnosis_can_be_marked_as_resolved()
    {
        $this->diagnosis->update([
            'status' => 'resolved',
            'resolution_notes' => 'Patient recovered fully after treatment'
        ]);

        $this->assertEquals('resolved', $this->diagnosis->status);
        $this->assertEquals('Patient recovered fully after treatment', $this->diagnosis->resolution_notes);
    }

    public function test_diagnosis_urgency_levels()
    {
        $urgencyLevels = ['low', 'medium', 'high', 'critical'];

        foreach ($urgencyLevels as $level) {
            $diagnosis = Diagnosis::factory()->create([
                'user_id' => $this->user->id,
                'urgency_level' => $level
            ]);

            $this->assertEquals($level, $diagnosis->urgency_level);
        }
    }

    public function test_diagnosis_can_have_ai_analysis()
    {
        $aiAnalysis = [
            'model_used' => 'gpt-4',
            'processing_time' => 2.5,
            'tokens_used' => 1250,
            'analysis_confidence' => 0.85,
            'suggested_followup' => 'Monitor symptoms for 48 hours'
        ];

        $this->diagnosis->update([
            'ai_analysis' => json_encode($aiAnalysis)
        ]);

        $storedAnalysis = json_decode($this->diagnosis->ai_analysis, true);
        $this->assertEquals('gpt-4', $storedAnalysis['model_used']);
        $this->assertEquals(2.5, $storedAnalysis['processing_time']);
        $this->assertEquals(0.85, $storedAnalysis['analysis_confidence']);
    }

    public function test_diagnosis_search_by_patient()
    {
        // Create multiple diagnoses for the same patient
        Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'patient_name' => 'John Doe',
            'patient_age' => 35,
            'diagnosis_text' => 'Hypertension'
        ]);

        Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'patient_name' => 'John Doe',
            'patient_age' => 35,
            'diagnosis_text' => 'Diabetes Type 2'
        ]);

        $patientDiagnoses = Diagnosis::where('patient_name', 'John Doe')
            ->where('patient_age', 35)
            ->where('user_id', $this->user->id)
            ->get();

        $this->assertCount(3, $patientDiagnoses); // Including the one from setUp
    }
}
