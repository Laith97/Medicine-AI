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
        // Check if table exists and columns don't already exist
        if (Schema::hasTable('doctor_landing_pages')) {
            Schema::table('doctor_landing_pages', function (Blueprint $table) {
                // Page builder specific fields
                if (!Schema::hasColumn('doctor_landing_pages', 'page_sections')) {
                    $table->json('page_sections')->nullable(); // Store custom sections with order
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'navbar_config')) {
                    $table->json('navbar_config')->nullable(); // Custom navbar links and styling
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'animations_config')) {
                    $table->json('animations_config')->nullable(); // Animation settings
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'custom_css')) {
                    $table->json('custom_css')->nullable(); // Custom CSS styles
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'fonts_config')) {
                    $table->json('fonts_config')->nullable(); // Font settings
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'background_config')) {
                    $table->json('background_config')->nullable(); // Background settings (images, gradients, etc.)
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'button_styles')) {
                    $table->json('button_styles')->nullable(); // Custom button styles
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'spacing_config')) {
                    $table->json('spacing_config')->nullable(); // Margin/padding configurations
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'enable_animations')) {
                    $table->boolean('enable_animations')->default(true); // Global animation toggle
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'page_layout')) {
                    $table->string('page_layout', 50)->default('default'); // Layout type
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('doctor_landing_pages')) {
            Schema::table('doctor_landing_pages', function (Blueprint $table) {
                $columnsToCheck = [
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
                ];
                
                foreach ($columnsToCheck as $column) {
                    if (Schema::hasColumn('doctor_landing_pages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
