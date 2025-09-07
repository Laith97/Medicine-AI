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
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->unique()->comment('Unique identifier for the notification type');
            $table->string('name', 100)->comment('Display name for the notification type');
            $table->text('description')->comment('Description of what this notification type is for');
            $table->boolean('default_enabled')->default(true)->comment('Whether this notification type is enabled by default');
            $table->json('default_channels')->comment('Default channels for this notification type');
            $table->string('icon', 50)->nullable()->comment('Icon class for UI display');
            $table->string('color', 20)->default('primary')->comment('Color class for UI display');
            $table->string('category', 50)->default('general')->comment('Category for grouping notifications');
            $table->timestamps();

            $table->index('type');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
