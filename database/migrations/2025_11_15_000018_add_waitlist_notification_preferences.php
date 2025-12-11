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
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Waitlist notification preferences
            $table->boolean('waitlist_slot_available')->default(true)->after('system_alert');
            $table->boolean('waitlist_offer_expiring')->default(true)->after('waitlist_slot_available');
            $table->boolean('waitlist_position_update')->default(true)->after('waitlist_offer_expiring');
            $table->boolean('waitlist_auto_booked')->default(true)->after('waitlist_position_update');
            $table->boolean('waitlist_expired')->default(true)->after('waitlist_auto_booked');

            // Channel preferences per notification type (JSON for flexibility)
            $table->json('waitlist_channels')->nullable()->after('waitlist_expired')->comment('Channel preferences for waitlist notifications');

            // Frequency controls for waitlist notifications
            $table->enum('waitlist_frequency', ['immediate', 'hourly', 'daily', 'weekly'])->default('immediate')->after('waitlist_channels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'waitlist_slot_available',
                'waitlist_offer_expiring',
                'waitlist_position_update',
                'waitlist_auto_booked',
                'waitlist_expired',
                'waitlist_channels',
                'waitlist_frequency',
            ]);
        });
    }
};
