<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KioskSession>
 */
class KioskSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kiosk_id' => \App\Models\Kiosk::factory(),
            'start_time' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'end_time' => $this->faker->optional(0.7)->dateTimeBetween('now', '+1 day'),
            'status' => $this->faker->randomElement(['active', 'completed', 'abandoned', 'error']),
            'session_data' => [
                'user_agent' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
            ],
        ];
    }
}
