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
        Schema::table('waitlist_patient_preferences', function (Blueprint $table) {
            // Geographic preferences
            $table->decimal('max_travel_distance', 8, 2)->nullable()->after('auto_accept_threshold')
                ->comment('Maximum distance patient is willing to travel (in km)');
            $table->decimal('preferred_location_lat', 10, 8)->nullable()->after('max_travel_distance')
                ->comment('Preferred location latitude for proximity calculations');
            $table->decimal('preferred_location_lng', 11, 8)->nullable()->after('preferred_location_lat')
                ->comment('Preferred location longitude for proximity calculations');

            // Additional patient information
            $table->string('emergency_contact')->nullable()->after('preferred_location_lng')
                ->comment('Emergency contact information');
            $table->text('special_requirements')->nullable()->after('emergency_contact')
                ->comment('Special requirements or accessibility needs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waitlist_patient_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'max_travel_distance',
                'preferred_location_lat',
                'preferred_location_lng',
                'emergency_contact',
                'special_requirements',
            ]);
        });
    }
};
