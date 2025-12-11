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
        Schema::create('kpi_permissions', function (Blueprint $table) {
            $table->id('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->string('kpi_category', 100);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_export')->default(false);
            $table->timestamps();

            $table->foreign('role_id')->references('role_id')->on('analytics_roles');
            $table->unique(['role_id', 'kpi_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_permissions');
    }
};
