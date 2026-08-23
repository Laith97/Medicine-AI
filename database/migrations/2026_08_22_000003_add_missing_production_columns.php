<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_risk_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_risk_scores', 'prediction_method')) {
                $table->string('prediction_method')->default('ml')->after('hospitalization_risk');
            }
            if (!Schema::hasColumn('patient_risk_scores', 'confidence')) {
                $table->decimal('confidence', 3, 2)->nullable()->after('prediction_method');
            }
            if (!Schema::hasColumn('patient_risk_scores', 'model_version')) {
                $table->string('model_version')->nullable()->after('confidence');
            }
            if (!Schema::hasColumn('patient_risk_scores', 'feature_snapshot')) {
                $table->json('feature_snapshot')->nullable()->after('model_version');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'was_hospitalized')) {
                $table->boolean('was_hospitalized')->nullable()->after('follow_up_required');
            }
            if (!Schema::hasColumn('appointments', 'hospitalized_at')) {
                $table->timestamp('hospitalized_at')->nullable()->after('was_hospitalized');
            }
            if (!Schema::hasColumn('appointments', 'hospitalization_source')) {
                $table->string('hospitalization_source')->nullable()->after('hospitalized_at');
            }
        });
    }

    public function down(): void
    {
        // keep columns for safety
    }
};
