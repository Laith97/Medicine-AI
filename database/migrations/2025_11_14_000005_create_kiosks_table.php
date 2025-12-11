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
        Schema::create('kiosks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('serial_number')->unique();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('configuration')->nullable(); // Store kiosk-specific settings
            $table->timestamp('last_ping')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiosks');
    }
};
