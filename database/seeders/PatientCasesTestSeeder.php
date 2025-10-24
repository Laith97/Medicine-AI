<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\PatientAnalysis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientCasesTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a doctor user
        $doctorUser = User::firstOrCreate([
            'email' => 'sarah.johnson@medclinic.com',
        ], [
            'name' => 'Dr. Sarah Johnson',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'subscription_active' => true,
        ]);

        // Create the doctor profile
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'bio' => 'Experienced family physician with over 15 years of practice in internal medicine and pediatrics.',
            'consultation_fee' => 7500, // $75.00
            'appointment_duration' => 30,
            'is_verified' => true,
        ]);

        // Create multiple patient users
        $patients = User::factory()->count(5)->create([
            'role' => 'patient',
        ]);

        // Create realistic patient analyses for the doctor
        $patientNames = [
            'John Smith',
            'Maria Garcia',
            'Robert Wilson',
            'Lisa Chen',
            'David Brown',
            'Jennifer Davis',
            'Michael Rodriguez',
            'Emily Johnson',
        ];

        $commonSymptoms = [
            'Persistent cough and fever',
            'Severe headache and nausea',
            'Chest pain and shortness of breath',
            'Abdominal pain and vomiting',
            'Joint pain and fatigue',
            'Skin rash and itching',
            'Back pain and limited mobility',
            'Dizziness and blurred vision',
        ];

        $diagnoses = [
            'Upper respiratory infection',
            'Migraine headache',
            'Acute bronchitis',
            'Gastroenteritis',
            'Rheumatoid arthritis flare',
            'Allergic dermatitis',
            'Muscle strain',
            'Hypertensive episode',
        ];

        foreach ($patientNames as $index => $patientName) {
            $patientUser = $patients[$index % count($patients)];

            // Create 1-3 visits per patient
            $visitCount = rand(1, 3);

            for ($visit = 1; $visit <= $visitCount; $visit++) {
                PatientAnalysis::factory()->create([
                    'user_id' => $doctorUser->id,
                    'name' => $patientName,
                    'age' => rand(25, 75),
                    'gender' => collect(['male', 'female'])->random(),
                    'weight' => rand(60, 100),
                    'height' => rand(160, 190),
                    'temperature' => rand(365, 390) / 10, // 36.5 to 39.0
                    'blood_pressure' => collect(['120/80', '130/85', '140/90', '110/70', '125/82'])->random(),
                    'blood_sugar' => rand(85, 140),
                    'symptoms' => json_encode(collect($commonSymptoms)->random(rand(1, 3))->toArray()),
                    'chief_complaint' => collect($commonSymptoms)->random(),
                    'symptom_duration' => collect(['2 days', '1 week', '3 days', '2 weeks', '1 month'])->random(),
                    'past_medical_history' => collect([
                        'Hypertension, Type 2 Diabetes',
                        'No significant past medical history',
                        'Asthma, Seasonal allergies',
                        'Previous knee surgery',
                        'Depression, Anxiety',
                    ])->random(),
                    'medication_history' => collect([
                        'Lisinopril 10mg daily, Metformin 500mg twice daily',
                        'No current medications',
                        'Albuterol inhaler as needed, Loratadine 10mg daily',
                        'Ibuprofen 400mg as needed',
                        'Sertraline 50mg daily',
                    ])->random(),
                    'allergies' => json_encode(collect([
                        'Penicillin',
                        'Sulfa drugs',
                        'Shellfish',
                        'Peanuts',
                    ])->random(rand(0, 2))),
                    'family_history' => collect([
                        'Father: Heart disease, Mother: Diabetes',
                        'No significant family history',
                        'Sister: Breast cancer',
                        'Brother: Hypertension',
                    ])->random(),
                    'pain_scale' => rand(0, 10),
                    'visit_type' => collect(['Initial', 'Follow-up', 'Emergency'])->random(),
                    'heart_rate' => rand(65, 95),
                    'respiratory_rate' => rand(14, 20),
                    'oxygen_saturation' => rand(96, 100),
                    'preliminary_diagnosis' => collect($diagnoses)->random(),
                    'ai_response' => 'Based on the patient\'s symptoms and vital signs, the preliminary assessment suggests ' . collect($diagnoses)->random() . '. Recommended next steps include diagnostic testing and appropriate treatment plan.',
                    'physician_notes' => 'Patient presents with ' . collect($commonSymptoms)->random() . '. Vital signs stable. Will monitor closely.',
                    'visit_number' => $visit,
                    'patient_key' => md5($patientName . $visit),
                ]);
            }
        }

        // Create additional random patient analyses to have more test data
        for ($i = 0; $i < 10; $i++) {
            PatientAnalysis::factory()->create([
                'user_id' => $doctorUser->id,
                'symptoms' => json_encode(['fever', 'headache']),
                'allergies' => json_encode(['penicillin']),
            ]);
        }
    }
}