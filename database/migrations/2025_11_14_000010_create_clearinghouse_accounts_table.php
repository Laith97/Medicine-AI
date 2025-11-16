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
        Schema::create('clearinghouse_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // e.g., 'availity', 'change_healthcare', 'trizetto'
            $table->string('name'); // Human readable name for the account
            $table->json('credentials'); // Encrypted credentials (API keys, usernames, passwords)
            $table->json('settings')->nullable(); // Additional configuration settings
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'name']); // Prevent duplicate provider/name combinations
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearinghouse_accounts');
    }
};
