<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\AvailabilitySlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = Specialty::all();

        $doctors = [
            [
                'name' => 'Dr. Sarah Johnson',
                'email' => 'sarah.johnson@medcura.com',
                'specialty' => 'General Medicine',
                'bio' => 'Experienced family physician with over 15 years of practice. Specializes in preventive care and chronic disease management.',
                'city' => 'New York',
                'state' => 'NY',
                'languages' => ['English', 'Spanish'],
                'consultation_fee' => 15000, // $150.00
            ],
            [
                'name' => 'Dr. Michael Chen',
                'email' => 'michael.chen@medcura.com',
                'specialty' => 'Cardiology',
                'bio' => 'Board-certified cardiologist specializing in interventional cardiology and heart disease prevention.',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'languages' => ['English', 'Mandarin'],
                'consultation_fee' => 25000, // $250.00
            ],
            [
                'name' => 'Dr. Emily Rodriguez',
                'email' => 'emily.rodriguez@medcura.com',
                'specialty' => 'Dermatology',
                'bio' => 'Dermatologist with expertise in medical and cosmetic dermatology, skin cancer screening, and acne treatment.',
                'city' => 'Miami',
                'state' => 'FL',
                'languages' => ['English', 'Spanish'],
                'consultation_fee' => 20000, // $200.00
            ],
            [
                'name' => 'Dr. James Wilson',
                'email' => 'james.wilson@medcura.com',
                'specialty' => 'Orthopedics',
                'bio' => 'Orthopedic surgeon specializing in sports medicine, joint replacement, and minimally invasive procedures.',
                'city' => 'Chicago',
                'state' => 'IL',
                'languages' => ['English'],
                'consultation_fee' => 30000, // $300.00
            ],
            [
                'name' => 'Dr. Lisa Thompson',
                'email' => 'lisa.thompson@medcura.com',
                'specialty' => 'Pediatrics',
                'bio' => 'Pediatrician dedicated to providing comprehensive healthcare for children from infancy through adolescence.',
                'city' => 'Houston',
                'state' => 'TX',
                'languages' => ['English'],
                'consultation_fee' => 18000, // $180.00
            ],
            [
                'name' => 'Dr. David Kumar',
                'email' => 'david.kumar@medcura.com',
                'specialty' => 'Neurology',
                'bio' => 'Neurologist with expertise in treating epilepsy, stroke, multiple sclerosis, and other neurological disorders.',
                'city' => 'Boston',
                'state' => 'MA',
                'languages' => ['English', 'Hindi'],
                'consultation_fee' => 28000, // $280.00
            ],
        ];

        foreach ($doctors as $doctorData) {
            // Create user
            $user = User::firstOrCreate([
                'email' => $doctorData['email']
            ], [
                'name' => $doctorData['name'],
                'password' => Hash::make('password123'),
                'role' => 'doctor',
                'email_verified_at' => now(),
            ]);

            // Find specialty
            $specialty = $specialties->where('name', $doctorData['specialty'])->first();

            // Create doctor profile
            $doctor = Doctor::firstOrCreate([
                'user_id' => $user->id
            ], [
                'specialty_id' => $specialty->id,
                'license_number' => 'LIC' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'bio' => $doctorData['bio'],
                'consultation_fee' => $doctorData['consultation_fee'],
                'appointment_duration' => 30,
                'address' => rand(100, 9999) . ' Medical Center Dr',
                'city' => $doctorData['city'],
                'state' => $doctorData['state'],
                'zip_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                'country' => 'United States',
                'phone' => '+1' . rand(1000000000, 9999999999),
                'languages' => $doctorData['languages'],
                'is_verified' => true,
                'is_active' => true,
                'auto_approve_appointments' => rand(0, 1) == 1,
                'average_rating' => rand(40, 50) / 10, // 4.0 to 5.0
                'total_reviews' => rand(10, 100),
            ]);

            // Create availability slots (Monday to Friday, 9 AM to 5 PM)
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($days as $day) {
                // Morning slot
                AvailabilitySlot::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'slot_duration' => 30,
                    'max_bookings_per_slot' => 1,
                    'is_active' => true,
                ]);

                // Afternoon slot
                AvailabilitySlot::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '14:00',
                    'end_time' => '17:00',
                    'slot_duration' => 30,
                    'max_bookings_per_slot' => 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
