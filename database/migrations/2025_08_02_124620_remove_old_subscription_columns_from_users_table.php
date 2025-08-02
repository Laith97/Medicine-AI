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
            // Restore old columns if needed for rollback
            $table->string('current_plan')->default('free')->after('stripe_customer_id');
            $table->boolean('subscription_active')->default(false)->after('current_plan');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_active');
        });
    }
};
