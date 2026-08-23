<?php

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\AiAssistantResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('ai.prescription_suggestions.enabled', true);
    Config::set('ai.enabled', true);
    Config::set('openai.api_key', 'test-key-12345');
});

function createScenario(string $patientName = 'New Professional Patient', array $patientOverrides = [], array $appointmentOverrides = []): array
{
    $doctor = User::factory()->create([
        'role' => 'doctor',
        'email' => 'doctor_' . uniqid() . '@test.com',
        'name' => 'Dr. Test',
    ]);
    $doctorProfile = Doctor::factory()->create([
        'user_id' => $doctor->id,
        'is_active' => true,
    ]);

    $patient = User::factory()->create(array_merge([
        'role' => 'patient',
        'email' => strtolower(str_replace(' ', '.', $patientName)) . uniqid() . '@test.com',
        'name' => $patientName,
        'age' => 42,
        'gender' => 'male',
        'phone' => '+966500000001',
    ], $patientOverrides));

    $appointment = Appointment::factory()->create(array_merge([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'appointment_date' => now()->addDay(),
        'status' => 'confirmed',
        'appointment_type' => 'video_call',
        'reason' => 'General consultation for AI suggestion testing',
        'symptoms' => null,
        'doctor_notes' => null,
    ], $appointmentOverrides));

    return compact('doctor', 'doctorProfile', 'patient', 'appointment');
}

function createTestDiagnosis($patient, $doctor, $appointment, array $overrides = []): Diagnosis
{
    $base = [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_id' => $appointment->id,
        'diagnosis_text' => $overrides['diagnosis_text'] ?? 'Test diagnosis ' . uniqid(),
        'patient_data' => $overrides['patient_data'] ?? ['allergies' => 'No known allergies', 'medications' => 'None', 'clinical_notes' => 'Test notes'],
        'severity' => $overrides['severity'] ?? 'medium',
    ];
    // Remove overrides that were already handled to avoid duplication
    unset($overrides['diagnosis_text'], $overrides['patient_data'], $overrides['severity']);
    return Diagnosis::create(array_merge($base, $overrides));
}

function mockAIAssistantSuccess(array $suggestions = null, array $riskFlags = null): void
{
    $suggestions = $suggestions ?? [
        [
            'med' => 'Amoxicillin',
            'dosage' => '500mg',
            'freq' => 'three times daily',
            'dur' => '7 days',
            'confidence' => 88,
            'reason' => 'First-line for bacterial infection, no allergy',
            'warnings' => ['Take with food'],
            'interactions' => [],
        ],
        [
            'med' => 'Acetaminophen',
            'dosage' => '500mg',
            'freq' => 'every 6 hours as needed',
            'dur' => '3 days',
            'confidence' => 82,
            'reason' => 'Symptomatic fever relief',
            'warnings' => [],
            'interactions' => [],
        ],
    ];
    $riskFlags = $riskFlags ?? ['Verify allergies', 'Check renal function'];

    $mock = Mockery::mock(App\Services\AIAssistant::class);
    $mock->shouldReceive('generatePrescriptionSuggestionsWithFDAValidation')
        ->andReturn([
            'suggestions' => $suggestions,
            'risk_flags' => $riskFlags,
            'clinical_data_used' => ['symptoms' => 'fever, cough', 'doctor_notes' => 'pharyngitis'],
            'message' => 'AI suggestions generated',
            'source' => 'openai_fda_enhanced',
            'disclaimer' => 'Clinical decision support only',
            'generated_at' => now()->toISOString(),
        ]);
    app()->instance(App\Services\AIAssistant::class, $mock);
}

// ---------------------------------------------------------------------------
// Scenario 1: New patient with NO data — the exact bug reported (500 on Continue Limited)
// ---------------------------------------------------------------------------
it('new patient with no clinical data returns blocked response not 500 (bugfix verification)', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario(
        patientName: 'NewPatientNoData',
        patientOverrides: ['age' => 28, 'gender' => 'female'],
        appointmentOverrides: ['reason' => 'First visit, no prior records', 'doctor_notes' => null]
    );

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode([]),
        'allergies' => json_encode([]),
        'past_meds' => json_encode([]),
        'current_diagnosis' => json_encode(null),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data)->toHaveKey('suggestions');
    expect($data)->toHaveKey('risk_flags');
    expect($data['suggestions'][0]['med'])->toMatch('/Critical Data Missing|Clinical Assessment Required/');
    expect($response->json('message'))->not->toBeEmpty();
    expect($data)->not->toHaveKey('exception');
});

it('continue limited with dummy placeholders now succeeds (professional flow)', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario(
        patientName: 'ContinueLimitedPatient'
    );

    mockAIAssistantSuccess();

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['General consultation - no specific symptoms documented']),
        'allergies' => json_encode(['No known allergies']),
        'past_meds' => json_encode(['No current medications']),
        'current_diagnosis' => json_encode(null),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
        'doctor_notes' => 'General consultation',
        'continue_limited' => 1,
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data['suggestions'])->toHaveCount(2);
    expect($data['suggestions'][0]['med'])->toBe('Amoxicillin');
    expect($data)->toHaveKey('risk_flags');
});

// ---------------------------------------------------------------------------
// Scenario 2: Complete data — happy path with FDA validation
// ---------------------------------------------------------------------------
it('patient with complete verified data returns AI suggestions with FDA enrichment', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario(
        patientName: 'CompleteDataPatient',
        appointmentOverrides: ['doctor_notes' => 'Pharyngitis, fever 38.5C', 'reason' => 'Sore throat and fever']
    );

    createTestDiagnosis($patient, $doctor, $appointment, [
        'diagnosis_text' => 'Acute pharyngitis, likely bacterial',
        'patient_data' => [
            'allergies' => 'No known allergies',
            'medications' => 'None',
            'clinical_notes' => 'Sore throat, fever, tonsillar exudate',
            'weight' => 70,
            'height' => 175,
        ],
    ]);

    // Past diagnosis
    $pastDiag = createTestDiagnosis($patient, $doctor, $appointment, [
        'diagnosis_text' => 'Hypertension controlled',
        'patient_data' => ['allergies' => 'None'],
    ]);
    $pastDiag->update(['created_at' => now()->subMonths(2)]);

    mockAIAssistantSuccess();

    $currentDiagnosisArray = Diagnosis::where('patient_id', $patient->id)->latest()->first()->toArray();
    $pastDiagnosesArray = Diagnosis::where('patient_id', $patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get()->toArray();

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['Sore throat, fever']),
        'allergies' => json_encode(['No known allergies']),
        'past_meds' => json_encode(['None']),
        'current_diagnosis' => json_encode($currentDiagnosisArray),
        'past_diagnoses' => json_encode($pastDiagnosesArray),
        'voice_diagnosis' => json_encode(null),
        'doctor_notes' => 'Pharyngitis, fever 38.5C',
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data['suggestions'])->toHaveCount(2);
    expect($data['suggestions'][0])->toHaveKeys(['med', 'dosage', 'freq', 'dur', 'confidence', 'reason']);
    expect($data['suggestions'][0]['confidence'])->toBeGreaterThanOrEqual(80);
    expect($data)->toHaveKey('risk_flags');
    expect($data)->toHaveKey('clinical_data_used');
    expect($data['source'])->toBe('openai_fda_enhanced');
});

// ---------------------------------------------------------------------------
// Scenario 3: Missing allergies — should block
// ---------------------------------------------------------------------------
it('missing allergies blocks AI and returns critical data missing (professional safety)', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario(
        patientName: 'MissingAllergiesPatient'
    );

    $diag = createTestDiagnosis($patient, $doctor, $appointment, [
        'diagnosis_text' => 'Upper respiratory infection',
        'patient_data' => [
            'allergies' => '',
            'medications' => 'Paracetamol',
            'clinical_notes' => 'Cough and fever',
        ],
    ]);

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['Cough']),
        'allergies' => json_encode([]),
        'past_meds' => json_encode(['Paracetamol']),
        'current_diagnosis' => json_encode($diag->fresh()->toArray()),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data['suggestions'][0]['med'])->toBe('Critical Data Missing');
    expect(implode(' ', $data['risk_flags']))->toContain('Allergies');
    expect(($data['blocked'] ?? $data['requires_evaluation'] ?? false))->toBeTruthy();
});

// ---------------------------------------------------------------------------
// Scenario 4: Red flag — chest pain should block
// ---------------------------------------------------------------------------
it('red flag symptoms (chest pain) block medication suggestions and require evaluation', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario(
        patientName: 'RedFlagPatient',
        appointmentOverrides: ['reason' => 'Chest pain and shortness of breath', 'doctor_notes' => 'Chest pain, dyspnea']
    );

    $diag = createTestDiagnosis($patient, $doctor, $appointment, [
        'diagnosis_text' => 'Chest pain, need evaluation',
        'patient_data' => [
            'allergies' => 'No known allergies',
            'medications' => 'Aspirin',
            'clinical_notes' => 'Chest pain, shortness of breath',
        ],
    ]);

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['chest pain', 'shortness of breath']),
        'allergies' => json_encode(['No known allergies']),
        'past_meds' => json_encode(['Aspirin']),
        'current_diagnosis' => json_encode($diag->fresh()->toArray()),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
        'doctor_notes' => 'Chest pain, shortness of breath',
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data['requires_evaluation'] ?? $data['blocked'] ?? false)->toBeTrue();
    expect(implode(' ', $data['risk_flags']))->toContain('RED FLAG');
});

// ---------------------------------------------------------------------------
// Scenario 5: Unauthorized access
// ---------------------------------------------------------------------------
it('prevents unauthorized doctor and patient from accessing AI suggestions', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario();

    $otherDoctor = User::factory()->create(['role' => 'doctor']);
    Doctor::factory()->create(['user_id' => $otherDoctor->id, 'is_active' => true]);

    $responseOtherDoctor = $this->actingAs($otherDoctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['fever']),
        'allergies' => json_encode(['None']),
        'past_meds' => json_encode(['None']),
    ]);
    expect($responseOtherDoctor->status())->toBe(403);

    $responsePatient = $this->actingAs($patient)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['fever']),
        'allergies' => json_encode(['None']),
        'past_meds' => json_encode(['None']),
    ]);
    expect($responsePatient->status())->toBe(403);
});

// ---------------------------------------------------------------------------
// Scenario 6: Config disabled
// ---------------------------------------------------------------------------
it('returns disabled response when AI prescription suggestions are disabled', function () {
    Config::set('ai.prescription_suggestions.enabled', false);

    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario();

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['fever']),
        'allergies' => json_encode(['None']),
        'past_meds' => json_encode(['None']),
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data['disabled'])->toBeTrue();
    expect($data['suggestions'][0]['med'])->toBe('AI Feature Disabled');

    Config::set('ai.prescription_suggestions.enabled', true);
});

// ---------------------------------------------------------------------------
// Scenario 7: New patient vs existing patient with history
// ---------------------------------------------------------------------------
it('handles new patient with no history vs existing patient with rich history', function () {
    ['doctor' => $doctorNew, 'patient' => $patientNew, 'appointment' => $appointmentNew] = createScenario(
        patientName: 'BrandNewPatient',
        appointmentOverrides: ['doctor_notes' => 'First visit, mild cough']
    );
    Diagnosis::where('patient_id', $patientNew->id)->delete();
    mockAIAssistantSuccess();

    $responseNew = $this->actingAs($doctorNew)->post(route('ai.appointments.suggest', $appointmentNew), [
        'symptoms' => json_encode(['mild cough']),
        'allergies' => json_encode(['No known allergies']),
        'past_meds' => json_encode(['No current medications']),
        'current_diagnosis' => json_encode(null),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
        'doctor_notes' => 'First visit, mild cough',
        'continue_limited' => 1,
    ]);
    expect($responseNew->status())->toBe(200);
    expect($responseNew->json('suggestions'))->toHaveCount(2);

    ['doctor' => $doctorExist, 'patient' => $patientExist, 'appointment' => $appointmentExist] = createScenario(
        patientName: 'ExistingPatientWithHistory'
    );
    createTestDiagnosis($patientExist, $doctorExist, $appointmentExist, [
        'diagnosis_text' => 'Chronic bronchitis',
        'patient_data' => ['allergies' => 'Penicillin', 'medications' => 'Salbutamol', 'clinical_notes' => 'Chronic cough'],
    ]);
    $past = createTestDiagnosis($patientExist, $doctorExist, $appointmentExist, [
        'diagnosis_text' => 'Previous pneumonia 2024',
        'patient_data' => ['allergies' => 'Penicillin'],
    ]);
    $past->update(['created_at' => now()->subYear()]);

    AiAssistantResult::create([
        'doctor_id' => $doctorExist->id,
        'patient_id' => $patientExist->id,
        'source' => 'voice_assistant',
        'patient_data' => ['diagnosis' => 'Voice detected wheezing'],
        'ai_analysis' => 'Voice detected wheezing',
        'status' => 'pending',
    ]);

    mockAIAssistantSuccess();

    $currentArr = Diagnosis::where('patient_id', $patientExist->id)->latest()->first()->toArray();
    $pastArr = Diagnosis::where('patient_id', $patientExist->id)->orderBy('created_at','desc')->skip(1)->take(10)->get()->toArray();
    $voiceArr = AiAssistantResult::where('patient_id', $patientExist->id)->where('source','voice_assistant')->latest()->first()->toArray();

    $responseExist = $this->actingAs($doctorExist)->post(route('ai.appointments.suggest', $appointmentExist), [
        'symptoms' => json_encode(['chronic cough']),
        'allergies' => json_encode(['Penicillin']),
        'past_meds' => json_encode(['Salbutamol']),
        'current_diagnosis' => json_encode($currentArr),
        'past_diagnoses' => json_encode($pastArr),
        'voice_diagnosis' => json_encode($voiceArr),
    ]);
    expect($responseExist->status())->toBe(200);
    expect($responseExist->json('suggestions'))->toHaveCount(2);
    expect($responseExist->json('clinical_data_used'))->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Scenario 8: OpenAI failure fallback
// ---------------------------------------------------------------------------
it('handles OpenAI failure gracefully with fallback response', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario();
    createTestDiagnosis($patient, $doctor, $appointment, [
        'diagnosis_text' => 'Pharyngitis',
        'patient_data' => ['allergies' => 'None', 'medications' => 'None', 'clinical_notes' => 'Sore throat'],
    ]);

    $mock = Mockery::mock(App\Services\AIAssistant::class);
    $mock->shouldReceive('generatePrescriptionSuggestionsWithFDAValidation')
        ->andReturn([
            'suggestions' => [['med' => 'Fallback', 'dosage' => 'N/A', 'freq' => 'N/A', 'dur' => 'N/A', 'confidence' => 0, 'reason' => 'Fallback']],
            'risk_flags' => ['Fallback'],
            'fallback' => true,
            'message' => 'Fallback',
            'source' => 'fallback',
            'clinical_data_used' => [],
            'disclaimer' => 'Fallback',
            'generated_at' => now()->toISOString(),
        ]);
    app()->instance(App\Services\AIAssistant::class, $mock);

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['sore throat']),
        'allergies' => json_encode(['None']),
        'past_meds' => json_encode(['None']),
        'current_diagnosis' => json_encode(Diagnosis::where('patient_id', $patient->id)->latest()->first()->toArray()),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
    ]);

    expect($response->status())->toBe(200);
    $data = $response->json();
    expect($data)->toHaveKey('fallback');
    expect($data['fallback'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// Scenario 9: Professional response structure validation
// ---------------------------------------------------------------------------
it('validates professional response structure for all scenarios', function () {
    ['doctor' => $doctor, 'patient' => $patient, 'appointment' => $appointment] = createScenario();
    createTestDiagnosis($patient, $doctor, $appointment, [
        'diagnosis_text' => 'Test diagnosis',
        'patient_data' => ['allergies' => 'None', 'medications' => 'None', 'clinical_notes' => 'Test'],
    ]);
    mockAIAssistantSuccess();

    $response = $this->actingAs($doctor)->post(route('ai.appointments.suggest', $appointment), [
        'symptoms' => json_encode(['test']),
        'allergies' => json_encode(['None']),
        'past_meds' => json_encode(['None']),
        'current_diagnosis' => json_encode(Diagnosis::where('patient_id', $patient->id)->latest()->first()->toArray()),
        'past_diagnoses' => json_encode([]),
        'voice_diagnosis' => json_encode(null),
    ]);

    $data = $response->json();
    expect($data)->toHaveKeys(['suggestions', 'risk_flags', 'clinical_data_used', 'message', 'source', 'disclaimer', 'generated_at']);
    foreach ($data['suggestions'] as $s) {
        expect($s)->toHaveKeys(['med', 'dosage', 'freq', 'dur', 'confidence', 'reason']);
        expect($s['confidence'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
    }
    expect($data['risk_flags'])->toBeArray();
    expect($data['disclaimer'])->toContain('Clinical decision support');
});
