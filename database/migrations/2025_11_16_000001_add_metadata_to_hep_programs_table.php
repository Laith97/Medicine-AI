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
        Schema::table('hep_programs', function (Blueprint $table) {
            $table->json('personalization_metadata')->nullable()->after('precautions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hep_programs', function (Blueprint $table) {
            $table->dropColumn('personalization_metadata');
        });
    }
};
