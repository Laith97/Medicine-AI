<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Diagnosis::class;

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
            'primary_diagnosis' => $this->faker->sentence(),
            'icd10_code' => $this->faker->bothify('##.##'),
            'description' => $this->faker->paragraph(),
            'diagnosis_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => $this->faker->randomElement(['active', 'resolved', 'chronic']),
        ];
    }
}
