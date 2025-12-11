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
        Schema::create('compliance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type'); // 'hipaa', 'evaluation', 'custom'
            $table->string('event_type'); // 'document_created', 'document_updated', 'document_submitted', etc.
            $table->string('model_type'); // The model class this rule applies to
            $table->json('conditions'); // JSON conditions for rule evaluation
            $table->json('actions'); // JSON actions to take when rule is triggered
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority rules are evaluated first
            $table->json('metadata')->nullable(); // Additional rule metadata
            $table->timestamps();

            $table->index(['rule_type', 'event_type']);
            $table->index(['model_type', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_rules');
    }
};
