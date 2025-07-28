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
            $table->timestamps();

            $table->index(['username', 'is_published']);
            $table->index('custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_landing_pages');
    }
};
