<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KioskCheckin>
 */
class KioskCheckinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kiosk_session_id' => function() {
                $session = \App\Models\KioskSession::factory()->create();
                return $session->id;
            },
            'appointment_id' => \App\Models\Appointment::factory(),
            'checkin_time' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'verification_method' => $this->faker->randomElement(['qr_code', 'id_card', 'biometric', 'manual']),
            'verification_data' => [
                'verified' => true,
                'confidence_score' => $this->faker->numberBetween(70, 100),
            ],
        ];
    }
}
