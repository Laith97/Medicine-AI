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
        Schema::create('kpi_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_name');
            $table->unsignedInteger('hospital_key')->default(1);
            $table->json('thresholds'); // Stores threshold configuration as JSON
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['kpi_name', 'hospital_key']);
            $table->index(['hospital_key', 'is_active']);
        });

        // Insert default thresholds
        DB::table('kpi_thresholds')->insert([
            [
                'kpi_name' => 'patient_satisfaction_score',
                'hospital_key' => 1,
                'thresholds' => json_encode([
                    'critical_low' => 3.0,
                    'warning_low' => 3.5,
                    'warning_high' => 4.5,
                    'critical_high' => 5.0
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kpi_name' => 'appointment_show_up_rate',
                'hospital_key' => 1,
                'thresholds' => json_encode([
                    'critical_low' => 70.0,
                    'warning_low' => 75.0,
                    'warning_high' => 90.0,
                    'critical_high' => 95.0
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kpi_name' => 'average_wait_time_minutes',
                'hospital_key' => 1,
                'thresholds' => json_encode([
                    'critical_low' => 5.0,
                    'warning_low' => 10.0,
                    'warning_high' => 25.0,
                    'critical_high' => 30.0
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kpi_name' => 'total_revenue',
                'hospital_key' => 1,
                'thresholds' => json_encode([
                    'critical_low' => 50000,
                    'warning_low' => 75000,
                    'warning_high' => 200000,
                    'critical_high' => 250000
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kpi_name' => 'readmission_rate_30_days',
                'hospital_key' => 1,
                'thresholds' => json_encode([
                    'critical_low' => 5.0,
                    'warning_low' => 8.0,
                    'warning_high' => 15.0,
                    'critical_high' => 20.0
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kpi_name' => 'provider_utilization_rate',
                'hospital_key' => 1,
                'thresholds' => json_encode([
                    'critical_low' => 60.0,
                    'warning_low' => 70.0,
                    'warning_high' => 90.0,
                    'critical_high' => 95.0
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_thresholds');
    }
};
