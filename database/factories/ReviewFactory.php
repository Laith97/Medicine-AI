<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => User::factory(),
            'appointment_id' => Appointment::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->paragraph(),
            'is_anonymous' => $this->faker->boolean(20), // 20% anonymous reviews
            'is_approved' => true,
            'posted_to_google' => $this->faker->boolean(30), // 30% posted to Google
            'google_review_id' => $this->faker->optional()->uuid(),
            'google_posted_at' => $this->faker->optional()->dateTime(),
            'source' => $this->faker->randomElement(['medcura', 'google'])
        ];
    }

    /**
     * Indicate that the review is a guest review.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => null,
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->email(),
            'is_verified' => $this->faker->boolean(80), // 80% verified guest reviews
        ]);
    }

    /**
     * Indicate that the review has consent to post to Google.
     */
    public function withGoogleConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'posted_to_google' => true,
        ]);
    }

    /**
     * Indicate that the review is posted to Google.
     */
    public function postedToGoogle(): static
    {
        return $this->state(fn (array $attributes) => [
            'posted_to_google' => true,
            'google_posted_at' => now(),
        ]);
    }
}
