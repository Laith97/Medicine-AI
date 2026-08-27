<?php

namespace Database\Factories;

use App\Models\DataMigration;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataMigrationFactory extends Factory
{
    protected $model = DataMigration::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'failed', 'cancelled']),
            'entity_type' => fake()->randomElement(['patient', 'doctor', 'appointment', 'diagnosis']),
            'source_type' => fake()->randomElement(['csv', 'excel']),
            'source_path' => 'data-migrations/test.csv',
            'source_config' => null,
            'total_records' => fake()->numberBetween(10, 100),
            'processed_records' => fake()->numberBetween(0, 10),
            'success_records' => fake()->numberBetween(0, 5),
            'failed_records' => fake()->numberBetween(0, 5),
            'error_log' => null,
            'field_mapping' => null,
            'validation_rules' => null,
            'incremental_sync' => false,
            'last_sync_at' => null,
            'template_name' => null,
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
