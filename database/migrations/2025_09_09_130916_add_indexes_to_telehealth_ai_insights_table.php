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
        Schema::table('telehealth_ai_insights', function (Blueprint $table) {
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('created_at');
            $table->index(['appointment_id', 'emotion']);
            $table->index(['appointment_id', 'attention_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telehealth_ai_insights', function (Blueprint $table) {
            $table->dropIndex(['appointment_id']);
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['appointment_id', 'emotion']);
            $table->dropIndex(['appointment_id', 'attention_score']);
        });
    }
};
