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
        // Check if column already exists before adding it
        if (!Schema::hasColumn('users', 'primary_doctor_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('primary_doctor_id')->nullable()->after('role');
                $table->index(['role', 'primary_doctor_id']);
            });

            // Add foreign key constraint separately to avoid issues
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('primary_doctor_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_doctor_id']);
            $table->dropIndex(['role', 'primary_doctor_id']); // Drop the composite index
            $table->dropColumn('primary_doctor_id');
        });
    }
};
