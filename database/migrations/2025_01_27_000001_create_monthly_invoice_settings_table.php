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
        Schema::create('monthly_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('monthly_amount', 10, 2)->default(0);
            $table->integer('grace_period_days')->default(7); // Grace period in days
            $table->integer('reminder_frequency_days')->default(3); // How often to send reminders
            $table->boolean('is_restricted')->default(false); // Whether user is currently restricted
            $table->json('restricted_pages')->nullable(); // Array of restricted page routes
            $table->text('restriction_message')->nullable(); // Custom restriction message
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->boolean('is_active')->default(true); // Whether monthly invoicing is active for this user
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_invoice_settings');
    }
};