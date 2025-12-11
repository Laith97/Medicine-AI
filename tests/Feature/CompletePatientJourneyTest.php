<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\PatientAnalysis;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\DrugInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompletePatientJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected $patient;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create patient user
        $this->patient = User::factory()->create([
            'role' => 'patient',
            'email' => 'patient@example.com'
        ]);

        // Create doctor user with profile
        $doctorUser = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@example.com'
        ]);

        $doctorProfile = new Doctor();
        $doctorProfile->user_id = $doctorUser->id;
        $doctorProfile->specialization = 'General Medicine';
        $doctorProfile->license_number = 'DOC123456';
        $doctorProfile->save();

        $this->doctor = $doctorUser;
        $this->doctor->doctor = $doctorProfile;
    }

    public function test_complete_patient_registration_to_treatment_journey()
    {
        // Step 1: Patient registers and completes profile
        $this->actingAs($this->patient);

        $profileData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1985-05-15',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, State',
            'emergency_contact' => 'Jane Doe',
            'emergency_phone' => '+0987654321',
            'medical_history' => 'No known allergies',
            'current_medications' => 'None'
        ];

        $response = $this->post('/api/patient/profile', $profileData);
        $response->assertStatus(200);

        // Step 2: Patient submits initial health assessment
        $healthData = [
            'symptoms' => ['fever', 'cough', 'fatigue'],
            'severity' => 'moderate',
            'duration' => '3 days',
            'pain_level' => 6,
            'additional_notes' => 'Started after weekend trip'
        ];

        $response = $this->post('/api/patient/health-assessment', $healthData);
        $response->assertStatus(201);

        $assessment = PatientAnalysis::where('user_id', $this->patient->id)->latest()->first();
        $this->assertNotNull($assessment);
        $this->assertEquals('pending_review', $assessment->status);

        // Step 3: Patient searches for and books appointment with doctor
        $response = $this->get('/api/doctors/search?specialization=General+Medicine&location=nearby');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $this->doctor->doctor->id]);

        $appointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDays(1)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'appointment_type' => 'initial_consultation',
            'duration' => 30,
            'reason' => 'Fever and cough assessment',
            'urgency' => 'moderate'
        ];

        Event::fake();
        $response = $this->post('/api/appointments', $appointmentData);
        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();
        $this->assertEquals($this->patient->id, $appointment->patient_id);
        $this->assertEquals('pending', $appointment->status);

        // Step 4: Doctor reviews appointment and confirms
        $this->actingAs($this->doctor);

        $response = $this->get('/api/appointments/pending');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $appointment->id]);

        $response = $this->patch("/api/appointments/{$appointment->id}/confirm");
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);

        // Step 5: Doctor reviews patient assessment and creates diagnosis
        $response = $this->get("/api/patients/{$this->patient->id}/assessment");
        $response->assertStatus(200);

        $diagnosisData = [
            'patient_id' => $this->patient->id,
            'assessment_id' => $assessment->id,
            'condition' => 'Upper Respiratory Infection',
            'icd_code' => 'J06.9',
            'severity' => 'mild',
            'confidence_level' => 85,
            'symptoms' => ['fever', 'cough', 'fatigue'],
            'differential_diagnosis' => ['Common Cold', 'Influenza'],
            'recommendations' => 'Rest, hydration, symptomatic treatment',
            'follow_up_required' => true,
            'follow_up_days' => 7
        ];

        $response = $this->post('/api/diagnoses', $diagnosisData);
        $response->assertStatus(201);

        $diagnosis = Diagnosis::latest()->first();
        $this->assertEquals($this->patient->id, $diagnosis->patient_id);
        $this->assertEquals('Upper Respiratory Infection', $diagnosis->condition);

        // Step 6: Doctor creates prescription with drug interaction checking
        $prescriptionData = [
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $diagnosis->id,
            'medications' => [
                [
                    'drug_name' => 'Amoxicillin',
                    'dosage' => '500mg',
                    'frequency' => 'three_times_daily',
                    'duration' => 7,
                    'instructions' => 'Take with food'
                ],
                [
                    'drug_name' => 'Ibuprofen',
                    'dosage' => '400mg',
                    'frequency' => 'as_needed',
                    'duration' => 5,
                    'instructions' => 'For fever and pain'
                ]
            ],
            'notes' => 'Complete full course of antibiotics'
        ];

        $response = $this->post('/api/prescriptions', $prescriptionData);
        $response->assertStatus(201);

        $prescription = Prescription::latest()->first();
        $this->assertEquals($this->patient->id, $prescription->patient_id);
        $this->assertCount(2, $prescription->medications);

        // Step 7: System checks for drug interactions
        $response = $this->get("/api/prescriptions/{$prescription->id}/interactions");
        $response->assertStatus(200);

        // Verify no critical interactions (Amoxicillin and Ibuprofen are generally safe together)
        $interactions = $response->json();
        $this->assertArrayHasKey('interactions', $interactions);
        // Should not have critical interactions

        // Step 8: Patient receives prescription and appointment confirmation
        $this->actingAs($this->patient);

        $response = $this->get('/api/patient/appointments/upcoming');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $appointment->id, 'status' => 'confirmed']);

        $response = $this->get('/api/patient/prescriptions');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $prescription->id]);

        // Step 9: Appointment day - doctor completes consultation
        $this->actingAs($this->doctor);

        $consultationNotes = [
            'vital_signs' => [
                'temperature' => 100.5,
                'blood_pressure' => '120/80',
                'heart_rate' => 85,
                'respiratory_rate' => 16
            ],
            'physical_exam' => 'Clear lungs, mild pharyngitis noted',
            'assessment' => 'Viral upper respiratory infection',
            'plan' => 'Prescribed antibiotics and supportive care',
            'patient_education' => 'Rest, fluids, complete antibiotic course'
        ];

        $response = $this->patch("/api/appointments/{$appointment->id}/complete", [
            'notes' => $consultationNotes,
            'outcome' => 'completed'
        ]);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);

        // Step 10: Follow-up appointment is scheduled automatically
        $followUpAppointment = Appointment::where('patient_id', $this->patient->id)
            ->where('appointment_type', 'follow_up')
            ->latest()
            ->first();

        $this->assertNotNull($followUpAppointment);
        $this->assertEquals('scheduled', $followUpAppointment->status);

        // Step 11: Patient receives follow-up reminders and completes treatment
        $this->actingAs($this->patient);

        // Simulate treatment completion
        $response = $this->patch("/api/prescriptions/{$prescription->id}/complete");
        $response->assertStatus(200);

        $prescription->refresh();
        $this->assertEquals('completed', $prescription->status);

        // Step 12: Patient provides feedback on care received
        $feedbackData = [
            'appointment_id' => $appointment->id,
            'rating' => 5,
            'feedback' => 'Excellent care, doctor was thorough and caring',
            'would_recommend' => true
        ];

        $response = $this->post('/api/patient/feedback', $feedbackData);
        $response->assertStatus(201);

        // Verify complete journey data integrity
        $this->assertDatabaseHas('users', ['id' => $this->patient->id, 'role' => 'patient']);
        $this->assertDatabaseHas('appointments', ['patient_id' => $this->patient->id, 'status' => 'completed']);
        $this->assertDatabaseHas('diagnoses', ['patient_id' => $this->patient->id]);
        $this->assertDatabaseHas('prescriptions', ['patient_id' => $this->patient->id, 'status' => 'completed']);
    }

    public function test_patient_journey_with_insurance_and_billing()
    {
        // Patient with insurance goes through complete journey with billing
        $this->actingAs($this->patient);

        // Set up insurance information
        $insuranceData = [
            'provider' => 'Blue Cross Blue Shield',
            'policy_number' => 'BCBS123456789',
            'group_number' => 'GRP001',
            'primary_insured' => 'John Doe',
            'relationship' => 'self'
        ];

        $response = $this->post('/api/patient/insurance', $insuranceData);
        $response->assertStatus(201);

        // Book appointment and complete journey (similar to above)
        $appointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'insurance_eligible' => true
        ];

        $response = $this->post('/api/appointments', $appointmentData);
        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();

        // Doctor confirms and processes
        $this->actingAs($this->doctor);

        $response = $this->patch("/api/appointments/{$appointment->id}/confirm");
        $response->assertStatus(200);

        // Create diagnosis and prescription
        $diagnosisData = [
            'patient_id' => $this->patient->id,
            'condition' => 'Hypertension',
            'icd_code' => 'I10',
            'severity' => 'moderate'
        ];

        $response = $this->post('/api/diagnoses', $diagnosisData);
        $response->assertStatus(201);

        $diagnosis = Diagnosis::latest()->first();

        $prescriptionData = [
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $diagnosis->id,
            'medications' => [
                [
                    'drug_name' => 'Lisinopril',
                    'dosage' => '10mg',
                    'frequency' => 'once_daily',
                    'duration' => 30
                ]
            ]
        ];

        $response = $this->post('/api/prescriptions', $prescriptionData);
        $response->assertStatus(201);

        $prescription = Prescription::latest()->first();

        // Complete appointment
        $response = $this->patch("/api/appointments/{$appointment->id}/complete");
        $response->assertStatus(200);

        // Verify billing was triggered
        $this->assertDatabaseHas('claims', ['appointment_id' => $appointment->id]);

        // Patient receives bill/explanation of benefits
        $this->actingAs($this->patient);

        $response = $this->get('/api/patient/billing');
        $response->assertStatus(200);

        $billingData = $response->json();
        $this->assertArrayHasKey('claims', $billingData);
        $this->assertArrayHasKey('insurance_coverage', $billingData);
    }

    public function test_complex_multi_condition_patient_journey()
    {
        // Patient with multiple conditions requiring coordinated care
        $this->actingAs($this->patient);

        // Initial assessment with multiple symptoms
        $complexHealthData = [
            'symptoms' => ['chest_pain', 'shortness_of_breath', 'fatigue', 'dizziness'],
            'severity' => 'high',
            'duration' => '2 weeks',
            'pain_level' => 8,
            'additional_notes' => 'Worsening over time, affects daily activities'
        ];

        $response = $this->post('/api/patient/health-assessment', $complexHealthData);
        $response->assertStatus(201);

        // Book urgent appointment
        $urgentAppointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addHours(4)->format('Y-m-d H:i:s'),
            'appointment_type' => 'urgent_care',
            'duration' => 45,
            'reason' => 'Chest pain and breathing difficulties',
            'urgency' => 'high'
        ];

        $response = $this->post('/api/appointments', $urgentAppointmentData);
        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();

        // Doctor reviews and requests additional tests
        $this->actingAs($this->doctor);

        $response = $this->patch("/api/appointments/{$appointment->id}/confirm");
        $response->assertStatus(200);

        // Order diagnostic tests
        $testOrders = [
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'tests' => [
                ['type' => 'ecg', 'priority' => 'urgent'],
                ['type' => 'chest_xray', 'priority' => 'urgent'],
                ['type' => 'blood_work', 'priority' => 'routine']
            ]
        ];

        $response = $this->post('/api/diagnostic-orders', $testOrders);
        $response->assertStatus(201);

        // Create multiple diagnoses
        $diagnoses = [
            [
                'patient_id' => $this->patient->id,
                'condition' => 'Coronary Artery Disease',
                'icd_code' => 'I25.10',
                'severity' => 'moderate',
                'confidence_level' => 75
            ],
            [
                'patient_id' => $this->patient->id,
                'condition' => 'Hypertension',
                'icd_code' => 'I10',
                'severity' => 'moderate',
                'confidence_level' => 90
            ]
        ];

        foreach ($diagnoses as $diagnosisData) {
            $response = $this->post('/api/diagnoses', $diagnosisData);
            $response->assertStatus(201);
        }

        // Create complex prescription regimen
        $complexPrescriptionData = [
            'patient_id' => $this->patient->id,
            'diagnosis_id' => Diagnosis::latest()->first()->id,
            'medications' => [
                [
                    'drug_name' => 'Aspirin',
                    'dosage' => '81mg',
                    'frequency' => 'once_daily',
                    'duration' => 365,
                    'instructions' => 'Take daily for heart protection'
                ],
                [
                    'drug_name' => 'Metoprolol',
                    'dosage' => '25mg',
                    'frequency' => 'twice_daily',
                    'duration' => 90,
                    'instructions' => 'Take with food'
                ],
                [
                    'drug_name' => 'Lisinopril',
                    'dosage' => '10mg',
                    'frequency' => 'once_daily',
                    'duration' => 90,
                    'instructions' => 'Take in morning'
                ]
            ],
            'notes' => 'Multiple medications for cardiovascular health. Monitor blood pressure weekly.'
        ];

        $response = $this->post('/api/prescriptions', $complexPrescriptionData);
        $response->assertStatus(201);

        $prescription = Prescription::latest()->first();

        // Check for drug interactions in complex regimen
        $response = $this->get("/api/prescriptions/{$prescription->id}/interactions");
        $response->assertStatus(200);

        $interactions = $response->json();
        // Verify interactions are properly flagged and managed

        // Schedule follow-up appointments and referrals
        $followUpData = [
            'patient_id' => $this->patient->id,
            'appointment_type' => 'cardiology_consultation',
            'reason' => 'Specialist evaluation for CAD',
            'urgency' => 'high',
            'referring_doctor_id' => $this->doctor->doctor->id
        ];

        $response = $this->post('/api/appointments/referral', $followUpData);
        $response->assertStatus(201);

        // Complete initial appointment
        $response = $this->patch("/api/appointments/{$appointment->id}/complete", [
            'outcome' => 'referred',
            'referrals' => ['cardiology'],
            'follow_up_required' => true
        ]);
        $response->assertStatus(200);

        // Verify comprehensive care coordination
        $this->actingAs($this->patient);

        $response = $this->get('/api/patient/care-plan');
        $response->assertStatus(200);

        $carePlan = $response->json();
        $this->assertArrayHasKey('active_diagnoses', $carePlan);
        $this->assertArrayHasKey('current_medications', $carePlan);
        $this->assertArrayHasKey('upcoming_appointments', $carePlan);
        $this->assertArrayHasKey('pending_tests', $carePlan);
    }
}
