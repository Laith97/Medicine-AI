<?php

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

// Skip database tests if SQLite driver is not available
$canRunDatabaseTests = class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers());

if ($canRunDatabaseTests) {
    uses(RefreshDatabase::class);
}

it('completes the full AI-enhanced e-prescribing workflow', function () {
    // Skip test if database is not available
    if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
        $this->markTestSkipped('SQLite driver not available for testing');
    }

    // Setup test data
    $doctor = User::factory()->create([
        'role' => 'doctor',
        'email' => 'doctor@test.com',
    ]);

    $doctorProfile = Doctor::factory()->create([
        'user_id' => $doctor->id,
        'specialty' => 'Internal Medicine',
        'is_active' => true,
    ]);

    $patient = User::factory()->create([
        'role' => 'patient',
        'email' => 'patient@test.com',
        'name' => 'John Doe',
        'age' => 35,
        'gender' => 'male',
    ]);

    // Create patient data with medical history
    $patientData = PatientData::factory()->create([
        'assigned_patient_id' => $patient->id,
        'symptoms' => ['fever', 'headache', 'cough'],
        'allergies' => ['penicillin', 'sulfa drugs'],
        'past_medications' => ['ibuprofen', 'amoxicillin'],
        'past_medical_history' => 'Hypertension, Diabetes Type 2',
        'chief_complaint' => 'Severe headache and fever',
        'symptom_duration' => '3 days',
    ]);

    // Create appointment
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'appointment_date' => now()->addDays(1),
        'status' => 'confirmed',
        'reason' => 'Patient complaining of severe headache and fever for 3 days',
        'symptoms' => json_encode(['fever', 'headache', 'cough']),
        'appointment_type' => 'in_person',
        'consultation_fee' => 5000, // $50.00
    ]);

    // Step 1: Doctor views appointment page
    $response = $this->actingAs($doctor)
        ->get(route('doctor.appointments.show', $appointment));

    expect($response->status())->toBe(200);
    expect($response->getContent())->toContain('Appointment Details');
    expect($response->getContent())->toContain('Add New Prescription');

    // Step 2: Test AI suggestion generation
    // Mock OpenAI response for consistent testing
    OpenAI::shouldReceive('chat->create')
        ->once()
        ->andReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'suggestions' => [
                                [
                                    'med' => 'Acetaminophen',
                                    'dosage' => '500mg',
                                    'freq' => 'every 6 hours as needed',
                                    'dur' => '7 days',
                                    'confidence' => 90,
                                    'reason' => 'Effective for fever and headache relief'
                                ],
                                [
                                    'med' => 'Ibuprofen',
                                    'dosage' => '400mg',
                                    'freq' => 'every 8 hours as needed',
                                    'dur' => '5 days',
                                    'confidence' => 85,
                                    'reason' => 'Anti-inflammatory for headache and fever'
                                ]
                            ],
                            'risk_flags' => [
                                'Patient has penicillin allergy - avoid beta-lactam antibiotics',
                                'Monitor for gastrointestinal side effects with NSAIDs',
                                'Patient has hypertension - monitor blood pressure'
                            ]
                        ])
                    ]
                ]
            ],
            'usage' => [
                'prompt_tokens' => 150,
                'completion_tokens' => 200,
                'total_tokens' => 350
            ]
        ]);

    // Test AI suggestion endpoint
    $aiResponse = $this->actingAs($doctor)
        ->post(route('ai.appointments.suggest', $appointment), [
            'symptoms' => json_encode(['fever', 'headache', 'cough']),
            'allergies' => json_encode(['penicillin', 'sulfa drugs']),
            'past_meds' => json_encode(['ibuprofen', 'amoxicillin']),
        ]);

    expect($aiResponse->status())->toBe(200);

    $aiData = $aiResponse->json();
    expect($aiData)->toHaveKey('suggestions');
    expect($aiData)->toHaveKey('risk_flags');
    expect($aiData['suggestions'])->toHaveCount(2);
    expect($aiData['risk_flags'])->toHaveCount(3);

    // Verify AI suggestions structure
    $firstSuggestion = $aiData['suggestions'][0];
    expect($firstSuggestion)->toHaveKeys(['med', 'dosage', 'freq', 'dur', 'confidence', 'reason']);
    expect($firstSuggestion['med'])->toBe('Acetaminophen');

    // Step 3: Test prescription creation with AI suggestions
    $prescriptionData = [
        'medication_name' => 'Acetaminophen',
        'dosage' => '500mg',
        'frequency' => 'every 6 hours as needed',
        'duration' => '7 days',
        'notes' => 'For fever and headache relief. Monitor for side effects.',
        'ai_suggestions' => json_encode($aiData['suggestions']),
        'ai_risk_flags' => json_encode($aiData['risk_flags']),
    ];

    $prescriptionResponse = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($prescriptionResponse->status())->toBe(302); // Redirect after success

    // Verify prescription was created in database
    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();
    expect($prescription->medication_name)->toBe('Acetaminophen');
    expect($prescription->dosage)->toBe('500mg');
    expect($prescription->doctor_id)->toBe($doctor->id);
    expect($prescription->patient_id)->toBe($patient->id);
    expect($prescription->ai_suggestions)->toBeArray();
    expect($prescription->ai_risk_flags)->toBeArray();

    // Verify appointment prescription_given flag was updated
    $appointment->refresh();
    expect($appointment->prescription_given)->toBeTrue();

    // Step 4: Test prescription display
    $showResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription));

    expect($showResponse->status())->toBe(200);
    expect($showResponse->getContent())->toContain('Acetaminophen');
    expect($showResponse->getContent())->toContain('500mg');

    // Step 5: Test PDF generation and download
    Storage::fake('local'); // Use fake storage for testing

    $pdfResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription) . '?pdf=1');

    expect($pdfResponse->status())->toBe(200);
    expect($pdfResponse->headers->get('content-type'))->toBe('application/pdf');
    expect($pdfResponse->headers->get('content-disposition'))
        ->toContain('attachment; filename="prescription.pdf"');

    // Step 6: Test prescription update
    $updateData = [
        'medication_name' => 'Acetaminophen',
        'dosage' => '650mg', // Updated dosage
        'frequency' => 'every 6 hours as needed',
        'duration' => '7 days',
        'notes' => 'Updated dosage for better fever control.',
    ];

    $updateResponse = $this->actingAs($doctor)
        ->put(route('prescriptions.update', $prescription), $updateData);

    expect($updateResponse->status())->toBe(302);

    $prescription->refresh();
    expect($prescription->dosage)->toBe('650mg');
    expect($prescription->notes)->toBe('Updated dosage for better fever control.');

    // Step 7: Test prescription deletion
    $deleteResponse = $this->actingAs($doctor)
        ->delete(route('prescriptions.destroy', $prescription));

    expect($deleteResponse->status())->toBe(200);

    // Verify prescription was deleted
    expect(Prescription::find($prescription->id))->toBeNull();

    // Verify appointment prescription_given flag was reset
    $appointment->refresh();
    expect($appointment->prescription_given)->toBeFalse();
});

it('handles AI suggestion failures gracefully', function () {
    // Skip test if database is not available
    if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
        $this->markTestSkipped('SQLite driver not available for testing');
    }

    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $patient = User::factory()->create(['role' => 'patient']);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
    ]);

    // Mock OpenAI failure
    OpenAI::shouldReceive('chat->create')
        ->once()
        ->andThrow(new Exception('OpenAI API Error'));

    $response = $this->actingAs($doctor)
        ->post(route('ai.appointments.suggest', $appointment), [
            'symptoms' => json_encode(['fever']),
            'allergies' => json_encode([]),
            'past_meds' => json_encode([]),
        ]);

    expect($response->status())->toBe(200);

    $data = $response->json();
    expect($data)->toHaveKey('suggestions');
    expect($data)->toHaveKey('risk_flags');
    expect($data)->toHaveKey('fallback');
    expect($data['fallback'])->toBeTrue();
});

it('validates prescription form data', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test missing required fields
    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), [
            'medication_name' => '', // Required field empty
            'dosage' => '500mg',
            'frequency' => 'twice daily',
            'duration' => '7 days',
        ]);

    expect($response->status())->toBe(302); // Redirect back with validation errors

    // Verify no prescription was created
    expect(Prescription::where('appointment_id', $appointment->id)->count())->toBe(0);
});

it('prevents unauthorized prescription access', function () {
    $doctor1 = User::factory()->create(['role' => 'doctor']);
    $doctor2 = User::factory()->create(['role' => 'doctor']);
    $patient = User::factory()->create(['role' => 'patient']);

    $doctorProfile1 = Doctor::factory()->create(['user_id' => $doctor1->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile1->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
    ]);

    $prescription = Prescription::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor1->id,
        'patient_id' => $patient->id,
    ]);

    // Doctor 2 tries to access prescription
    $response = $this->actingAs($doctor2)
        ->get(route('prescriptions.show', $prescription));

    expect($response->status())->toBe(403);

    // Doctor 2 tries to update prescription
    $response = $this->actingAs($doctor2)
        ->put(route('prescriptions.update', $prescription), [
            'medication_name' => 'Test Med',
            'dosage' => '100mg',
            'frequency' => 'daily',
            'duration' => '7 days',
        ]);

    expect($response->status())->toBe(403);

    // Doctor 2 tries to delete prescription
    $response = $this->actingAs($doctor2)
        ->delete(route('prescriptions.destroy', $prescription));

    expect($response->status())->toBe(403);
});

it('handles prescription PDF generation for complex prescriptions', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $patient = User::factory()->create(['role' => 'patient']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
    ]);

    $prescription = Prescription::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'medication_name' => 'Complex Medication Name',
        'dosage' => '500mg/5mL',
        'frequency' => 'every 8 hours for 7 days, then every 12 hours',
        'duration' => '14 days',
        'notes' => 'Take with food. Monitor for side effects including nausea, dizziness, and allergic reactions. Contact physician if symptoms worsen.',
        'ai_suggestions' => [
            [
                'med' => 'Complex Medication Name',
                'dosage' => '500mg/5mL',
                'freq' => 'every 8 hours for 7 days, then every 12 hours',
                'dur' => '14 days',
                'confidence' => 88,
                'reason' => 'Based on patient symptoms and medical history'
            ]
        ],
        'ai_risk_flags' => [
            'Monitor for gastrointestinal side effects',
            'Check for drug interactions with current medications',
            'Patient has allergy history - watch for reactions'
        ],
    ]);

    $response = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription) . '?pdf=1');

    expect($response->status())->toBe(200);
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

it('tests prescription active status calculation', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $patient = User::factory()->create(['role' => 'patient']);

    // Create prescription that expires in future
    $activePrescription = Prescription::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'duration' => '30 days',
        'created_at' => now()->subDays(10), // Created 10 days ago
    ]);

    // Create prescription that expired
    $expiredPrescription = Prescription::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'duration' => '7 days',
        'created_at' => now()->subDays(10), // Created 10 days ago, expired 3 days ago
    ]);

    expect($activePrescription->isActive())->toBeTrue();
    expect($expiredPrescription->isActive())->toBeFalse();

    // Test getActiveForPatient method
    $activePrescriptions = Prescription::getActiveForPatient($patient->id);
    expect($activePrescriptions)->toHaveCount(1);
    expect($activePrescriptions->first()->id)->toBe($activePrescription->id);
});

it('handles multiple prescriptions per appointment', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $patient = User::factory()->create(['role' => 'patient']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
    ]);

    // Create multiple prescriptions
    $prescription1 = Prescription::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'medication_name' => 'Medication A',
    ]);

    $prescription2 = Prescription::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'medication_name' => 'Medication B',
    ]);

    // Verify appointment has multiple prescriptions
    $appointment->refresh();
    expect($appointment->prescriptions)->toHaveCount(2);
    expect($appointment->prescription_given)->toBeTrue();

    // Delete one prescription - should not reset prescription_given flag
    $this->actingAs($doctor)->delete(route('prescriptions.destroy', $prescription1));

    $appointment->refresh();
    expect($appointment->prescriptions)->toHaveCount(1);
    expect($appointment->prescription_given)->toBeTrue();

    // Delete last prescription - should reset prescription_given flag
    $this->actingAs($doctor)->delete(route('prescriptions.destroy', $prescription2));

    $appointment->refresh();
    expect($appointment->prescriptions)->toHaveCount(0);
    expect($appointment->prescription_given)->toBeFalse();
});

it('validates AI suggestion response structure', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Mock malformed OpenAI response
    OpenAI::shouldReceive('chat->create')
        ->once()
        ->andReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Invalid JSON response that is not parseable'
                    ]
                ]
            ]
        ]);

    $response = $this->actingAs($doctor)
        ->post(route('ai.appointments.suggest', $appointment), [
            'symptoms' => json_encode(['fever']),
            'allergies' => json_encode([]),
            'past_meds' => json_encode([]),
        ]);

    expect($response->status())->toBe(200);

    $data = $response->json();
    expect($data)->toHaveKey('suggestions');
    expect($data)->toHaveKey('risk_flags');
    expect($data)->toHaveKey('fallback');
    expect($data['fallback'])->toBeTrue();
});

it('tests prescription form reset functionality', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($doctor)
        ->get(route('doctor.appointments.show', $appointment));

    expect($response->status())->toBe(200);
    expect($response->getContent())->toContain('resetPrescriptionForm');
    expect($response->getContent())->toContain('Reset Form');
});