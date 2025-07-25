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
        Schema::table('reviews', function (Blueprint $table) {
            // Make patient_id nullable for guest reviews
            $table->foreignId('patient_id')->nullable()->change();

            // Add verification fields for guest reviews (guest_name, guest_email, is_anonymous already exist)
            $table->string('verification_token')->nullable()->after('is_anonymous');
            $table->datetime('token_expires_at')->nullable()->after('verification_token');
            $table->boolean('is_verified')->default(false)->after('token_expires_at');

            // Add index for guest email lookups
            $table->index('guest_email');
            $table->index('verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'verification_token',
                'token_expires_at',
                'is_verified'
            ]);
            // Don't drop guest_name, guest_email, is_anonymous as they already existed

            // Make patient_id required again
            $table->foreignId('patient_id')->nullable(false)->change();
        });
    }
};
