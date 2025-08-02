<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'criterion' => $this->faker->randomElement(['CDC', 'WHO', 'ICD-10', 'DSM-5']),
            'specialty' => $this->faker->randomElement([
                'Internal Medicine',
                'Cardiology',
                'Neurology',
                'Pediatrics',
                'Dermatology',
                'Orthopedics',
                'Psychiatry',
                'Emergency Medicine',
                'Family Medicine',
                'Radiology'
            ]),
        ];
    }
}
