<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition()
    {
        return [
            'appointment_id' => Appointment::factory(),
            'doctor_id' => User::factory()->state(['role' => 'doctor']),
            'patient_id' => User::factory()->state(['role' => 'patient']),
            'medication_name' => $this->faker->randomElement([
                'Acetaminophen',
                'Ibuprofen',
                'Amoxicillin',
                'Lisinopril',
                'Metformin',
                'Omeprazole',
                'Simvastatin',
                'Amlodipine'
            ]),
            'dosage' => $this->faker->randomElement([
                '500mg',
                '250mg',
                '10mg',
                '50mg',
                '100mg',
                '20mg',
                '40mg',
                '5mg'
            ]),
            'frequency' => $this->faker->randomElement([
                'twice daily',
                'three times daily',
                'once daily',
                'every 6 hours',
                'every 8 hours',
                'as needed'
            ]),
            'duration' => $this->faker->randomElement([
                '7 days',
                '10 days',
                '14 days',
                '30 days',
                '3 days',
                '5 days'
            ]),
            'notes' => $this->faker->optional(0.7)->sentence(),
            'ai_suggestions' => $this->faker->optional(0.5)->randomElements([
                [
                    'med' => 'Acetaminophen',
                    'dosage' => '500mg',
                    'freq' => 'every 6 hours',
                    'dur' => '7 days',
                    'confidence' => 90,
                    'reason' => 'Effective for pain and fever relief'
                ]
            ], $this->faker->numberBetween(1, 3)),
            'ai_risk_flags' => $this->faker->optional(0.5)->randomElements([
                'Monitor for gastrointestinal side effects',
                'Check for drug interactions',
                'Patient has allergy history'
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'duration' => '30 days',
                'created_at' => now()->subDays(5), // Created 5 days ago, still active
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'duration' => '7 days',
                'created_at' => now()->subDays(10), // Created 10 days ago, expired
            ];
        });
    }
}