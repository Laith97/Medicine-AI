<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appointmentDate = $this->faker->dateTimeBetween('+1 day', '+1 month');

        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => User::factory(),
            'appointment_date' => $appointmentDate,
            'appointment_type' => $this->faker->randomElement(['consultation', 'follow_up', 'checkup']),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled', 'no_show']),
            'consultation_fee' => $this->faker->numberBetween(5000, 50000), // In cents
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'refunded']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the appointment is a guest appointment.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => null,
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->email(),
            'guest_phone' => $this->faker->phoneNumber(),
        ]);
    }
}
