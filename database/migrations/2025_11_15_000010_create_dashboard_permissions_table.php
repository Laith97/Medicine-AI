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
        Schema::create('dashboard_permissions', function (Blueprint $table) {
            $table->id('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->string('dashboard_name', 100);
            $table->enum('access_level', ['none', 'basic', 'limited', 'full'])->default('none');
            $table->enum('data_scope', ['personal', 'team', 'department', 'hospital', 'system'])->default('personal');
            $table->timestamps();

            $table->foreign('role_id')->references('role_id')->on('analytics_roles');
            $table->unique(['role_id', 'dashboard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_permissions');
    }
};
