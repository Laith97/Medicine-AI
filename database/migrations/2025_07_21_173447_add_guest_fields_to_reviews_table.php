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

            // Add guest fields if they don't exist
            if (!Schema::hasColumn('reviews', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('is_anonymous');
            }

            if (!Schema::hasColumn('reviews', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('guest_name');
            }

            // Add verification fields for guest reviews
            if (!Schema::hasColumn('reviews', 'verification_token')) {
                $table->string('verification_token')->nullable()->after('guest_email');
            }

            if (!Schema::hasColumn('reviews', 'token_expires_at')) {
                $table->datetime('token_expires_at')->nullable()->after('verification_token');
            }

            if (!Schema::hasColumn('reviews', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('token_expires_at');
            }

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
            // Drop indexes first
            $table->dropIndex(['guest_email']);
            $table->dropIndex(['verification_token']);

            // Drop the columns we added
            $table->dropColumn([
                'guest_name',
                'guest_email',
                'verification_token',
                'token_expires_at',
                'is_verified'
            ]);

            // Make patient_id required again
            $table->foreignId('patient_id')->nullable(false)->change();
        });
    }
};
