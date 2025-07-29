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
        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                if (!Schema::hasColumn('doctors', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('doctors', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('is_verified');
                }
                if (!Schema::hasColumn('doctors', 'verification_notes')) {
                    $table->string('verification_notes')->nullable()->after('verified_at');
                }

                if (!Schema::hasIndex('doctors', ['is_verified'])) {
                    $table->index(['is_verified']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasIndex('doctors', ['is_verified'])) {
                $table->dropIndex(['is_verified']);
            }
            if (Schema::hasColumn('doctors', 'verification_notes')) {
                $table->dropColumn('verification_notes');
            }
            if (Schema::hasColumn('doctors', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
            if (Schema::hasColumn('doctors', 'is_verified')) {
                $table->dropColumn('is_verified');
            }
        });
    }
};
