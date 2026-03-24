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
        Schema::table('users', function (Blueprint $table) {
            // Remove old subscription system columns
            $table->dropColumn([
                'current_plan',
                'subscription_active', 
                'subscription_ends_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore old columns if they don't already exist
            if (!Schema::hasColumn('users', 'current_plan')) {
                $table->string('current_plan')->default('free')->after('stripe_customer_id');
            }
            if (!Schema::hasColumn('users', 'subscription_active')) {
                $table->boolean('subscription_active')->default(false)->after('current_plan');
            }
            if (!Schema::hasColumn('users', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('subscription_active');
            }
        });
    }
};
