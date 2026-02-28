<?php

namespace Database\Factories;

use App\Models\HepProgram;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Diagnosis;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HepProgram>
 */
class HEPProgramFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HepProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'doctor_id' => Doctor::factory(),
            'patient_id' => User::factory(),
            'diagnosis_id' => Diagnosis::factory(),
            'appointment_id' => Appointment::factory(),
            'duration_weeks' => $this->faker->numberBetween(4, 12),
            'frequency_per_week' => $this->faker->numberBetween(1, 7),
            'goals' => [$this->faker->sentence(), $this->faker->sentence()],
            'precautions' => [$this->faker->sentence(), $this->faker->sentence()],
            'status' => $this->faker->randomElement(['active', 'completed', 'paused']),
        ];
    }
}
