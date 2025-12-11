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
        Schema::create('analytics_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 100); // view_dashboard, export_data, modify_permissions, etc.
            $table->string('resource_type', 50); // dashboard, kpi, report, etc.
            $table->string('resource_name', 100); // executive_dashboard, revenue_kpi, etc.
            $table->json('metadata')->nullable(); // Additional context like filters, date ranges, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();

            $table->index(['user_id', 'accessed_at']);
            $table->index(['action', 'resource_type']);
            $table->index('accessed_at');

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_audit_log');
    }
};
