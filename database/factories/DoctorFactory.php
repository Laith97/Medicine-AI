<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Doctor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Use an existing user or create one
        $user = User::first() ?? User::factory()->create();

        return [
            'user_id' => $user->id,
            'specialty_id' => null, // Will be set in tests as needed
            'license_number' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'phone' => $this->faker->phoneNumber(),
            'bio' => $this->faker->optional()->paragraph(),
            'profile_image' => $this->faker->optional()->imageUrl(),
            'languages' => ['English'],
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'consultation_fee' => $this->faker->numberBetween(5000, 50000), // In cents
            'appointment_duration' => $this->faker->randomElement([15, 30, 45, 60]),
            'auto_approve_appointments' => $this->faker->boolean(80), // 80% auto-approve
            'allow_cancellation' => true,
            'allow_rescheduling' => true,
            'cancellation_hours' => $this->faker->numberBetween(1, 48),
            'average_rating' => $this->faker->randomFloat(2, 1, 5),
            'total_reviews' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'is_verified' => $this->faker->boolean(90), // 90% verified
        ];
    }

    /**
     * Indicate that the doctor is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the doctor is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}
