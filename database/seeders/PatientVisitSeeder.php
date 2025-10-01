<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PatientVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find an existing doctor user
        $user = \App\Models\User::where('role', 'doctor')->first();

        if (!$user) {
            $this->command->error('No doctor user found. Please run the DoctorSeeder first.');
            return;
        }
        
        // Create settings for the user
        \App\Models\Setting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'criterion' => 'NICE',
                'specialty' => 'General Practice'
            ]
        );
        
        // Create patients with multiple visits
        $patients = [
            [
                'name' => 'John Smith',
                'age' => 45,
                'gender' => 'male',
                'visits' => [
                    [
                        'created_at' => now()->subDays(60),
                        'symptoms' => json_encode(['Headache', 'Fever']),
                        'ai_response' => "Visit 1: Patient presented with headache and fever. Likely viral infection."
                    ],
                    [
                        'created_at' => now()->subDays(30),
                        'symptoms' => json_encode(['Cough', 'Chest pain']),
                        'ai_response' => "Visit 2: Patient now has cough and chest pain. Possible pneumonia."
                    ],
                    [
                        'created_at' => now()->subDays(7),
                        'symptoms' => json_encode(['Improved breathing', 'Mild cough']),
                        'ai_response' => "Visit 3: Patient showing improvement. Continue current treatment."
                    ]
                ]
            ],
            [
                'name' => 'Jane Doe',
                'age' => 35,
                'gender' => 'female',
                'visits' => [
                    [
                        'created_at' => now()->subDays(45),
                        'symptoms' => json_encode(['Joint pain', 'Fatigue']),
                        'ai_response' => "Visit 1: Patient has joint pain and fatigue. Possible rheumatoid arthritis."
                    ],
                    [
                        'created_at' => now()->subDays(15),
                        'symptoms' => json_encode(['Reduced joint pain', 'Mild fatigue']),
                        'ai_response' => "Visit 2: Patient showing improvement with medication. Continue treatment."
                    ]
                ]
            ],
            [
                'name' => 'Alice Johnson',
                'age' => 28,
                'gender' => 'female',
                'visits' => [
                    [
                        'created_at' => now()->subDays(10),
                        'symptoms' => json_encode(['Nausea', 'Headache']),
                        'ai_response' => "Visit 1: Patient reports nausea and headache. Possible migraine or gastrointestinal issue."
                    ]
                ]
            ]
        ];

        foreach ($patients as $patient) {
            $patientKey = \App\Models\PatientAnalysis::generatePatientKey(
                $patient['name'],
                $patient['age'],
                $patient['gender'],
                $user->id
            );
            
            $previousRecordId = null;
            
            foreach ($patient['visits'] as $index => $visit) {
                $data = [
                    'name' => $patient['name'],
                    'age' => $patient['age'],
                    'gender' => $patient['gender'],
                    'symptoms' => $visit['symptoms'],
                    'ai_response' => $visit['ai_response'],
                    'user_id' => $user->id,
                    'previous_record_id' => $previousRecordId,
                    'visit_number' => $index + 1,
                    'patient_key' => $patientKey,
                    'created_at' => $visit['created_at'],
                    'updated_at' => $visit['created_at']
                ];

                // Add sample data for appointment 6 (the 6th record)
                if ($index + 1 == 1 && $patient['name'] == 'Alice Johnson') { // Since it's the 6th overall, but for this patient it's visit 1
                    // $data['assigned_patient_id'] = 142; // Removed hardcoded id
                    $data['allergies'] = json_encode(["penicillin", "aspirin"]);
                    $data['past_medications'] = json_encode(["ibuprofen", "paracetamol"]);
                }

                $record = \App\Models\PatientAnalysis::create($data);
                
                $previousRecordId = $record->id;
            }
        }
        
        $this->command->info('Created ' . count($patients) . ' patients with multiple visits.');
    }
}
