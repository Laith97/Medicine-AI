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
        Schema::create('feature_permissions', function (Blueprint $table) {
            $table->id('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->string('feature_name', 100);
            $table->boolean('can_access')->default(false);
            $table->timestamps();

            $table->foreign('role_id')->references('role_id')->on('analytics_roles');
            $table->unique(['role_id', 'feature_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_permissions');
    }
};
