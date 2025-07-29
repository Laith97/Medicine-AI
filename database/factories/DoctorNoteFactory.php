<?php

namespace Database\Factories;

use App\Models\DoctorNote;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorNoteFactory extends Factory
{
    protected $model = DoctorNote::class;

    public function definition(): array
    {
        $noteType = $this->faker->randomElement(['text', 'voice']);

        return [
            'doctor_id' => User::factory()->state(['role' => 'doctor']),
            'patient_id' => $this->faker->optional(0.7)->passthrough(User::factory()->state(['role' => 'patient'])),
            'appointment_id' => $this->faker->optional(0.5)->passthrough(Appointment::factory()),
            'note_type' => $noteType,
            'note_text' => $noteType === 'text' ? $this->faker->paragraphs(3, true) : null,
            'transcript' => $noteType === 'voice' ? $this->faker->paragraphs(2, true) : null,
            'audio_file_path' => $noteType === 'voice' ? 'audio/notes/' . $this->faker->uuid() . '.webm' : null,
            'appointment_date' => $this->faker->optional(0.6)->passthrough($this->faker->dateTimeBetween('-30 days', '+30 days')),
            'title' => $this->faker->optional(0.4)->passthrough($this->faker->sentence(4)),
            'tags' => $this->faker->optional(0.3)->passthrough($this->faker->words(3)),
        ];
    }

    /**
     * Indicate that the note is a text note.
     */
    public function textNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'note_type' => 'text',
            'note_text' => $this->faker->paragraphs(3, true),
            'transcript' => null,
            'audio_file_path' => null,
        ]);
    }

    /**
     * Indicate that the note is a voice note.
     */
    public function voiceNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'note_type' => 'voice',
            'note_text' => null,
            'transcript' => $this->faker->paragraphs(2, true),
            'audio_file_path' => 'audio/notes/' . $this->faker->uuid() . '.webm',
        ]);
    }

    /**
     * Indicate that the note is a general note (no patient).
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => null,
            'appointment_id' => null,
        ]);
    }

    /**
     * Indicate that the note is patient-specific.
     */
    public function patientSpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => User::factory()->state(['role' => 'patient']),
        ]);
    }
}
