<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email_enabled' => $this->faker->boolean(80),
            'email_appointment_reminders' => $this->faker->boolean(90),
            'email_diagnosis_updates' => $this->faker->boolean(90),
            'email_review_requests' => $this->faker->boolean(90),
            'email_system_alerts' => $this->faker->boolean(90),
            'email_marketing' => $this->faker->boolean(20),
            'sms_enabled' => $this->faker->boolean(30),
            'sms_appointment_reminders' => $this->faker->boolean(40),
            'sms_urgent_alerts' => $this->faker->boolean(80),
            'in_app_enabled' => $this->faker->boolean(90),
            'in_app_sound' => $this->faker->boolean(80),
            'in_app_desktop' => $this->faker->boolean(90),
            'in_app_vibrate' => $this->faker->boolean(30),
            'frequency' => $this->faker->randomElement(['immediate', 'daily', 'weekly']),
            'quiet_hours_start' => $this->faker->time('H:i'),
            'quiet_hours_end' => $this->faker->time('H:i'),
            'respect_quiet_hours' => $this->faker->boolean(70),
            'appointment_booked' => $this->faker->boolean(90),
            'appointment_reminder' => $this->faker->boolean(95),
            'diagnosis_submitted' => $this->faker->boolean(90),
            'review_submitted' => $this->faker->boolean(90),
            'voice_transcription_completed' => $this->faker->boolean(90),
            'system_alert' => $this->faker->boolean(95),
        ];
    }
}
