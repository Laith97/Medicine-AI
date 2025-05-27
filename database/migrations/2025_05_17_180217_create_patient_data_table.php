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
        Schema::create('patient_data', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->string('gender');
            $table->decimal('weight', 8, 2)->nullable();  // for weight
            $table->decimal('height', 8, 2)->nullable();  // for height
            $table->decimal('temperature', 5, 2)->nullable();  // for temperature
            $table->string('blood_pressure')->nullable();  // for blood pressure
            $table->decimal('blood_sugar', 8, 2)->nullable();  // for blood sugar
            $table->json('symptoms')->nullable();
            $table->text('test_results')->nullable();
            $table->text('preliminary_diagnosis')->nullable();
            $table->text('ai_response')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_data');
    }
};
