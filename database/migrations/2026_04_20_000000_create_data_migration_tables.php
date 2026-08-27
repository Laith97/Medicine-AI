<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('entity_type', ['department', 'specialty', 'doctor', 'patient', 'appointment', 'diagnosis', 'prescription', 'treatment', 'allergy', 'insurance', 'user', 'setting'])->nullable();
            $table->enum('source_type', ['csv', 'excel', 'api', 'sql_database', 'hl7', 'fhir'])->nullable();
            $table->string('source_path')->nullable();
            $table->json('source_config')->nullable(); // API credentials, DB connection details
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('success_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->longText('error_log')->nullable();
            $table->json('field_mapping')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('incremental_sync')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('template_name')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('data_migration_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_migration_id')->constrained()->onDelete('cascade');
            $table->enum('entity_type', ['department', 'specialty', 'doctor', 'patient', 'appointment', 'diagnosis', 'prescription', 'treatment', 'allergy', 'insurance', 'user', 'setting']);
            $table->string('source_id')->nullable();
            $table->string('medcura_id')->nullable();
            $table->enum('status', ['pending', 'mapped', 'validated', 'imported', 'failed', 'skipped'])->default('pending');
            $table->json('source_data')->nullable();
            $table->json('transformed_data')->nullable();
            $table->json('validation_errors')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('data_migration_id_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_migration_id')->constrained()->onDelete('cascade');
            $table->string('source_type'); // patient, doctor, appointment, etc
            $table->string('source_id');
            $table->string('medcura_type'); // App\Models\Patient, App\Models\Doctor, etc
            $table->string('medcura_id');
            $table->boolean('is_duplicate')->default(false); // If source had duplicate
            $table->timestamps();
            $table->unique(['data_migration_id', 'source_type', 'source_id'], 'migration_id_mapping_unique');
        });

        Schema::create('data_migration_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('entity_type');
            $table->json('field_mapping');
            $table->json('validation_rules');
            $table->json('transform_rules');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_migration_id_mappings');
        Schema::dropIfExists('data_migration_records');
        Schema::dropIfExists('data_migration_templates');
        Schema::dropIfExists('data_migrations');
    }
};