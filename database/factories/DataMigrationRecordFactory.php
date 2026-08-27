<?php

namespace Database\Factories;

use App\Models\DataMigrationRecord;
use App\Models\DataMigration;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataMigrationRecordFactory extends Factory
{
    protected $model = DataMigrationRecord::class;

    public function definition(): array
    {
        return [
            'data_migration_id' => DataMigration::factory(),
            'entity_type' => fake()->randomElement(['patient', 'doctor', 'appointment']),
            'source_id' => fake()->unique()->randomNumber(5),
            'medcura_id' => null,
            'status' => fake()->randomElement(['pending', 'mapped', 'validated', 'imported', 'failed']),
            'source_data' => ['field' => 'value'],
            'transformed_data' => null,
            'validation_errors' => null,
            'error_message' => null,
        ];
    }
}
