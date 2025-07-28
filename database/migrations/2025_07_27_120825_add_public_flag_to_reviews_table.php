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
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_anonymous');
            }

            if (!Schema::hasColumn('reviews', 'case_study')) {
                $table->text('case_study')->nullable()->after('comment');
            }

            if (!Schema::hasColumn('reviews', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('is_public');
            }

            if (!Schema::hasIndex('reviews', ['doctor_id', 'is_public'])) {
                $table->index(['doctor_id', 'is_public']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasIndex('reviews', ['doctor_id', 'is_public'])) {
                $table->dropIndex(['doctor_id', 'is_public']);
            }

            $table->dropColumn(['is_public', 'case_study', 'approved_at']);
        });
    }
};
