<?php

namespace Database\Factories;

use App\Models\DataMigrationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataMigrationTemplateFactory extends Factory
{
    protected $model = DataMigrationTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'entity_type' => 'patient',
            'field_mapping' => ['first_name' => 'first_name'],
            'validation_rules' => [],
            'transform_rules' => [],
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
