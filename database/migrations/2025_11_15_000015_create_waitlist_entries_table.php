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
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waitlist_id')->constrained('waitlists')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->date('slot_date');
            $table->time('slot_time');
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('response_deadline')->nullable();
            $table->enum('status', ['pending', 'offered', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamps();

            // Indexes for performance
            $table->index(['waitlist_id', 'status']);
            $table->index(['appointment_id', 'status']);
            $table->index(['slot_date', 'slot_time']);
            $table->index(['status', 'response_deadline']);
            $table->index('offered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
