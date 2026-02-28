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
        Schema::create('dim_date', function (Blueprint $table) {
            $table->integer('date_key')->primary();
            $table->date('date')->unique();
            $table->integer('year');
            $table->integer('quarter');
            $table->integer('month');
            $table->string('month_name', 20);
            $table->integer('week_of_year');
            $table->integer('day_of_week');
            $table->string('day_name', 20);
            $table->boolean('is_weekend');
            $table->boolean('is_holiday')->default(false);
            $table->integer('fiscal_year')->nullable();
            $table->integer('fiscal_quarter')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dim_date');
    }
};
