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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->longText('content')->nullable(); // For storing content directly (small documents)
            $table->string('content_hash'); // SHA256 hash of content for integrity
            $table->text('changes_summary'); // Summary of what changed
            $table->text('change_reason'); // Reason for the change
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->json('metadata')->nullable(); // Additional metadata about the version
            $table->json('compliance_data')->nullable(); // Compliance validation data
            $table->string('storage_path')->nullable(); // Path if content stored externally
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['document_id', 'version_number']);
            $table->index(['document_id', 'created_at']);
            $table->index('changed_by');
            $table->index('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
