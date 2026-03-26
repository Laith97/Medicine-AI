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
        Schema::create('google_review_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->text('generated_response');
            $table->enum('tone', ['professional', 'friendly', 'empathetic', 'formal'])->default('professional');
            $table->enum('status', ['draft', 'approved', 'posted', 'rejected'])->default('draft');
            $table->text('approved_response')->nullable(); // Final approved response
            $table->boolean('was_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('review_id');
            $table->index('doctor_id');
            $table->index('status');
        });

        Schema::create('review_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->boolean('auto_generate_enabled')->default(false);
            $table->boolean('auto_post_enabled')->default(false); // Requires manual approval if false
            $table->enum('default_tone', ['professional', 'friendly', 'empathetic', 'formal'])->default('professional');
            $table->json('custom_instructions')->nullable(); // Doctor's preferences for response style
            $table->integer('min_rating_for_auto_response')->default(4); // Only auto-respond to 4+ stars
            $table->boolean('respond_to_negative')->default(true); // Respond to negative reviews
            $table->timestamps();

            $table->unique('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_ai_settings');
        Schema::dropIfExists('google_review_responses');
    }
};
