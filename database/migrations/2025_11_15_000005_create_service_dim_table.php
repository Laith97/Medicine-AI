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
        Schema::create('service_dim', function (Blueprint $table) {
            $table->integer('service_key')->primary();
            $table->unsignedBigInteger('service_id');
            $table->string('service_name', 255);
            $table->string('service_category', 100)->nullable();
            $table->string('cpt_code', 20)->nullable();
            $table->string('icd_code', 20)->nullable();
            $table->integer('average_duration_minutes')->nullable();
            $table->decimal('average_cost', 10, 2)->nullable();
            $table->decimal('reimbursement_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_dim');
    }
};
