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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('primary_doctor_id')->nullable()->after('role')->constrained('users')->onDelete('set null');
            $table->index(['role', 'primary_doctor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint using direct SQL statement
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `users` DROP FOREIGN KEY `users_primary_doctor_id_foreign`");
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue silently
        }

        Schema::table('users', function (Blueprint $table) {
            // Drop column if it exists
            if (Schema::hasColumn('users', 'primary_doctor_id')) {
                $table->dropColumn('primary_doctor_id');
            }
        });
    }
};
