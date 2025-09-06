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
            // Drop the foreign key constraint if it exists
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist, ignore the error
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Drop the custom columns we added if they exist
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('title')->nullable()->after('user_id');
            $table->text('message')->nullable()->after('title');
            $table->string('icon')->nullable()->after('message');
            $table->string('link')->nullable()->after('icon');
            $table->string('link_text')->nullable()->after('link');
            $table->string('related_type')->nullable()->after('link_text');
            $table->unsignedBigInteger('related_id')->nullable()->after('related_type');

            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
