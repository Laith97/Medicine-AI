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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->json('drug_interaction_warnings')->nullable()->after('ai_risk_flags');
            $table->json('drug_interaction_errors')->nullable()->after('drug_interaction_warnings');
            $table->enum('drug_interaction_severity', ['low', 'medium', 'high'])->default('low')->after('drug_interaction_errors');
            $table->timestamp('drug_interaction_validated_at')->nullable()->after('drug_interaction_severity');
            $table->boolean('force_override')->default(false)->after('drug_interaction_validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'drug_interaction_warnings',
                'drug_interaction_errors',
                'drug_interaction_severity',
                'drug_interaction_validated_at',
                'force_override'
            ]);
        });
    }
};
