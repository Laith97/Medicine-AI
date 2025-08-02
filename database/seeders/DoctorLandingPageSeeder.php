<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\DoctorLandingPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorLandingPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a specialty if it doesn't exist
        $specialty = Specialty::firstOrCreate([
            'name' => 'General Practice',
            'description' => 'General medical practice',
            'is_active' => true,
        ]);

        // Create a sample doctor user
        $user = User::firstOrCreate([
            'email' => 'dr.smith@example.com'
        ], [
            'name' => 'Dr. John Smith',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'phone' => '+1-555-0123',
        ]);

        // Create doctor profile
        $doctor = Doctor::firstOrCreate([
            'user_id' => $user->id
        ], [
            'specialty_id' => $specialty->id,
            'license_number' => 'MD123456',
            'phone' => '+1-555-0123',
            'bio' => 'Experienced general practitioner with over 10 years of experience in family medicine. Dedicated to providing comprehensive healthcare services to patients of all ages.',
            'address' => '123 Medical Center Drive',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'USA',
            'consultation_fee' => 15000, // $150.00 in cents
            'appointment_duration' => 30,
            'auto_approve_appointments' => false,
            'allow_cancellation' => true,
            'allow_rescheduling' => true,
            'cancellation_hours' => 24,
            'is_active' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'languages' => ['English', 'Spanish'],
        ]);

        // Create landing page
        DoctorLandingPage::firstOrCreate([
            'doctor_id' => $doctor->id
        ], [
            'username' => 'drjohnsmith',
            'template' => 'template1',
            'page_title' => 'Dr. John Smith - General Practice Physician',
            'page_description' => 'Book an appointment with Dr. John Smith, an experienced general practice physician in New York. Comprehensive healthcare services for the whole family.',
            'tagline' => 'Your Health, Our Priority',
            'about_text' => 'Dr. John Smith is a board-certified family medicine physician with over 10 years of experience providing comprehensive healthcare services. He specializes in preventive care, chronic disease management, and acute illness treatment for patients of all ages.',
            'colors' => [
                'primary' => '#2563eb',
                'secondary' => '#64748b',
                'accent' => '#10b981',
                'button' => '#2563eb',
                'button_text' => '#ffffff',
                'header_bg' => '#ffffff',
                'footer_bg' => '#f8fafc',
            ],
            'section_visibility' => [
                'hero' => true,
                'about' => true,
                'appointments' => true,
                'reviews' => true,
                'contact' => true,
            ],
            'is_published' => true,
            'subdomain_enabled' => true,
        ]);

        $this->command->info('Sample doctor and landing page created successfully!');
        $this->command->info('Doctor login: dr.smith@example.com / password');
        $this->command->info('Landing page URL: /doctor/drjohnsmith');
        $this->command->info('Subdomain URL: drjohnsmith.medcuraai.com (if configured)');
    }
}
