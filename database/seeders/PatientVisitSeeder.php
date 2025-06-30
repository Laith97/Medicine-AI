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
        // Create a test user if none exists
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        
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
                $record = \App\Models\PatientAnalysis::create([
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
                ]);
                
                $previousRecordId = $record->id;
            }
        }
        
        $this->command->info('Created ' . count($patients) . ' patients with multiple visits.');
    }
}
