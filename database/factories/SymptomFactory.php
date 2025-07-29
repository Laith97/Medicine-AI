<?php

namespace Database\Factories;

use App\Models\Symptom;
use Illuminate\Database\Eloquent\Factories\Factory;

class SymptomFactory extends Factory
{
    protected $model = Symptom::class;

    public function definition(): array
    {
        $symptoms = [
            'Fever', 'Cough', 'Headache', 'Nausea', 'Vomiting', 'Diarrhea',
            'Chest Pain', 'Shortness of Breath', 'Fatigue', 'Dizziness',
            'Abdominal Pain', 'Back Pain', 'Joint Pain', 'Muscle Pain',
            'Sore Throat', 'Runny Nose', 'Congestion', 'Sneezing',
            'Rash', 'Itching', 'Swelling', 'Bruising'
        ];

        return [
            'name' => $this->faker->randomElement($symptoms),
        ];
    }
}
