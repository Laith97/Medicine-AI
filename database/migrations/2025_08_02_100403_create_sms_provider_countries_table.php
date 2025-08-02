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
        Schema::create('sms_provider_countries', function (Blueprint $table) {
            $table->id();
            $table->string('provider_key'); // twilio, plivo, messagebird, unifonic, smsgatewayhub
            $table->string('country_code', 2); // ISO 3166-1 alpha-2 country codes (JO, SA, US, etc.)
            $table->string('country_name'); // Human readable country name
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure each provider can only be assigned to a country once
            $table->unique(['provider_key', 'country_code']);

            // Index for faster lookups
            $table->index(['country_code', 'is_active']);
            $table->index(['provider_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_provider_countries');
    }
};
