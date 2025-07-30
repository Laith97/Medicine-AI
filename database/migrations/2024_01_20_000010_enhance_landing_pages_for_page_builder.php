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
        Schema::table('doctor_landing_pages', function (Blueprint $table) {
            // Page builder specific fields
            $table->json('page_sections')->nullable(); // Store custom sections with order
            $table->json('navbar_config')->nullable(); // Custom navbar links and styling
            $table->json('animations_config')->nullable(); // Animation settings
            $table->json('custom_css')->nullable(); // Custom CSS styles
            $table->json('fonts_config')->nullable(); // Font settings
            $table->json('background_config')->nullable(); // Background settings (images, gradients, etc.)
            $table->json('button_styles')->nullable(); // Custom button styles
            $table->json('spacing_config')->nullable(); // Margin/padding configurations
            $table->boolean('enable_animations')->default(true); // Global animation toggle
            $table->string('page_layout', 50)->default('default'); // Layout type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'page_sections',
                'navbar_config',
                'animations_config',
                'custom_css',
                'fonts_config',
                'background_config',
                'button_styles',
                'spacing_config',
                'enable_animations',
                'page_layout'
            ]);
        });
    }
};
