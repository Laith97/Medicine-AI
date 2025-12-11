<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataWarehouse\KPICalculationService;
use App\Services\KPICacheService;
use App\Services\RealtimeStreamingService;
use App\Events\KPIUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RefreshRealtimeKPIs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:refresh-realtime
                            {--hospital=1 : Hospital key to refresh}
                            {--kpis= : Comma-separated list of specific KPIs to refresh}
                            {--force : Force refresh even if cache is valid}
                            {--broadcast : Broadcast updates via WebSocket}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh real-time KPI data and update caches';

    protected KPICalculationService $kpiService;
    protected KPICacheService $cacheService;
    protected RealtimeStreamingService $streamingService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        KPICalculationService $kpiService,
        KPICacheService $cacheService,
        RealtimeStreamingService $streamingService
    ) {
        parent::__construct();
        $this->kpiService = $kpiService;
        $this->cacheService = $cacheService;
        $this->streamingService = $streamingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hospitalKey = (int) $this->option('hospital');
        $specificKPIs = $this->option('kpis') ? explode(',', $this->option('kpis')) : null;
        $forceRefresh = $this->option('force');
        $shouldBroadcast = $this->option('broadcast');

        $this->info("🔄 Starting real-time KPI refresh for Hospital {$hospitalKey}");

        try {
            // Get KPIs to refresh
            $kpisToRefresh = $this->getKPIsToRefresh($specificKPIs);

            if (empty($kpisToRefresh)) {
                $this->warn('No KPIs found to refresh');
                return Command::SUCCESS;
            }

            $this->info("Found " . count($kpisToRefresh) . " KPIs to refresh");

            // Create progress bar
            $progressBar = $this->output->createProgressBar(count($kpisToRefresh));
            $progressBar->start();

            $refreshedCount = 0;
            $broadcastCount = 0;
            $errors = [];

            foreach ($kpisToRefresh as $kpiName) {
                try {
                    $result = $this->refreshSingleKPI($kpiName, $hospitalKey, $forceRefresh, $shouldBroadcast);

                    if ($result['refreshed']) {
                        $refreshedCount++;
                        if ($result['broadcast']) {
                            $broadcastCount++;
                        }
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'kpi' => $kpiName,
                        'error' => $e->getMessage()
                    ];

                    Log::error("Failed to refresh KPI: {$kpiName}", [
                        'hospital_key' => $hospitalKey,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Summary
            $this->info("✅ KPI refresh completed!");
            $this->line("📊 KPIs refreshed: {$refreshedCount}");
            $this->line("📡 Updates broadcast: {$broadcastCount}");

            if (!empty($errors)) {
                $this->warn("⚠️  Errors encountered: " . count($errors));
                foreach ($errors as $error) {
                    $this->line("  - {$error['kpi']}: {$error['error']}");
                }
            }

            // Performance metrics
            $this->displayPerformanceMetrics($hospitalKey);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Fatal error during KPI refresh: ' . $e->getMessage());
            Log::error('Fatal error in KPI refresh command', [
                'error' => $e->getMessage(),
                'hospital_key' => $hospitalKey,
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Get list of KPIs to refresh
     */
    protected function getKPIsToRefresh(?array $specificKPIs): array
    {
        if ($specificKPIs) {
            return $specificKPIs;
        }

        // Default real-time KPIs
        return [
            'patient_satisfaction_score',
            'appointment_show_up_rate',
            'average_wait_time_minutes',
            'total_revenue',
            'readmission_rate_30_days',
            'provider_utilization_rate',
            'no_show_appointments',
            'cancelled_appointments',
            'emergency_visits',
            'patient_volume_daily'
        ];
    }

    /**
     * Refresh a single KPI
     */
    protected function refreshSingleKPI(string $kpiName, int $hospitalKey, bool $forceRefresh, bool $shouldBroadcast): array
    {
        $result = [
            'refreshed' => false,
            'broadcast' => false
        ];

        // Check if refresh is needed (unless forced)
        if (!$forceRefresh && !$this->shouldRefreshKPI($kpiName, $hospitalKey)) {
            return $result;
        }

        // Calculate fresh KPI data
        $kpiData = $this->calculateKPI($kpiName, $hospitalKey);

        if (!$kpiData) {
            return $result;
        }

        // Cache the data
        $this->cacheService->cacheKPI($kpiName, $kpiData, $hospitalKey);

        // Broadcast update if requested
        if ($shouldBroadcast) {
            $this->broadcastKPIUpdate($kpiName, $kpiData, $hospitalKey);
            $result['broadcast'] = true;
        }

        $result['refreshed'] = true;

        return $result;
    }

    /**
     * Check if KPI needs refresh based on cache age
     */
    protected function shouldRefreshKPI(string $kpiName, int $hospitalKey): bool
    {
        $cached = $this->cacheService->getCachedKPI($kpiName, $hospitalKey);

        if (!$cached) {
            return true; // No cache, needs refresh
        }

        $cacheAge = now()->diffInMinutes(Carbon::parse($cached['cached_at']));

        // Refresh if cache is older than 5 minutes for real-time KPIs
        return $cacheAge > 5;
    }

    /**
     * Calculate KPI data
     */
    protected function calculateKPI(string $kpiName, int $hospitalKey): ?array
    {
        try {
            // Use the KPI calculation service
            $dailyKPIs = $this->kpiService->calculateDailyKPIs(null, $hospitalKey);

            if (isset($dailyKPIs[$kpiName])) {
                return [
                    'value' => $dailyKPIs[$kpiName],
                    'timestamp' => now()->toISOString(),
                    'period' => 'realtime',
                    'calculated_at' => now()
                ];
            }

            // If not in daily KPIs, try to calculate specifically
            return $this->calculateSpecificKPI($kpiName, $hospitalKey);

        } catch (\Exception $e) {
            Log::error("Error calculating KPI: {$kpiName}", [
                'hospital_key' => $hospitalKey,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Calculate specific KPIs that might not be in the standard daily calculation
     */
    protected function calculateSpecificKPI(string $kpiName, int $hospitalKey): ?array
    {
        // This could be extended to calculate specific KPIs
        // For now, return null
        return null;
    }

    /**
     * Broadcast KPI update
     */
    protected function broadcastKPIUpdate(string $kpiName, array $kpiData, int $hospitalKey): void
    {
        try {
            // Fire Laravel event (will be broadcast via listeners)
            event(new KPIUpdated($kpiName, $kpiData, $hospitalKey));

            // Also broadcast directly via streaming service
            $this->streamingService->broadcastKPIUpdate($kpiName, $kpiData, $hospitalKey);

        } catch (\Exception $e) {
            Log::error("Error broadcasting KPI update: {$kpiName}", [
                'hospital_key' => $hospitalKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display performance metrics
     */
    protected function displayPerformanceMetrics(int $hospitalKey): void
    {
        $cacheStats = $this->cacheService->getCacheStats();
        $streamingStats = $this->streamingService->getSubscriptionStats();

        $this->newLine();
        $this->info("📈 Performance Metrics:");
        $this->line("  Cache: {$cacheStats['total_cached_kpis']} KPIs cached");
        $this->line("  Subscriptions: {$streamingStats['total_active_subscriptions']} active");
        $this->line("  Memory: " . ($cacheStats['memory_usage'] ? number_format($cacheStats['memory_usage'] / 1024 / 1024, 2) . ' MB' : 'N/A'));
    }
}
