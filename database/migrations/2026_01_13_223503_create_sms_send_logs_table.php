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
        Schema::create('sms_send_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('recipient', 20);
            $table->text('message')->nullable();
            $table->string('context_type', 50)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('selected_provider', 50);
            $table->enum('selection_level', ['doctor', 'hospital', 'system', 'country', 'fallback']);
            $table->boolean('success');
            $table->boolean('fallback_used')->default(false);
            $table->string('fallback_provider', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_send_logs');
    }
};