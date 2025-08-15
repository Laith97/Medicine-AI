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
            $table->enum('role', ['patient', 'doctor', 'hospital_admin'])->default('patient')->after('password');
            $table->unsignedBigInteger('hospital_id')->nullable()->after('primary_doctor_id');
            
            $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('set null');
            $table->index('hospital_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropIndex(['hospital_id']);
            $table->dropColumn(['hospital_id']);
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['patient', 'doctor', 'admin'])->default('patient')->after('password');
        });

        Schema::dropIfExists('hospitals');
    }
};