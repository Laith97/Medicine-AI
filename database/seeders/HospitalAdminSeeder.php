<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Support\Facades\Hash;

class HospitalAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample hospital
        $hospital = Hospital::firstOrCreate(
            ['slug' => 'city-general-hospital'],
            [
                'name' => 'City General Hospital',
                'description' => 'A leading healthcare institution providing comprehensive medical services.',
                'address' => '123 Medical Center Drive',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10001',
                'phone' => '+1 (555) 123-4567',
                'email' => 'info@citygeneralhospital.com',
                'website' => 'https://citygeneralhospital.com',
                'is_active' => true,
            ]
        );

        // Create a hospital admin
        $hospitalAdmin = User::firstOrCreate(
            ['email' => 'admin@citygeneralhospital.com'],
            [
                'name' => 'Hospital Administrator',
                'password' => Hash::make('password123'),
                'role' => 'hospital_admin',
                'hospital_id' => $hospital->id,
                'phone' => '+1 (555) 123-4567',
                'email_verified_at' => now(),
            ]
        );

        // Get some specialties (assuming they exist)
        $specialties = Specialty::take(5)->get();
        
        if ($specialties->count() === 0) {
            // Create some basic specialties if none exist
            $specialtyNames = [
                'Internal Medicine',
                'Cardiology',
                'Pediatrics',
                'Orthopedics',
                'Dermatology'
            ];
            
            foreach ($specialtyNames as $name) {
                $specialties[] = Specialty::create(['name' => $name]);
            }
            $specialties = collect($specialties);
        }

        // Create sample doctors under this hospital
        $doctorData = [
            [
                'name' => 'Dr. John Smith',
                'email' => 'john.smith@citygeneralhospital.com',
                'specialty' => $specialties->first(),
                'license_number' => 'MD123456',
                'bio' => 'Experienced internal medicine physician with expertise in preventive care.',
                'consultation_fee' => 200,
            ],
            [
                'name' => 'Dr. Sarah Johnson',
                'email' => 'sarah.johnson@citygeneralhospital.com',
                'specialty' => $specialties->skip(1)->first(),
                'license_number' => 'MD789012',
                'bio' => 'Board-certified cardiologist specializing in interventional cardiology.',
                'consultation_fee' => 300,
            ],
            [
                'name' => 'Dr. Michael Brown',
                'email' => 'michael.brown@citygeneralhospital.com',
                'specialty' => $specialties->skip(2)->first(),
                'license_number' => 'MD345678',
                'bio' => 'Pediatrician dedicated to providing comprehensive care for children.',
                'consultation_fee' => 150,
            ],
        ];

        foreach ($doctorData as $data) {
            // Create the user account
            $doctorUser = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'doctor',
                    'hospital_id' => $hospital->id,
                    'phone' => '+1 (555) ' . rand(100, 999) . '-' . rand(1000, 9999),
                    'email_verified_at' => now(),
                ]
            );

            // Create the doctor profile if it doesn't exist
            if (!$doctorUser->doctor) {
                    Doctor::create([
                        'user_id' => $doctorUser->id,
                        'specialty_id' => $data['specialty']->id,
                        'license_number' => $data['license_number'],
                        'bio' => $data['bio'],
                        'consultation_fee' => $data['consultation_fee'],
                        'is_active' => true,
                        'is_verified' => true,
                    ]);
                }
        }

        $this->command->info('Hospital admin and sample data created successfully!');
        $this->command->info('Hospital Admin Login:');
        $this->command->info('Email: admin@citygeneralhospital.com');
        $this->command->info('Password: password123');
        $this->command->info('');
        $this->command->info('Sample Doctor Logins:');
        foreach ($doctorData as $data) {
            $this->command->info('Email: ' . $data['email']);
            $this->command->info('Password: password123');
        }
    }
}