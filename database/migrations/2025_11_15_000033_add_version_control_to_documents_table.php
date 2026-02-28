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
        Schema::table('documents', function (Blueprint $table) {
            $table->integer('current_version')->default(1)->after('compliance_data');
            $table->longText('content')->nullable()->after('current_version');
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->onDelete('set null')->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['current_version', 'content', 'template_id']);
        });
    }
};
