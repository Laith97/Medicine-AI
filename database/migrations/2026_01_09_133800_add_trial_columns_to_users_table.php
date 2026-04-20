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
        // Check if columns already exist before adding them
        if (!Schema::hasColumn('users', 'trial_ends_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable()->after('email_verified_at');
            });
            echo "Added trial_ends_at column\n";
        } else {
            echo "trial_ends_at column already exists\n";
        }

        if (!Schema::hasColumn('users', 'trial_used')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('trial_used')->default(false)->after('trial_ends_at');
            });
            echo "Added trial_used column\n";
        } else {
            echo "trial_used column already exists\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'trial_ends_at')) {
                $columnsToDrop[] = 'trial_ends_at';
            }
            if (Schema::hasColumn('users', 'trial_used')) {
                $columnsToDrop[] = 'trial_used';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};