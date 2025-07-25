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
        Schema::create('openai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('request_type')->default('diagnosis'); // diagnosis, follow_up, summary
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost_estimate', 10, 6)->default(0); // Cost in dollars
            $table->string('model_used')->nullable(); // gpt-4, gpt-3.5-turbo, etc.
            $table->json('request_metadata')->nullable(); // Store additional request info
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('request_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openai_usages');
    }
};
