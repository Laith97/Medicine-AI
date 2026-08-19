<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'api_endpoint' => $this->faker->optional()->url(),
            'api_key' => $this->faker->optional()->lexify('key-????????????????'),
            'supported_services' => $this->faker->optional()->randomElements(['office_visit', 'consultation', 'procedure', 'diagnostic', 'therapy'], 3),
            'contact_info' => ['email' => $this->faker->safeEmail(), 'phone' => $this->faker->phoneNumber()],
        ];
    }
}