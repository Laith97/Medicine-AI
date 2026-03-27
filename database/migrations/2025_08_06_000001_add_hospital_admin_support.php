<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create hospitals table
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Update users table to support hospital admin role and hospital association
        Schema::table('users', function (Blueprint $table) {
            // Update role enum to include hospital_admin
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Only add hospital_id column if it doesn't exist to avoid duplicates
            if (!Schema::hasColumn('users', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('set null');
                $table->index('hospital_id');
            }

            // Update the role enum to include hospital_admin if needed
            if (!Schema::hasColumn('users', 'role') ||
                !collect(DB::select("SHOW COLUMNS FROM users WHERE Field='role'"))->first()->Type->contains('hospital_admin')) {
                $table->enum('role', ['patient', 'doctor', 'hospital_admin'])->default('patient');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if foreign key exists before dropping it
        $this->dropForeignKeyIfExists('users', 'users_hospital_id_foreign');

        // Check if index exists before dropping it
        $this->dropIndexIfExists('users', 'users_hospital_id_index');

        Schema::table('users', function (Blueprint $table) {
            // Drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'hospital_id')) {
                $columnsToDrop[] = 'hospital_id';
            }
            if (Schema::hasColumn('users', 'role')) {
                $columnsToDrop[] = 'role';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['patient', 'doctor', 'admin'])->default('patient');
            }
        });

        Schema::dropIfExists('hospitals');
    }

    /**
     * Drop a foreign key constraint if it exists
     */
    private function dropForeignKeyIfExists(string $table, string $foreignKeyName): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignKeyName}`");
        } catch (\Exception $e) {
            // Foreign key doesn't exist or another error occurred, continue silently
        }
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        } catch (\Exception $e) {
            // Index doesn't exist or another error occurred, continue silently
        }
    }
};
