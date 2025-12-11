<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\DataWarehouse\KPICalculationService;
use App\Services\KPICacheService;
use App\Services\RealtimeStreamingService;
use App\Events\KPIUpdated;
use Throwable;

class ProcessRealtimeKPIUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute
    public int $timeout = 300; // 5 minutes

    protected string $kpiName;
    protected int $hospitalKey;
    protected bool $forceRefresh;
    protected bool $broadcastUpdate;
    protected ?array $additionalData;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $kpiName,
        int $hospitalKey = 1,
        bool $forceRefresh = false,
        bool $broadcastUpdate = true,
        ?array $additionalData = null
    ) {
        $this->kpiName = $kpiName;
        $this->hospitalKey = $hospitalKey;
        $this->forceRefresh = $forceRefresh;
        $this->broadcastUpdate = $broadcastUpdate;
        $this->additionalData = $additionalData;

        $this->queue = 'realtime-kpi-updates';
        $this->delay = 0; // Process immediately unless specified
    }

    /**
     * Execute the job.
     */
    public function handle(
        KPICalculationService $kpiService,
        KPICacheService $cacheService,
        RealtimeStreamingService $streamingService
    ): void {
        Log::info("Processing real-time KPI update", [
            'kpi_name' => $this->kpiName,
            'hospital_key' => $this->hospitalKey,
            'job_id' => $this->job->getJobId()
        ]);

        try {
            // Check if refresh is needed
            if (!$this->forceRefresh && !$this->shouldRefreshKPI($cacheService)) {
                Log::info("KPI refresh skipped - cache still valid", [
                    'kpi_name' => $this->kpiName,
                    'hospital_key' => $this->hospitalKey
                ]);
                return;
            }

            // Calculate KPI data
            $kpiData = $this->calculateKPI($kpiService);

            if (!$kpiData) {
                Log::warning("Failed to calculate KPI data", [
                    'kpi_name' => $this->kpiName,
                    'hospital_key' => $this->hospitalKey
                ]);
                return;
            }

            // Cache the data
            $cacheService->cacheKPI($this->kpiName, $kpiData, $this->hospitalKey);

            // Broadcast update if requested
            if ($this->broadcastUpdate) {
                $this->broadcastUpdate($kpiData, $streamingService);
            }

            Log::info("KPI update processed successfully", [
                'kpi_name' => $this->kpiName,
                'hospital_key' => $this->hospitalKey,
                'value' => $kpiData['value'] ?? null,
                'broadcast' => $this->broadcastUpdate
            ]);

        } catch (Throwable $e) {
            Log::error("Failed to process KPI update", [
                'kpi_name' => $this->kpiName,
                'hospital_key' => $this->hospitalKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Check if KPI needs refresh
     */
    protected function shouldRefreshKPI(KPICacheService $cacheService): bool
    {
        $cached = $cacheService->getCachedKPI($this->kpiName, $this->hospitalKey);

        if (!$cached) {
            return true; // No cache, needs refresh
        }

        $cacheAge = now()->diffInMinutes(\Carbon\Carbon::parse($cached['cached_at']));

        // Refresh if cache is older than configured threshold
        $refreshThreshold = $this->getRefreshThreshold($this->kpiName);

        return $cacheAge > $refreshThreshold;
    }

    /**
     * Get refresh threshold for KPI (in minutes)
     */
    protected function getRefreshThreshold(string $kpiName): int
    {
        // Different KPIs may have different refresh frequencies
        $thresholds = [
            'patient_satisfaction_score' => 15, // 15 minutes
            'appointment_show_up_rate' => 10,   // 10 minutes
            'average_wait_time_minutes' => 5,   // 5 minutes (real-time)
            'total_revenue' => 30,              // 30 minutes
            'emergency_visits' => 2,            // 2 minutes (critical)
            'patient_volume_daily' => 60,       // 1 hour
        ];

        return $thresholds[$kpiName] ?? 15; // Default 15 minutes
    }

    /**
     * Calculate KPI data
     */
    protected function calculateKPI(KPICalculationService $kpiService): ?array
    {
        try {
            // Try to get from daily KPIs first
            $dailyKPIs = $kpiService->calculateDailyKPIs(null, $this->hospitalKey);

            if (isset($dailyKPIs[$this->kpiName])) {
                return [
                    'value' => $dailyKPIs[$this->kpiName],
                    'timestamp' => now()->toISOString(),
                    'period' => 'realtime',
                    'calculated_at' => now(),
                    'source' => 'daily_calculation'
                ];
            }

            // Try specific calculation methods
            return $this->calculateSpecificKPI($kpiService);

        } catch (Throwable $e) {
            Log::error("Error calculating KPI: {$this->kpiName}", [
                'hospital_key' => $this->hospitalKey,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Calculate specific KPIs that need custom logic
     */
    protected function calculateSpecificKPI(KPICalculationService $kpiService): ?array
    {
        // This can be extended for KPIs that need special calculation logic
        // For now, return null to indicate calculation not available
        return null;
    }

    /**
     * Broadcast KPI update
     */
    protected function broadcastUpdate(array $kpiData, RealtimeStreamingService $streamingService): void
    {
        try {
            // Fire Laravel event
            event(new KPIUpdated($this->kpiName, $kpiData, $this->hospitalKey));

            // Broadcast via streaming service
            $streamingService->broadcastKPIUpdate($this->kpiName, $kpiData, $this->hospitalKey);

        } catch (Throwable $e) {
            Log::error("Error broadcasting KPI update", [
                'kpi_name' => $this->kpiName,
                'hospital_key' => $this->hospitalKey,
                'error' => $e->getMessage()
            ]);

            // Don't throw here - broadcasting failure shouldn't fail the job
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error("KPI update job failed permanently", [
            'kpi_name' => $this->kpiName,
            'hospital_key' => $this->hospitalKey,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Could send notification about failed KPI update
        // Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new KPIRefreshFailed($this->kpiName, $this->hospitalKey, $exception));
    }

    /**
     * Get the queue name
     */
    public function queue(): string
    {
        return 'realtime-kpi-updates';
    }

    /**
     * Get the middleware
     */
    public function middleware(): array
    {
        return [
            // Add rate limiting or other middleware if needed
        ];
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'kpi-update',
            "kpi:{$this->kpiName}",
            "hospital:{$this->hospitalKey}",
            'realtime'
        ];
    }
}
