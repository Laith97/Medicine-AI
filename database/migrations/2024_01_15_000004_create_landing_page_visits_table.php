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
        // Check if the table already exists
        if (!Schema::hasTable('landing_page_visits')) {
            Schema::create('landing_page_visits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('doctor_id');
                $table->ipAddress('visitor_ip');
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
                $table->index(['visitor_ip']);
                $table->index(['visited_at']);
            });

            // Add foreign key constraint only if doctors table exists
            if (Schema::hasTable('doctors')) {
                Schema::table('landing_page_visits', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the table exists before trying to drop it
        if (Schema::hasTable('landing_page_visits')) {
            Schema::dropIfExists('landing_page_visits');
        }
    }
};
