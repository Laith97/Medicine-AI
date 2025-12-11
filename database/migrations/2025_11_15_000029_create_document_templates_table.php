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
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('template_type'); // 'claim', 'prescription', 'diagnosis', 'custom'
            $table->text('description')->nullable();
            $table->longText('template_content'); // HTML/template content
            $table->json('placeholders'); // Available placeholders in the template
            $table->json('compliance_rules'); // Compliance rules for this template
            $table->json('metadata')->nullable(); // Additional template metadata
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default template for type
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['template_type', 'is_active']);
            $table->index('is_default');

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
