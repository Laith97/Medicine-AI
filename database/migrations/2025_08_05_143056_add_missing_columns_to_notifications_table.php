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
        Schema::table('notifications', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('notifications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('notifications', 'message')) {
                $table->text('message')->nullable()->after('title');
            }
            if (!Schema::hasColumn('notifications', 'icon')) {
                $table->string('icon')->nullable()->after('message');
            }
            if (!Schema::hasColumn('notifications', 'link')) {
                $table->string('link')->nullable()->after('icon');
            }
            if (!Schema::hasColumn('notifications', 'link_text')) {
                $table->string('link_text')->nullable()->after('link');
            }
            if (!Schema::hasColumn('notifications', 'related_type')) {
                $table->string('related_type')->nullable()->after('link_text');
            }
            if (!Schema::hasColumn('notifications', 'related_id')) {
                $table->unsignedBigInteger('related_id')->nullable()->after('related_type');
            }
        });

        // Add foreign key constraint only if user_id column exists
        if (Schema::hasColumn('notifications', 'user_id')) {
            // Check if foreign key already exists by querying the database
            $foreignKeyExists = \DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'notifications'
                AND COLUMN_NAME = 'user_id'
                AND REFERENCED_TABLE_NAME = 'users'
            ");

            if ($foreignKeyExists[0]->count == 0) {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop foreign key if it exists
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist, ignore the error
            }

            // Drop columns if they exist
            $columnsToCheck = ['user_id', 'title', 'message', 'icon', 'link', 'link_text', 'related_type', 'related_id'];
            $columnsToDrop = [];

            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
