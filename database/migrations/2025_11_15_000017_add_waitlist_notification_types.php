<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert waitlist notification types
        $waitlistTypes = [
            [
                'type' => 'waitlist_slot_available',
                'name' => 'Waitlist Slot Available',
                'description' => 'Notify when a slot becomes available for waitlisted appointments',
                'default_enabled' => true,
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-calendar-check',
                'color' => 'success',
                'category' => 'waitlist',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'waitlist_offer_expiring',
                'name' => 'Waitlist Offer Expiring',
                'description' => 'Notify when a waitlist offer is about to expire',
                'default_enabled' => true,
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-clock',
                'color' => 'warning',
                'category' => 'waitlist',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'waitlist_position_update',
                'name' => 'Waitlist Position Update',
                'description' => 'Notify when waitlist position changes',
                'default_enabled' => true,
                'default_channels' => json_encode(['database']),
                'icon' => 'fas fa-list-ol',
                'color' => 'info',
                'category' => 'waitlist',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'waitlist_auto_booked',
                'name' => 'Waitlist Auto-Booked',
                'description' => 'Notify when appointment is automatically booked from waitlist',
                'default_enabled' => true,
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-magic',
                'color' => 'success',
                'category' => 'waitlist',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'waitlist_expired',
                'name' => 'Waitlist Expired',
                'description' => 'Notify when waitlist entry expires without booking',
                'default_enabled' => true,
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-times-circle',
                'color' => 'danger',
                'category' => 'waitlist',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('notification_types')->insert($waitlistTypes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('notification_types')
            ->whereIn('type', [
                'waitlist_slot_available',
                'waitlist_offer_expiring',
                'waitlist_position_update',
                'waitlist_auto_booked',
                'waitlist_expired',
            ])
            ->delete();
    }
};
