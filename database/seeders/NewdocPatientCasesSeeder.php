<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PatientAnalysis;
use Illuminate\Database\Seeder;

class NewdocPatientCasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the newdoc user
        $newdocUser = User::where('email', 'like', '%newdoc%')->orWhere('name', 'like', '%newdoc%')->first();

        if (!$newdocUser) {
            $this->command->error('newdoc user not found!');
            return;
        }

        $this->command->info("Found newdoc user: {$newdocUser->name} (ID: {$newdocUser->id})");

        // Create realistic patient analyses for the newdoc user
        $patientNames = [
            'Patient 1',
            'Patient 2',
            'Patient 3',
            'Patient 4',
            'Patient 5',
        ];

        $commonSymptoms = [
            'Persistent cough and fever',
            'Severe headache and nausea',
            'Chest pain and shortness of breath',
            'Abdominal pain and vomiting',
            'Joint pain and fatigue',
        ];

        $diagnoses = [
            'Upper respiratory infection',
            'Migraine headache',
            'Acute bronchitis',
            'Gastroenteritis',
            'Rheumatoid arthritis flare',
        ];

        foreach ($patientNames as $index => $patientName) {
            // Create 1-2 visits per patient
            $visitCount = rand(1, 2);

            for ($visit = 1; $visit <= $visitCount; $visit++) {
                PatientAnalysis::factory()->create([
                    'user_id' => $newdocUser->id,
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
                    'symptom_duration' => collect(['2 days', '1 week', '3 days', '2 weeks'])->random(),
                    'past_medical_history' => collect([
                        'Hypertension, Type 2 Diabetes',
                        'No significant past medical history',
                        'Asthma, Seasonal allergies',
                        'Previous knee surgery',
                    ])->random(),
                    'medication_history' => collect([
                        'Lisinopril 10mg daily, Metformin 500mg twice daily',
                        'No current medications',
                        'Albuterol inhaler as needed',
                        'Ibuprofen 400mg as needed',
                    ])->random(),
                    'allergies' => json_encode(collect([
                        'Penicillin',
                        'Sulfa drugs',
                        'Shellfish',
                    ])->random(rand(0, 2))),
                    'family_history' => collect([
                        'Father: Heart disease, Mother: Diabetes',
                        'No significant family history',
                        'Sister: Breast cancer',
                    ])->random(),
                    'pain_scale' => rand(0, 10),
                    'visit_type' => collect(['Initial', 'Follow-up'])->random(),
                    'heart_rate' => rand(65, 95),
                    'respiratory_rate' => rand(14, 20),
                    'oxygen_saturation' => rand(96, 100),
                    'preliminary_diagnosis' => collect($diagnoses)->random(),
                    'ai_response' => 'Based on the patient\'s symptoms and vital signs, the preliminary assessment suggests ' . collect($diagnoses)->random() . '. Recommended next steps include diagnostic testing and appropriate treatment plan.',
                    'physician_notes' => 'Patient presents with ' . collect($commonSymptoms)->random() . '. Vital signs stable. Will monitor closely.',
                    'visit_number' => $visit,
                    'patient_key' => md5($patientName . $visit . $newdocUser->id),
                ]);
            }
        }

        $this->command->info("Created patient cases for newdoc user");
    }
}