<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\User;
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
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => User::factory()->create()->id,
            'diagnosis_text' => $this->faker->paragraph(),
            'voice_transcript' => $this->faker->paragraph(),
            'voice_file_path' => $this->faker->word . '.mp3',
            'patient_data' => [
                'name' => $this->faker->name(),
                'age' => $this->faker->numberBetween(18, 80),
                'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            ],
            'ai_response' => $this->faker->sentence(),
            'follow_up_count' => $this->faker->numberBetween(0, 5),
            'patient_notified' => $this->faker->boolean(),
            'patient_viewed_at' => $this->faker->optional()->dateTime(),
            'patient_reviewed' => $this->faker->boolean(),
        ];
    }
}
