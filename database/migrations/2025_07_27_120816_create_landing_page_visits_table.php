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
        if (!Schema::hasTable('landing_page_visits')) {
            Schema::create('landing_page_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
                $table->string('ip_address', 45); // IPv6 support
                $table->text('user_agent')->nullable();
                $table->string('referrer_url')->nullable();
                $table->string('page_url');
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->string('device_type')->nullable(); // mobile, desktop, tablet
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->integer('session_duration')->nullable(); // in seconds
                $table->timestamp('visited_at');
                $table->timestamps();

                $table->index(['doctor_id', 'visited_at']);
                $table->index(['ip_address']);
                $table->index(['visited_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('landing_page_visits')) {
            Schema::dropIfExists('landing_page_visits');
        }
    }
};
