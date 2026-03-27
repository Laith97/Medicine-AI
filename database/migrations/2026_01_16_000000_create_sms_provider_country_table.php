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
        Schema::create('sms_provider_country', function (Blueprint $table) {
            $table->id();
            $table->string('provider_key', 50)->index();
            $table->string('country_code', 2)->index();
            $table->string('country_name', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Add unique constraint to prevent duplicate country assignments
            $table->unique(['provider_key', 'country_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_provider_country');
    }
};
