<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Doctor;
use App\Models\WaitlistPatientPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WaitlistPatientPreference>
 */
class WaitlistPatientPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WaitlistPatientPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'doctor_id' => Doctor::factory(),
            'preferred_times' => $this->faker->randomElements(['morning', 'afternoon', 'evening'], 2),
            'preferred_days' => $this->faker->randomElements(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], 3),
            'service_priorities' => $this->faker->randomElements(['consultation', 'follow_up', 'procedure', 'emergency'], 2),
            'notification_settings' => [
                'email' => true,
                'sms' => $this->faker->boolean(),
                'push' => $this->faker->boolean(),
            ],
            'auto_accept_threshold' => $this->faker->numberBetween(1, 14),
        ];
    }
}