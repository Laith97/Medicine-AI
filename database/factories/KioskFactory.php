<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kiosk>
 */
class KioskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Kiosk',
            'location' => $this->faker->address(),
            'serial_number' => $this->faker->unique()->bothify('KSK-####-????'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'configuration' => [
                'language' => $this->faker->randomElement(['en', 'es', 'fr']),
                'theme' => $this->faker->randomElement(['light', 'dark']),
                'auto_logout' => $this->faker->numberBetween(30, 300), // seconds
            ],
            'last_ping' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}
