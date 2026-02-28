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
        Schema::create('doctor_dim', function (Blueprint $table) {
            $table->integer('doctor_key')->primary();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name', 255);
            $table->string('specialty', 100)->nullable();
            $table->string('license_number', 100)->nullable();
            $table->integer('years_experience')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);
            $table->decimal('availability_score', 5, 2)->nullable(); // Percentage of time available
            $table->boolean('is_active')->default(true);
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_dim');
    }
};
