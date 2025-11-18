<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        $specialties = [
            'Cardiology',
            'Dermatology',
            'Endocrinology',
            'Gastroenterology',
            'Neurology',
            'Oncology',
            'Orthopedics',
            'Pediatrics',
            'Psychiatry',
            'Radiology',
            'Anesthesiology',
            'Emergency Medicine',
            'Family Medicine',
            'Internal Medicine',
            'Obstetrics and Gynecology',
            'Ophthalmology',
            'Pathology',
            'Physical Medicine',
            'Plastic Surgery',
            'Preventive Medicine',
            'Radiation Oncology',
            'Surgery',
            'Urology'
        ];

        // Use a unique counter to ensure no duplicates
        static $counter = 0;
        $counter++;

        $name = $specialties[array_rand($specialties)] . " {$counter}";
        $slug = \Illuminate\Support\Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->paragraph(),
            'icon' => $this->faker->optional()->randomElement(['fas fa-heart', 'fas fa-brain', 'fas fa-bone', 'fas fa-eye']),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
