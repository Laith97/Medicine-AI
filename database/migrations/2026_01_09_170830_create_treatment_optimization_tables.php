<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('treatment_optimization_tables')) {
            Schema::create('treatment_optimization_tables', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('treatment_optimization_recommendations')) {
            Schema::create('treatment_optimization_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('users');
                $table->foreignId('appointment_id')->nullable()->constrained('appointments');
                $table->string('ai_session_id')->nullable();

                // Treatment recommendations
                $table->json('recommended_medications')->nullable();
                $table->json('alternative_medications')->nullable();
                $table->json('dosage_adjustments')->nullable();
                $table->json('timing_optimizations')->nullable();

                // Predictive analytics
                $table->json('outcome_predictions')->nullable();
                $table->json('risk_assessment')->nullable();
                $table->json('adherence_factors')->nullable();

                // Optimization scores
                $table->decimal('effectiveness_score', 3, 2)->nullable();
                $table->decimal('safety_score', 3, 2)->nullable();
                $table->decimal('cost_efficiency_score', 3, 2)->nullable();

                // Integration
                $table->boolean('validated_by_doctor')->default(false);
                $table->timestamp('validated_at')->nullable();
                $table->boolean('implemented')->default(false);

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('treatment_pathways')) {
            Schema::create('treatment_pathways', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('condition_code', 50)->nullable();
                $table->enum('pathway_type', ['standard', 'personalized', 'protocol_based']);
                $table->text('description')->nullable();
                $table->json('steps')->nullable();
                $table->json('success_rates')->nullable();
                $table->json('contraindications')->nullable();
                $table->enum('evidence_level', ['A', 'B', 'C'])->nullable();
                $table->string('version', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('patient_treatment_responses')) {
            Schema::create('patient_treatment_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('users');
                $table->string('medication_name');
                $table->string('dosage', 100)->nullable();
                $table->string('duration', 100)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('outcome', 50)->nullable(); // 'effective', 'partially_effective', 'ineffective', 'adverse_reaction'
                $table->decimal('effectiveness_score', 3, 2)->nullable();
                $table->json('side_effects')->nullable();
                $table->decimal('adherence_rate', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('clinical_decision_rules')) {
            Schema::create('clinical_decision_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('trigger_conditions')->nullable();
                $table->enum('action_type', ['recommend', 'alert', 'restrict', 'escalate']);
                $table->json('action_payload')->nullable();
                $table->integer('priority')->default(5);
                $table->boolean('is_active')->default(true);
                $table->text('evidence_reference')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_decision_rules');
        Schema::dropIfExists('patient_treatment_responses');
        Schema::dropIfExists('treatment_pathways');
        Schema::dropIfExists('treatment_optimization_recommendations');
    }
};
