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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->json('seo_meta')->nullable(); // For keywords and other SEO data
            $table->integer('views_count')->default(0);
            $table->string('reading_time')->nullable(); // e.g., "5 min read"
            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctor_id', 'is_published']);
            $table->index(['slug']);
            $table->index(['published_at']);
        });

        // Add foreign key constraint only if doctors table exists
        if (Schema::hasTable('doctors')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
