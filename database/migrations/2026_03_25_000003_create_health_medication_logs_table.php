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
        Schema::create('health_medication_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medication_schedule_id');
            $table->date('scheduled_date');
            $table->timestamp('taken_at')->nullable();
            $table->boolean('skipped')->default(false);
            $table->text('skip_reason')->nullable();
            $table->timestamps();

            $table->index('medication_schedule_id');
            $table->index('scheduled_date');
            $table->unique(['medication_schedule_id', 'scheduled_date'], 'health_med_log_unique');
            $table->foreign('medication_schedule_id')->references('id')->on('health_medication_schedules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_medication_logs');
    }
};
