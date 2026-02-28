<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataWarehouse\KPICalculationService;
use App\Services\DataWarehouse\KPIAlertService;
use App\Services\DataWarehouse\KPIAnalyticsService;
use Carbon\Carbon;

class ProcessKPICalculations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:process
                            {--date= : Specific date to process (Y-m-d format)}
                            {--hospital=1 : Hospital key to process}
                            {--alerts : Process alerts after calculations}
                            {--forecasts : Generate forecasts}
                            {--all : Process everything (KPIs, alerts, forecasts)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process KPI calculations, alerts, and analytics for the data warehouse';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $hospitalKey = (int) $this->option('hospital');
        $processAlerts = $this->option('alerts') || $this->option('all');
        $processForecasts = $this->option('forecasts') || $this->option('all');

        $this->info("Processing KPIs for date: {$date->format('Y-m-d')}, Hospital: {$hospitalKey}");

        // Initialize services
        $kpiService = app(KPICalculationService::class);
        $alertService = app(KPIAlertService::class);
        $analyticsService = app(KPIAnalyticsService::class);

        try {
            // Calculate daily KPIs
            $this->info('Calculating daily KPIs...');
            $dailyKPIs = $kpiService->calculateDailyKPIs($date, $hospitalKey);
            $this->info('✓ Daily KPIs calculated successfully');

            // Calculate monthly KPIs (if it's end of month)
            if ($date->isLastOfMonth()) {
                $this->info('Calculating monthly KPIs...');
                $kpiService->calculateMonthlyKPIs($date->year, $date->month, $hospitalKey);
                $this->info('✓ Monthly KPIs calculated successfully');
            }

            // Process alerts if requested
            if ($processAlerts) {
                $this->info('Checking for KPI alerts...');
                $alerts = $alertService->checkAllKPIsForAlerts($hospitalKey);
                if ($alerts['alerts_generated'] > 0) {
                    $this->warn("⚠️  Generated {$alerts['alerts_generated']} alerts");
                    foreach ($alerts['alerts'] as $alert) {
                        $this->line("  - {$alert['kpi_name']}: {$alert['alert_level']} ({$alert['current_value']})");
                    }
                } else {
                    $this->info('✓ No alerts generated');
                }
            }

            // Generate forecasts if requested
            if ($processForecasts) {
                $this->info('Generating KPI forecasts...');
                $forecasts = $analyticsService->getPredictiveInsights($hospitalKey);
                $this->info('✓ Forecasts generated successfully');
            }

            // Clear analytics cache to ensure fresh data
            $analyticsService->clearCache($hospitalKey);

            $this->info('🎉 KPI processing completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error processing KPIs: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
