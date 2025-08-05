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
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_user_id']);
            $table->dropIndex(['parent_user_id', 'is_sub_user']);
            $table->dropColumn(['parent_user_id', 'sub_user_role', 'is_sub_user']);
        });
    }
};
