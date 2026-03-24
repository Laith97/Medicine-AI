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
        Schema::create('user_whats_app_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('provider_key'); // For different WhatsApp providers (e.g., twilio, graph_api)
            $table->json('provider_config'); // Configuration for the provider (keys, tokens, etc.)
            $table->boolean('is_active')->default(false);
            $table->boolean('use_admin_config')->default(true); // For doctors/hospitals to use admin config or their own
            $table->timestamps();

            // Ensure only one of user_id or hospital_id is set (mutually exclusive)
            $table->unique(['user_id'], 'user_whatsapp_config_user_unique');
            $table->unique(['hospital_id'], 'user_whatsapp_config_hospital_unique');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_whats_app_configurations');
    }
};
