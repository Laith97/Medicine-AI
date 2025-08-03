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
        Schema::table('diagnoses', function (Blueprint $table) {
            // Remove the type column since diagnoses will only be manual
            $table->dropColumn('type');
            // Remove ai_response column since AI responses will be in separate table
            $table->dropColumn('ai_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            // Restore the type column
            $table->enum('type', ['manual', 'ai'])->default('manual');
            // Restore ai_response column
            $table->text('ai_response')->nullable();
        });
    }
};
