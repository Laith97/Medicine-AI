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
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->comment('Provider message ID');
            $table->string('provider')->comment('twilio or meta');
            $table->string('status')->default('pending');
            $table->string('from')->nullable()->comment('Sender phone number');
            $table->string('to')->nullable()->comment('Recipient phone number');
            $table->text('body')->nullable()->comment('Message content');
            $table->json('metadata')->nullable()->comment('Additional provider metadata');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Associated user if known');
            $table->unsignedBigInteger('hospital_id')->nullable()->comment('Associated hospital if known');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['message_id']);
            $table->index(['status']);
            $table->index(['from']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};
