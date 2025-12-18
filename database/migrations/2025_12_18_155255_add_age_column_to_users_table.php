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
        // Add the age column if it doesn't exist
        if (!Schema::hasColumn('users', 'age')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('age')->nullable()->after('date_of_birth');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'age')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('age');
            });
        }
    }
};
