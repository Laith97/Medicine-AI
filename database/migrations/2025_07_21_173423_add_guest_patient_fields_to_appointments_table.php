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
            // Make patient_id nullable for guest bookings
            $table->foreignId('patient_id')->nullable()->change();

            // Add guest patient fields
            $table->string('guest_name')->nullable()->after('patient_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_email');
            $table->date('guest_date_of_birth')->nullable()->after('guest_phone');
            $table->enum('guest_gender', ['male', 'female', 'other'])->nullable()->after('guest_date_of_birth');
            $table->text('guest_address')->nullable()->after('guest_gender');

            // Add verification fields for guest appointments
            $table->string('verification_token')->nullable()->after('guest_address');
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
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'guest_name',
                'guest_email',
                'guest_phone',
                'guest_date_of_birth',
                'guest_gender',
                'guest_address',
                'verification_token',
                'token_expires_at',
                'is_verified'
            ]);

            // Make patient_id required again
            $table->foreignId('patient_id')->nullable(false)->change();
        });
    }
};
