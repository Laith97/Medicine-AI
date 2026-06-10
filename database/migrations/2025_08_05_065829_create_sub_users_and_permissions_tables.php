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
        // Add parent_user_id and sub_user_role to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_user_id')->nullable()->after('primary_doctor_id');
            $table->string('sub_user_role')->nullable()->after('parent_user_id'); // e.g., 'secretary', 'assistant', etc.
            $table->boolean('is_sub_user')->default(false)->after('sub_user_role');
            
            $table->foreign('parent_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['parent_user_id', 'is_sub_user']);
        });

        // Create permissions table for available system permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'dashboard', 'appointments', 'settings'
            $table->string('display_name'); // e.g., 'Dashboard', 'Appointments', 'Settings'
            $table->string('description')->nullable();
            $table->string('route_pattern')->nullable(); // e.g., 'dashboard', 'appointments.*', 'settings'
            $table->string('category')->default('general'); // e.g., 'core', 'medical', 'admin'
            $table->boolean('is_restricted')->default(false); // true for AI Assistant, Diagnoses, Voice Assistant
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Create user_permissions table for sub-user permissions
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('granted_by'); // The parent user who granted this permission
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['user_id', 'permission_id']);
            $table->index(['user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permissions');

        // Drop foreign key constraint and index using direct SQL statements
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `users` DROP FOREIGN KEY `users_parent_user_id_foreign`");
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue silently
        }

        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `users` DROP INDEX `users_parent_user_id_is_sub_user_index`");
        } catch (\Exception $e) {
            // Index doesn't exist, continue silently
        }

        Schema::table('users', function (Blueprint $table) {
            // Drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'parent_user_id')) {
                $columnsToDrop[] = 'parent_user_id';
            }
            if (Schema::hasColumn('users', 'sub_user_role')) {
                $columnsToDrop[] = 'sub_user_role';
            }
            if (Schema::hasColumn('users', 'is_sub_user')) {
                $columnsToDrop[] = 'is_sub_user';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
