<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            if (!Schema::hasColumn('diagnoses', 'severity')) {
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->nullable()->after('diagnosis_text');
            }
            if (!Schema::hasColumn('diagnoses', 'requires_hospitalization')) {
                $table->boolean('requires_hospitalization')->default(false)->after('severity');
            }
        });
        // Ensure indexes
        try {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->index(['severity', 'requires_hospitalization'], 'diagnoses_severity_hosp_idx');
            });
        } catch (\Exception $e) {
            // index may already exist
        }
    }

    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            if (Schema::hasColumn('diagnoses', 'severity')) {
                $table->dropColumn('severity');
            }
            if (Schema::hasColumn('diagnoses', 'requires_hospitalization')) {
                $table->dropColumn('requires_hospitalization');
            }
        });
    }
};
