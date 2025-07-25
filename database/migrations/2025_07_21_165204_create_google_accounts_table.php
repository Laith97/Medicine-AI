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
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('google_account_id');
            $table->string('business_account_id')->nullable();
            $table->string('location_id')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->datetime('token_expires_at');
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->datetime('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'google_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_accounts');
    }
};
