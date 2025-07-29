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
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('doctor_id');
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->ipAddress('visitor_ip');
            $table->text('visitor_user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'is_active']);
            $table->index(['session_id']);
            $table->index(['last_activity_at']);
        });

        // Add foreign key constraint only if doctors table exists
        if (Schema::hasTable('doctors')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
