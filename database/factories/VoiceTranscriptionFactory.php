<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Diagnosis;
use App\Models\VoiceTranscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoiceTranscriptionFactory extends Factory
{
    protected $model = VoiceTranscription::class;

    public function definition()
    {
        return [
            'doctor_id' => User::factory()->create()->id,
            'patient_id' => User::factory()->create()->id,
            'diagnosis_id' => Diagnosis::factory()->create()->id,
            'session_id' => $this->faker->uuid,
            'raw_transcription' => $this->faker->paragraph,
            'audio_file' => $this->faker->word . '.mp3',
            'audio_format' => 'mp3',
            'audio_duration' => $this->faker->randomNumber(3),
            'audio_file_size' => $this->faker->randomNumber(6),
            'extracted_data' => json_encode(['sample' => 'data']),
            'ai_analysis' => json_encode(['analysis' => 'results']),
            'structured_chart' => json_encode([]),
            'is_confirmed' => $this->faker->boolean,
            'is_final' => $this->faker->boolean,
            'status' => $this->faker->randomElement(['active', 'completed', 'error']),
            'session_started_at' => $this->faker->dateTime,
            'session_ended_at' => $this->faker->dateTime,
        ];
    }
}