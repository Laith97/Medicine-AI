<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Diagnosis;
use Carbon\Carbon;

class PredictiveAnalyticsTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating predictive analytics test data...');

        // Create a specialty if it doesn't exist
        $specialty = \App\Models\Specialty::firstOrCreate(
            ['name' => 'General Practice'],
            ['description' => 'General medical practice']
        );

        // Create a doctor user
        $doctorUser = User::firstOrCreate(
            ['email' => 'doctor@test.com'],
            [
                'name' => 'Dr. Test Doctor',
                'password' => bcrypt('password'),
                'role' => 'doctor',
                'email_verified_at' => now(),
            ]
        );

        // Create doctor profile
        $doctor = \App\Models\Doctor::firstOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'specialty_id' => $specialty->id,
                'license_number' => 'TEST123456',
                'phone' => '+1234567890',
                'bio' => 'Test doctor for predictive analytics',
                'consultation_fee' => 10000, // $100
                'appointment_duration' => 30,
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ]
        );

        // Create test patients with different risk profiles
        $this->createLowRiskPatients($doctor->id);
        $this->createMediumRiskPatients($doctor->id);
        $this->createHighRiskPatients($doctor->id);

        $this->command->info('Predictive analytics test data created successfully.');
    }

    /**
     * Create low risk patients
     */
    private function createLowRiskPatients(int $doctorId): void
    {
        $lowRiskProfiles = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@test.com',
                'age' => 28,
                'gender' => 'female',
                'diagnoses' => [], // No chronic conditions
                'appointment_history' => [
                    ['date' => now()->subDays(30), 'status' => 'completed'],
                    ['date' => now()->subDays(60), 'status' => 'completed'],
                    ['date' => now()->subDays(90), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(2),
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@test.com',
                'age' => 32,
                'gender' => 'male',
                'diagnoses' => [], // No chronic conditions
                'appointment_history' => [
                    ['date' => now()->subDays(45), 'status' => 'completed'],
                    ['date' => now()->subDays(75), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(3),
            ],
        ];

        foreach ($lowRiskProfiles as $profile) {
            $this->createPatientWithHistory($doctorId, $profile);
        }
    }

    /**
     * Create medium risk patients
     */
    private function createMediumRiskPatients(int $doctorId): void
    {
        $mediumRiskProfiles = [
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@test.com',
                'age' => 45,
                'gender' => 'male',
                'diagnoses' => ['Hypertension'], // One chronic condition
                'appointment_history' => [
                    ['date' => now()->subDays(25), 'status' => 'completed'],
                    ['date' => now()->subDays(80), 'status' => 'no_show'], // One no-show
                    ['date' => now()->subDays(120), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(1),
            ],
            [
                'name' => 'Diana Prince',
                'email' => 'diana@test.com',
                'age' => 38,
                'gender' => 'female',
                'diagnoses' => ['Diabetes'], // One chronic condition
                'appointment_history' => [
                    ['date' => now()->subDays(50), 'status' => 'completed'],
                    ['date' => now()->subDays(100), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(4),
            ],
            [
                'name' => 'George Miller',
                'email' => 'george@test.com',
                'age' => 52,
                'gender' => 'male',
                'diagnoses' => ['Hypertension', 'High Cholesterol'], // Two related conditions
                'appointment_history' => [
                    ['date' => now()->subDays(30), 'status' => 'completed'],
                    ['date' => now()->subDays(65), 'status' => 'no_show'],
                    ['date' => now()->subDays(95), 'status' => 'completed'],
                    ['date' => now()->subDays(140), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(3),
            ],
            [
                'name' => 'Helen Davis',
                'email' => 'helen@test.com',
                'age' => 48,
                'gender' => 'female',
                'diagnoses' => ['Asthma'], // Respiratory condition
                'appointment_history' => [
                    ['date' => now()->subDays(20), 'status' => 'completed'],
                    ['date' => now()->subDays(55), 'status' => 'completed'],
                    ['date' => now()->subDays(90), 'status' => 'no_show'],
                    ['date' => now()->subDays(125), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(2),
            ],
            [
                'name' => 'Ian Foster',
                'email' => 'ian@test.com',
                'age' => 55,
                'gender' => 'male',
                'diagnoses' => ['Diabetes', 'Obesity'], // Metabolic conditions
                'appointment_history' => [
                    ['date' => now()->subDays(35), 'status' => 'no_show'],
                    ['date' => now()->subDays(70), 'status' => 'completed'],
                    ['date' => now()->subDays(105), 'status' => 'completed'],
                    ['date' => now()->subDays(150), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(5),
            ],
        ];

        foreach ($mediumRiskProfiles as $profile) {
            $this->createPatientWithHistory($doctorId, $profile);
        }
    }

    /**
     * Create high risk patients
     */
    private function createHighRiskPatients(int $doctorId): void
    {
        $highRiskProfiles = [
            [
                'name' => 'Edward Norton',
                'email' => 'edward@test.com',
                'age' => 65,
                'gender' => 'male',
                'diagnoses' => ['Diabetes', 'Hypertension', 'Heart Disease'], // Multiple chronic conditions
                'appointment_history' => [
                    ['date' => now()->subDays(15), 'status' => 'no_show'],
                    ['date' => now()->subDays(45), 'status' => 'no_show'],
                    ['date' => now()->subDays(90), 'status' => 'completed'],
                    ['date' => now()->subDays(150), 'status' => 'no_show'],
                    ['date' => now()->subDays(200), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(1),
            ],
            [
                'name' => 'Fiona Green',
                'email' => 'fiona@test.com',
                'age' => 58,
                'gender' => 'female',
                'diagnoses' => ['Cancer', 'Kidney Disease'], // Multiple chronic conditions
                'appointment_history' => [
                    ['date' => now()->subDays(20), 'status' => 'completed'],
                    ['date' => now()->subDays(60), 'status' => 'no_show'],
                    ['date' => now()->subDays(120), 'status' => 'no_show'],
                    ['date' => now()->subDays(180), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(2),
            ],
            [
                'name' => 'Grace Thompson',
                'email' => 'grace@test.com',
                'age' => 72,
                'gender' => 'female',
                'diagnoses' => ['Diabetes', 'Hypertension', 'Heart Disease', 'COPD'], // Multiple severe conditions
                'appointment_history' => [
                    ['date' => now()->subDays(10), 'status' => 'no_show'],
                    ['date' => now()->subDays(35), 'status' => 'no_show'],
                    ['date' => now()->subDays(70), 'status' => 'completed'],
                    ['date' => now()->subDays(110), 'status' => 'no_show'],
                    ['date' => now()->subDays(160), 'status' => 'no_show'],
                    ['date' => now()->subDays(220), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(1),
            ],
            [
                'name' => 'Henry Wilson',
                'email' => 'henry@test.com',
                'age' => 68,
                'gender' => 'male',
                'diagnoses' => ['Cancer', 'Diabetes', 'Kidney Disease', 'Stroke'], // Multiple critical conditions
                'appointment_history' => [
                    ['date' => now()->subDays(25), 'status' => 'no_show'],
                    ['date' => now()->subDays(55), 'status' => 'completed'],
                    ['date' => now()->subDays(95), 'status' => 'no_show'],
                    ['date' => now()->subDays(140), 'status' => 'no_show'],
                    ['date' => now()->subDays(190), 'status' => 'completed'],
                    ['date' => now()->subDays(250), 'status' => 'no_show'],
                ],
                'upcoming_appointment' => now()->addDays(3),
            ],
            [
                'name' => 'Isabella Rodriguez',
                'email' => 'isabella@test.com',
                'age' => 61,
                'gender' => 'female',
                'diagnoses' => ['Hypertension', 'Heart Disease', 'Diabetes', 'Arthritis'], // Multiple chronic conditions
                'appointment_history' => [
                    ['date' => now()->subDays(18), 'status' => 'completed'],
                    ['date' => now()->subDays(48), 'status' => 'no_show'],
                    ['date' => now()->subDays(88), 'status' => 'no_show'],
                    ['date' => now()->subDays(128), 'status' => 'completed'],
                    ['date' => now()->subDays(178), 'status' => 'no_show'],
                    ['date' => now()->subDays(228), 'status' => 'completed'],
                ],
                'upcoming_appointment' => now()->addDays(2),
            ],
        ];

        foreach ($highRiskProfiles as $profile) {
            $this->createPatientWithHistory($doctorId, $profile);
        }
    }

    /**
     * Create a patient with appointment history and diagnoses
     */
    private function createPatientWithHistory(int $doctorId, array $profile): void
    {
        // Create patient
        $patient = User::firstOrCreate(
            ['email' => $profile['email']],
            [
                'name' => $profile['name'],
                'password' => bcrypt('password'),
                'role' => 'patient',
                'age' => $profile['age'],
                'gender' => $profile['gender'],
                'email_verified_at' => now(),
                'date_of_birth' => now()->subYears($profile['age']),
            ]
        );

        // Create diagnoses
        foreach ($profile['diagnoses'] as $diagnosisText) {
            Diagnosis::create([
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
                'diagnosis_text' => $diagnosisText,
                'created_at' => now()->subDays(rand(30, 365)),
            ]);
        }

        // Create historical appointments
        foreach ($profile['appointment_history'] as $appointmentData) {
            $appointmentDate = $appointmentData['date'];
            $duration = 30; // minutes
            $appointmentEnd = $appointmentDate->copy()->addMinutes($duration);

            Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'appointment_date' => $appointmentDate,
                'appointment_end' => $appointmentEnd,
                'status' => $appointmentData['status'],
                'duration' => $duration,
                'fee' => 10000, // $100.00
                'completed_at' => $appointmentData['status'] === 'completed' ? $appointmentDate : null,
                'created_at' => $appointmentDate->copy()->subDays(1),
            ]);
        }

        // Create upcoming appointment
        $upcomingDate = $profile['upcoming_appointment'];
        $duration = 30; // minutes
        $upcomingEnd = $upcomingDate->copy()->addMinutes($duration);

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorId,
            'appointment_date' => $upcomingDate,
            'appointment_end' => $upcomingEnd,
            'status' => 'confirmed',
            'duration' => $duration,
            'fee' => 10000, // $100.00
            'confirmed_at' => now(),
            'created_at' => now()->subDays(1),
        ]);
    }
}