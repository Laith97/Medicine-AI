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
        Schema::create('compensation_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->string('name'); // Plan name (e.g., "Base Salary + Commission")
            $table->enum('plan_type', ['salary', 'hourly', 'commission', 'hybrid'])->default('salary');
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->decimal('base_hourly_rate', 10, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->nullable(); // Percentage of revenue
            $table->decimal('bonus_threshold', 10, 2)->nullable(); // Revenue threshold for bonus
            $table->decimal('bonus_percentage', 5, 2)->nullable(); // Bonus percentage when threshold reached
            $table->json('cpt_commission_rates')->nullable(); // Different rates per CPT code category
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index('doctor_id');
            $table->index('is_active');
        });

        Schema::create('provider_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('compensation_plan_id')->nullable()->constrained('compensation_plans')->onDelete('set null');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('claim_id')->nullable()->constrained('claims')->onDelete('set null');
            $table->enum('compensation_type', ['salary', 'hourly', 'commission', 'bonus', 'adjustment'])->default('salary');
            $table->decimal('amount', 10, 2);
            $table->decimal('hours_worked', 6, 2)->nullable();
            $table->decimal('base_amount', 10, 2)->nullable(); // Base portion for commission
            $table->decimal('commission_rate', 5, 4)->nullable(); // Applied commission rate
            $table->string('description')->nullable();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->string('payroll_reference')->nullable();
            $table->timestamps();

            $table->index('doctor_id');
            $table->index(['pay_period_start', 'pay_period_end']);
            $table->index('status');
        });

        Schema::create('provider_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('compensation_plan_id')->nullable()->constrained('compensation_plans')->onDelete('set null');
            $table->string('bonus_type'); // performance, retention, referral, holiday
            $table->decimal('amount', 10, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->date('earned_date');
            $table->date('paid_at')->nullable();
            $table->string('payroll_reference')->nullable();
            $table->timestamps();

            $table->index('doctor_id');
            $table->index('bonus_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_bonuses');
        Schema::dropIfExists('provider_compensations');
        Schema::dropIfExists('compensation_plans');
    }
};
