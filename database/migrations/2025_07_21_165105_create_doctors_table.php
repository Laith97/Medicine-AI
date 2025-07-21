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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            $table->string('license_number')->unique();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_image')->nullable();
            $table->json('languages')->nullable(); // ['English', 'Spanish', 'French']
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('US');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('consultation_fee')->default(0); // in cents
            $table->integer('appointment_duration')->default(30); // in minutes
            $table->boolean('auto_approve_appointments')->default(false);
            $table->boolean('allow_cancellation')->default(true);
            $table->boolean('allow_rescheduling')->default(true);
            $table->integer('cancellation_hours')->default(24); // hours before appointment
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
