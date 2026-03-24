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
        Schema::create('user_sms_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // For individual doctors
            $table->unsignedBigInteger('hospital_id')->nullable(); // For hospital-wide settings
            $table->string('provider_key'); // twilio, plivo, messagebird, unifonic, smsgatewayhub, etc.
            $table->json('provider_config')->nullable(); // Store provider-specific config as JSON (API keys, etc.)
            $table->boolean('is_active')->default(true); // Whether this provider is active for the user/hospital
            $table->boolean('use_admin_config')->default(false); // If true, use admin's configuration instead of the stored one
            $table->timestamps();

            // Either user_id or hospital_id must be set, but not both (for individual users vs hospital-wide settings)
            $table->unique(['user_id', 'provider_key'], 'user_provider_unique');
            $table->unique(['hospital_id', 'provider_key'], 'hospital_provider_unique');
            
            $table->index(['user_id', 'is_active']);
            $table->index(['hospital_id', 'is_active']);
            $table->index(['provider_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sms_configurations');
    }
};