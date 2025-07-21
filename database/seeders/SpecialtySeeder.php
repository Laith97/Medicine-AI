<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            [
                'name' => 'General Medicine',
                'description' => 'Primary care and general health management',
                'is_active' => true,
            ],
            [
                'name' => 'Cardiology',
                'description' => 'Heart and cardiovascular system specialists',
                'is_active' => true,
            ],
            [
                'name' => 'Dermatology',
                'description' => 'Skin, hair, and nail conditions',
                'is_active' => true,
            ],
            [
                'name' => 'Endocrinology',
                'description' => 'Hormone and metabolic disorders',
                'is_active' => true,
            ],
            [
                'name' => 'Gastroenterology',
                'description' => 'Digestive system and liver conditions',
                'is_active' => true,
            ],
            [
                'name' => 'Neurology',
                'description' => 'Brain, spinal cord, and nervous system',
                'is_active' => true,
            ],
            [
                'name' => 'Orthopedics',
                'description' => 'Bones, joints, and musculoskeletal system',
                'is_active' => true,
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Medical care for infants, children, and adolescents',
                'is_active' => true,
            ],
            [
                'name' => 'Psychiatry',
                'description' => 'Mental health and behavioral disorders',
                'is_active' => true,
            ],
            [
                'name' => 'Pulmonology',
                'description' => 'Respiratory system and lung conditions',
                'is_active' => true,
            ],
            [
                'name' => 'Urology',
                'description' => 'Urinary tract and male reproductive system',
                'is_active' => true,
            ],
            [
                'name' => 'Gynecology',
                'description' => 'Female reproductive health',
                'is_active' => true,
            ],
            [
                'name' => 'Ophthalmology',
                'description' => 'Eye and vision care',
                'is_active' => true,
            ],
            [
                'name' => 'ENT (Otolaryngology)',
                'description' => 'Ear, nose, and throat conditions',
                'is_active' => true,
            ],
            [
                'name' => 'Oncology',
                'description' => 'Cancer diagnosis and treatment',
                'is_active' => true,
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::create($specialty);
        }
    }
}
