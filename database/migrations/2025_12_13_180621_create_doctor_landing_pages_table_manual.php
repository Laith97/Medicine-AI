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
        // Create the doctor_landing_pages table if it doesn't exist
        if (!Schema::hasTable('doctor_landing_pages')) {
            Schema::create('doctor_landing_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
                $table->string('username')->unique();
                $table->string('template', 50)->default('template1');
                $table->string('page_title')->nullable();
                $table->text('page_description')->nullable();
                $table->string('tagline')->nullable();
                $table->string('hero_image')->nullable();
                $table->text('about_text')->nullable();
                $table->json('colors')->nullable(); // Store color customizations
                $table->json('section_visibility')->nullable(); // Store which sections are visible
                $table->boolean('is_published')->default(false);
                $table->string('custom_domain')->nullable();
                $table->boolean('subdomain_enabled')->default(false);
                $table->json('seo_settings')->nullable(); // Store SEO meta tags
                $table->string('default_language', 5)->default('en');
                $table->json('translations')->nullable();
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
                $table->timestamps();

                $table->index(['username', 'is_published']);
                $table->index('custom_domain');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_landing_pages');
    }
};