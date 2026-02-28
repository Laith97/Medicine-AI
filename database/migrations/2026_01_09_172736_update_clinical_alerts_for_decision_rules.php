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
        Schema::table('clinical_alerts', function (Blueprint $table) {
            $table->foreignId('rule_id')->nullable()->change();
            $table->foreignId('decision_rule_id')->nullable()->after('rule_id')->constrained('clinical_decision_rules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_alerts', function (Blueprint $table) {
            $table->dropForeign(['decision_rule_id']);
            $table->dropColumn('decision_rule_id');
            $table->foreignId('rule_id')->nullable(false)->change();
        });
    }
};
