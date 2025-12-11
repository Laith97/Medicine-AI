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
        Schema::create('kpi_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_id')->unique();
            $table->string('kpi_name');
            $table->enum('alert_level', ['critical', 'warning', 'info']);
            $table->decimal('current_value', 15, 2)->nullable();
            $table->string('threshold_breached')->nullable();
            $table->unsignedInteger('hospital_key')->default(1);
            $table->json('trend_context')->nullable();
            $table->json('recommended_actions')->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->index(['hospital_key', 'status']);
            $table->index(['kpi_name', 'alert_level']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_alerts');
    }
};
