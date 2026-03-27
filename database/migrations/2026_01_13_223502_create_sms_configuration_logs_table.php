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
        Schema::create('sms_configuration_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('user_role', 50);
            $table->enum('model_type', ['doctor', 'hospital', 'system']);
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('old_provider', 50)->nullable();
            $table->string('new_provider', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_configuration_logs');
    }
};