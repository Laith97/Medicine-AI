<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the table already exists
        if (!Schema::hasTable('landing_page_visits')) {
            Schema::create('landing_page_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
                $table->string('visitor_ip');
                $table->text('user_agent')->nullable();
                $table->string('referrer_url')->nullable();
                $table->string('page_url')->nullable();
                $table->string('device_type')->nullable();
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->timestamp('visited_at');
                $table->timestamps();

                $table->index(['doctor_id', 'visited_at']);
            });
        }
    }

    public function down(): void
    {
        // Check if the table exists before trying to drop it
        if (Schema::hasTable('landing_page_visits')) {
            Schema::dropIfExists('landing_page_visits');
        }
    }
};
