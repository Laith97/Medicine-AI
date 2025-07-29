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
        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('duration')->nullable(); // in minutes
            $table->integer('fee')->nullable(); // in cents
            $table->text('notes')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('meeting_id')->nullable();
            $table->datetime('reminder_sent_at')->nullable();
            $table->datetime('follow_up_date')->nullable();
            $table->boolean('prescription_given')->default(false);
            $table->integer('visit_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'duration', 'fee', 'notes', 'payment_status', 'payment_intent_id',
                'meeting_id', 'reminder_sent_at', 'follow_up_date',
                'prescription_given', 'visit_number'
            ]);
        });
    }
};
