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
            $table->string('stripe_customer_id')->nullable()->after('email');
            $table->string('current_plan')->default('free')->after('stripe_customer_id'); // free, basic, pro, enterprise
            $table->timestamp('subscription_ends_at')->nullable()->after('current_plan');
            $table->boolean('subscription_active')->default(false)->after('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'current_plan',
                'subscription_ends_at',
                'subscription_active'
            ]);
        });
    }
};
