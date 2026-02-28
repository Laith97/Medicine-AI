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
        Schema::create('time_dim', function (Blueprint $table) {
            $table->integer('time_key')->primary();
            $table->time('time');
            $table->integer('hour');
            $table->integer('minute');
            $table->integer('hour_of_day');
            $table->string('time_of_day', 20); // Morning, Afternoon, Evening, Night
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_dim');
    }
};
