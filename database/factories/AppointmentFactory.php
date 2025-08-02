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
        $appointmentEnd = $this->faker->dateTimeBetween($appointmentDate, $appointmentDate->format('Y-m-d H:i:s') . ' +2 hours');

        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => User::factory(),
            'appointment_number' => 'APT-' . strtoupper($this->faker->bothify('??########')),
            'appointment_date' => $appointmentDate,
            'appointment_end' => $appointmentEnd,
            'appointment_type' => $this->faker->randomElement(['in_person', 'video_call', 'phone_call']),
            'reason' => $this->faker->sentence(),
            'symptoms' => $this->faker->optional()->paragraph(),
            'doctor_notes' => $this->faker->optional()->paragraph(),
            'patient_notes' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled', 'no_show']),
            'consultation_fee' => $this->faker->numberBetween(5000, 50000), // In cents
            'meeting_link' => $this->faker->optional()->url(),
            'reminder_sent' => $this->faker->boolean(30),
            'follow_up_required' => $this->faker->boolean(20),
        ];
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
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
