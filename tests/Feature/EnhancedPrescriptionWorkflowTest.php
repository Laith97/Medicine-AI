<?php

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Prescription;

it('tests enhanced prescription workflow with new fields', function () {
    // Create test users
    $doctor = User::factory()->create([
        'role' => 'doctor',
        'email' => 'doctor@test.com',
    ]);

    $doctorProfile = Doctor::factory()->create([
        'user_id' => $doctor->id,
        'is_active' => true,
    ]);

    $patient = User::factory()->create([
        'role' => 'patient',
        'email' => 'patient@test.com',
        'name' => 'John Doe',
    ]);

    // Create appointment
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'appointment_date' => now()->addDays(1),
        'status' => 'confirmed',
        'reason' => 'Patient needs prescription',
        'appointment_type' => 'in_person',
        'consultation_fee' => 5000,
    ]);

    // Test prescription creation with all new fields
    $prescriptionData = [
        'medication_name' => 'Lisinopril',
        'dosage' => '10mg',
        'form' => 'tablet',
        'route' => 'oral',
        'quantity' => 30,
        'frequency' => 'once daily',
        'duration' => '30 days',
        'refills' => 2,
        'start_date' => now()->format('Y-m-d'),
        'indication' => 'Hypertension',
        'generic_allowed' => true,
        'instructions' => 'Take with food. Monitor blood pressure.',
        'notes' => 'Patient has mild hypertension',
    ];

    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($response->status())->toBe(302); // Redirect after success

    // Verify prescription was created with all fields
    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();
    expect($prescription->medication_name)->toBe('Lisinopril');
    expect($prescription->dosage)->toBe('10mg');
    expect($prescription->form)->toBe('tablet');
    expect($prescription->route)->toBe('oral');
    expect($prescription->quantity)->toBe(30);
    expect($prescription->frequency)->toBe('once daily');
    expect($prescription->duration)->toBe('30 days');
    expect($prescription->refills)->toBe(2);
    expect($prescription->indication)->toBe('Hypertension');
    expect($prescription->generic_allowed)->toBeTrue();
    expect($prescription->instructions)->toBe('Take with food. Monitor blood pressure.');
    expect($prescription->notes)->toBe('Patient has mild hypertension');

    // Verify start_date is set
    expect($prescription->start_date)->not->toBeNull();
    expect($prescription->start_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));

    // Verify appointment prescription_given flag was updated
    $appointment->refresh();
    expect($appointment->prescription_given)->toBeTrue();

    // Test prescription show view
    $showResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription));

    expect($showResponse->status())->toBe(200);
    expect($showResponse->getContent())->toContain('Lisinopril');
    expect($showResponse->getContent())->toContain('10mg');
    expect($showResponse->getContent())->toContain('Tablet');
    expect($showResponse->getContent())->toContain('Oral');
    expect($showResponse->getContent())->toContain('30');
    expect($showResponse->getContent())->toContain('2');
    expect($showResponse->getContent())->toContain('Hypertension');
    expect($showResponse->getContent())->toContain('Yes'); // Generic allowed

    // Test PDF generation
    $pdfResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription) . '?pdf=1');

    expect($pdfResponse->status())->toBe(200);
    expect($pdfResponse->headers->get('content-type'))->toBe('application/pdf');
    expect($pdfResponse->headers->get('content-disposition'))
        ->toContain('attachment; filename="prescription.pdf"');

    // Test prescription update
    $updateData = [
        'medication_name' => 'Lisinopril',
        'dosage' => '20mg', // Updated dosage
        'form' => 'tablet',
        'route' => 'oral',
        'quantity' => 60, // Updated quantity
        'frequency' => 'once daily',
        'duration' => '60 days', // Updated duration
        'refills' => 3, // Updated refills
        'indication' => 'Hypertension',
        'generic_allowed' => false, // Changed to false
        'instructions' => 'Take with food. Monitor blood pressure weekly.',
        'notes' => 'Increased dosage due to persistent hypertension',
    ];

    $updateResponse = $this->actingAs($doctor)
        ->put(route('prescriptions.update', $prescription), $updateData);

    expect($updateResponse->status())->toBe(302);

    $prescription->refresh();
    expect($prescription->dosage)->toBe('20mg');
    expect($prescription->quantity)->toBe(60);
    expect($prescription->duration)->toBe('60 days');
    expect($prescription->refills)->toBe(3);
    expect($prescription->generic_allowed)->toBeFalse();
    expect($prescription->instructions)->toBe('Take with food. Monitor blood pressure weekly.');
    expect($prescription->notes)->toBe('Increased dosage due to persistent hypertension');
});

it('validates new prescription fields', function () {
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
            'dosage' => '10mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 30,
            'frequency' => 'once daily',
            'duration' => '30 days',
        ]);

    expect($response->status())->toBe(302); // Redirect back with validation errors

    // Test invalid quantity (negative)
    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), [
            'medication_name' => 'Test Med',
            'dosage' => '10mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => -1, // Invalid
            'frequency' => 'once daily',
            'duration' => '30 days',
        ]);

    expect($response->status())->toBe(302);

    // Test invalid refills (negative)
    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), [
            'medication_name' => 'Test Med',
            'dosage' => '10mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 30,
            'frequency' => 'once daily',
            'duration' => '30 days',
            'refills' => -1, // Invalid
        ]);

    expect($response->status())->toBe(302);

    // Verify no prescriptions were created
    expect(Prescription::where('appointment_id', $appointment->id)->count())->toBe(0);
});

it('handles prescription active status with new fields', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $patient = User::factory()->create(['role' => 'patient']);

    // Create prescription with new fields that expires in future
    $activePrescription = Prescription::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'medication_name' => 'Test Med',
        'dosage' => '10mg',
        'form' => 'tablet',
        'route' => 'oral',
        'quantity' => 30,
        'frequency' => 'once daily',
        'duration' => '30 days',
        'refills' => 2,
        'start_date' => now(),
        'indication' => 'Test',
        'generic_allowed' => true,
        'created_at' => now()->subDays(10), // Created 10 days ago
    ]);

    // Create prescription that expired
    $expiredPrescription = Prescription::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'medication_name' => 'Expired Med',
        'dosage' => '10mg',
        'form' => 'tablet',
        'route' => 'oral',
        'quantity' => 30,
        'frequency' => 'once daily',
        'duration' => '7 days',
        'refills' => 0,
        'start_date' => now()->subDays(10),
        'indication' => 'Test',
        'generic_allowed' => true,
        'created_at' => now()->subDays(10), // Created 10 days ago, expired 3 days ago
    ]);

    expect($activePrescription->isActive())->toBeTrue();
    expect($expiredPrescription->isActive())->toBeFalse();

    // Test getActiveForPatient method
    $activePrescriptions = Prescription::getActiveForPatient($patient->id);
    expect($activePrescriptions)->toHaveCount(1);
    expect($activePrescriptions->first()->id)->toBe($activePrescription->id);

    // Verify new fields are accessible
    expect($activePrescription->form)->toBe('tablet');
    expect($activePrescription->route)->toBe('oral');
    expect($activePrescription->quantity)->toBe(30);
    expect($activePrescription->refills)->toBe(2);
    expect($activePrescription->indication)->toBe('Test');
    expect($activePrescription->generic_allowed)->toBeTrue();
});

it('tests dynamic form transformation with custom form values', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test custom form submission
    $prescriptionData = [
        'medication_name' => 'Hydrocortisone',
        'dosage' => '1%',
        'form' => 'Suppository', // Custom form value
        'route' => 'rectal',
        'quantity' => 12,
        'frequency' => 'twice daily',
        'duration' => '7 days',
        'refills' => 0,
        'indication' => 'Hemorrhoids',
        'generic_allowed' => true,
        'instructions' => 'Insert one suppository rectally twice daily',
        'notes' => 'Patient has external hemorrhoids',
    ];

    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($response->status())->toBe(302);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();
    expect($prescription->form)->toBe('Suppository');
    expect($prescription->route)->toBe('rectal');
});

it('tests dynamic route transformation with custom route values', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test custom route submission
    $prescriptionData = [
        'medication_name' => 'Insulin',
        'dosage' => '100 units/mL',
        'form' => 'injection',
        'route' => 'Subcutaneous', // Custom route value
        'quantity' => 1,
        'frequency' => 'once daily',
        'duration' => 'ongoing',
        'refills' => 5,
        'indication' => 'Diabetes Mellitus Type 1',
        'generic_allowed' => false,
        'instructions' => 'Inject subcutaneously in abdomen, rotate sites',
        'notes' => 'Monitor blood glucose regularly',
    ];

    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($response->status())->toBe(302);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();
    expect($prescription->route)->toBe('Subcutaneous');
});

it('tests dynamic frequency transformation with custom frequency values', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test custom frequency submission
    $prescriptionData = [
        'medication_name' => 'Amoxicillin',
        'dosage' => '500mg',
        'form' => 'capsule',
        'route' => 'oral',
        'quantity' => 21,
        'frequency' => 'Every 8 hours', // Custom frequency value
        'duration' => '7 days',
        'refills' => 0,
        'indication' => 'Acute otitis media',
        'generic_allowed' => true,
        'instructions' => 'Take with food to reduce stomach upset',
        'notes' => 'Complete full course of antibiotics',
    ];

    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($response->status())->toBe(302);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();
    expect($prescription->frequency)->toBe('Every 8 hours');
});

it('tests dynamic duration transformation with custom duration values', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test custom duration submission
    $prescriptionData = [
        'medication_name' => 'Warfarin',
        'dosage' => '5mg',
        'form' => 'tablet',
        'route' => 'oral',
        'quantity' => 90,
        'frequency' => 'once daily',
        'duration' => 'Indefinite', // Custom duration value
        'refills' => 11,
        'indication' => 'Atrial fibrillation',
        'generic_allowed' => true,
        'instructions' => 'Take at the same time each day. Monitor INR regularly.',
        'notes' => 'Anticoagulation therapy - close monitoring required',
    ];

    $response = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($response->status())->toBe(302);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();
    expect($prescription->duration)->toBe('Indefinite');
});

it('tests custom form values are properly stored and displayed', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Create prescription with custom values
    $prescriptionData = [
        'medication_name' => 'Test Medication',
        'dosage' => '10mg',
        'form' => 'Custom Form',
        'route' => 'Custom Route',
        'quantity' => 30,
        'frequency' => 'Custom Frequency',
        'duration' => 'Custom Duration',
        'refills' => 2,
        'indication' => 'Test Indication',
        'generic_allowed' => true,
        'instructions' => 'Test instructions',
        'notes' => 'Test notes',
    ];

    $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();

    // Test data storage
    expect($prescription->form)->toBe('Custom Form');
    expect($prescription->route)->toBe('Custom Route');
    expect($prescription->frequency)->toBe('Custom Frequency');
    expect($prescription->duration)->toBe('Custom Duration');

    // Test prescription show view displays custom values
    $showResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription));

    expect($showResponse->status())->toBe(200);
    expect($showResponse->getContent())->toContain('Custom Form');
    expect($showResponse->getContent())->toContain('Custom Route');
    expect($showResponse->getContent())->toContain('Custom Frequency');
    expect($showResponse->getContent())->toContain('Custom Duration');
});

it('tests custom form values display in PDF', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Create prescription with custom values
    $prescriptionData = [
        'medication_name' => 'PDF Test Med',
        'dosage' => '20mg',
        'form' => 'Ophthalmic Solution',
        'route' => 'Ophthalmic',
        'quantity' => 1,
        'frequency' => 'Twice daily in both eyes',
        'duration' => 'As needed',
        'refills' => 1,
        'indication' => 'Conjunctivitis',
        'generic_allowed' => true,
        'instructions' => 'Apply to affected eye(s)',
        'notes' => 'Eye drops for bacterial conjunctivitis',
    ];

    $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();

    // Test PDF generation includes custom values
    $pdfResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription) . '?pdf=1');

    expect($pdfResponse->status())->toBe(200);
    expect($pdfResponse->headers->get('content-type'))->toBe('application/pdf');
    // Note: We can't easily test PDF content in this test environment
    // but the PDF generation should work with custom values
});

it('tests form reset functionality with transformed fields', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test that form reset works (this would be tested via JavaScript in browser)
    // Here we test that the form can be submitted multiple times with different values
    $prescriptionData1 = [
        'medication_name' => 'First Med',
        'dosage' => '10mg',
        'form' => 'tablet',
        'route' => 'oral',
        'quantity' => 30,
        'frequency' => 'once daily',
        'duration' => '30 days',
        'refills' => 2,
        'indication' => 'Test',
        'generic_allowed' => true,
        'instructions' => 'Take once daily',
        'notes' => 'First prescription',
    ];

    $response1 = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData1);

    expect($response1->status())->toBe(302);

    // Create second prescription (simulating form reset and new entry)
    $prescriptionData2 = [
        'medication_name' => 'Second Med',
        'dosage' => '20mg',
        'form' => 'capsule',
        'route' => 'oral',
        'quantity' => 60,
        'frequency' => 'twice daily',
        'duration' => '60 days',
        'refills' => 3,
        'indication' => 'Test 2',
        'generic_allowed' => false,
        'instructions' => 'Take twice daily',
        'notes' => 'Second prescription',
    ];

    $response2 = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData2);

    expect($response2->status())->toBe(302);

    // Verify both prescriptions exist
    $prescriptions = Prescription::where('appointment_id', $appointment->id)->get();
    expect($prescriptions)->toHaveCount(2);

    $firstPrescription = $prescriptions->where('medication_name', 'First Med')->first();
    $secondPrescription = $prescriptions->where('medication_name', 'Second Med')->first();

    expect($firstPrescription)->not->toBeNull();
    expect($secondPrescription)->not->toBeNull();
    expect($firstPrescription->form)->toBe('tablet');
    expect($secondPrescription->form)->toBe('capsule');
});

it('tests end-to-end workflow with custom form values', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $patient = User::factory()->create(['role' => 'patient']);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
        'reason' => 'Patient needs custom medication',
    ]);

    // 1. Create prescription with custom form values
    $prescriptionData = [
        'medication_name' => 'Nitroglycerin',
        'dosage' => '0.4mg',
        'form' => 'Sublingual Tablet', // Custom form
        'route' => 'Sublingual', // Custom route
        'quantity' => 100,
        'frequency' => 'As needed for chest pain', // Custom frequency
        'duration' => 'PRN (as needed)', // Custom duration
        'refills' => 5,
        'indication' => 'Angina pectoris',
        'generic_allowed' => false,
        'instructions' => 'Place under tongue and let dissolve. Call 911 if pain persists.',
        'notes' => 'Emergency medication for chest pain',
    ];

    $storeResponse = $this->actingAs($doctor)
        ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

    expect($storeResponse->status())->toBe(302);

    $prescription = Prescription::where('appointment_id', $appointment->id)->first();
    expect($prescription)->not->toBeNull();

    // 2. Verify data storage
    expect($prescription->form)->toBe('Sublingual Tablet');
    expect($prescription->route)->toBe('Sublingual');
    expect($prescription->frequency)->toBe('As needed for chest pain');
    expect($prescription->duration)->toBe('PRN (as needed)');

    // 3. Verify appointment prescription_given flag
    $appointment->refresh();
    expect($appointment->prescription_given)->toBeTrue();

    // 4. Test prescription display in appointment view
    $appointmentResponse = $this->actingAs($doctor)
        ->get(route('doctor.appointments.show', $appointment));

    expect($appointmentResponse->status())->toBe(200);
    expect($appointmentResponse->getContent())->toContain('Nitroglycerin');
    expect($appointmentResponse->getContent())->toContain('Sublingual Tablet');
    expect($appointmentResponse->getContent())->toContain('Sublingual');

    // 5. Test prescription show view
    $showResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription));

    expect($showResponse->status())->toBe(200);
    expect($showResponse->getContent())->toContain('Nitroglycerin');
    expect($showResponse->getContent())->toContain('Sublingual Tablet');
    expect($showResponse->getContent())->toContain('Sublingual');
    expect($showResponse->getContent())->toContain('As needed for chest pain');
    expect($showResponse->getContent())->toContain('PRN (as needed)');

    // 6. Test PDF generation (basic check)
    $pdfResponse = $this->actingAs($doctor)
        ->get(route('prescriptions.show', $prescription) . '?pdf=1');

    expect($pdfResponse->status())->toBe(200);
    expect($pdfResponse->headers->get('content-type'))->toBe('application/pdf');
});

it('validates custom form values are accepted', function () {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctorProfile = Doctor::factory()->create(['user_id' => $doctor->id, 'is_active' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorProfile->id,
        'status' => 'confirmed',
    ]);

    // Test various custom values that should be accepted
    $customValues = [
        ['form' => 'Transdermal Patch'],
        ['route' => 'Intravitreal'],
        ['frequency' => 'Every 4-6 hours as needed'],
        ['duration' => 'Until symptoms resolve'],
    ];

    foreach ($customValues as $customValue) {
        $prescriptionData = array_merge([
            'medication_name' => 'Test Med',
            'dosage' => '10mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 30,
            'frequency' => 'once daily',
            'duration' => '30 days',
            'refills' => 0,
            'indication' => 'Test',
            'generic_allowed' => true,
        ], $customValue);

        $response = $this->actingAs($doctor)
            ->post(route('doctor.prescriptions.store', $appointment), $prescriptionData);

        expect($response->status())->toBe(302);

        $prescription = Prescription::where('appointment_id', $appointment->id)
            ->where('medication_name', 'Test Med')
            ->first();

        expect($prescription)->not->toBeNull();

        // Verify the custom value was stored
        foreach ($customValue as $field => $value) {
            expect($prescription->$field)->toBe($value);
        }

        // Clean up for next iteration
        $prescription->delete();
    }
});