<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
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
        ]);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'icon' => fake()->optional()->randomElement(['fas fa-heart', 'fas fa-brain', 'fas fa-bone', 'fas fa-eye']),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'created_at' => fake()->dateTimeThisMonth(),
            'updated_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
