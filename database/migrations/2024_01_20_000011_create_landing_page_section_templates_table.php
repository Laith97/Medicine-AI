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
        Schema::create('landing_page_section_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // hero, about, services, testimonials, contact, custom, etc.
            $table->string('category')->default('general'); // medical, business, creative, etc.
            $table->text('description')->nullable();
            $table->json('default_config'); // Default configuration for the section
            $table->text('html_template'); // HTML template with placeholders
            $table->text('css_template')->nullable(); // CSS specific to this section
            $table->text('js_template')->nullable(); // JavaScript specific to this section
            $table->string('preview_image')->nullable(); // Preview image URL
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_section_templates');
    }
};
