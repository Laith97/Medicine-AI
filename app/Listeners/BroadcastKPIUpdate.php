<?php

namespace App\Listeners;

use App\Events\KPIUpdated;
use App\Services\RealtimeStreamingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class BroadcastKPIUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    protected RealtimeStreamingService $streamingService;

    /**
     * Create the event listener.
     */
    public function __construct(RealtimeStreamingService $streamingService)
    {
        $this->streamingService = $streamingService;
    }

    /**
     * Handle the event.
     */
    public function handle(KPIUpdated $event): void
    {
        try {
            // Broadcast the KPI update via WebSocket
            $success = $this->streamingService->broadcastKPIUpdate(
                $event->kpiName,
                $event->data,
                $event->hospitalKey
            );

            if ($success) {
                Log::info('KPI update broadcast successfully', [
                    'kpi_name' => $event->kpiName,
                    'hospital_key' => $event->hospitalKey,
                    'event_id' => $event->eventId
                ]);
            } else {
                Log::warning('Failed to broadcast KPI update', [
                    'kpi_name' => $event->kpiName,
                    'hospital_key' => $event->hospitalKey,
                    'event_id' => $event->eventId
                ]);
            }

            // Update cache with latest KPI data
            $this->updateKPICache($event);

        } catch (\Exception $e) {
            Log::error('Error broadcasting KPI update', [
                'kpi_name' => $event->kpiName,
                'hospital_key' => $event->hospitalKey,
                'event_id' => $event->eventId,
                'error' => $e->getMessage()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Update KPI cache with latest data
     */
    protected function updateKPICache(KPIUpdated $event): void
    {
        $cacheKey = "kpi:{$event->hospitalKey}:{$event->kpiName}:latest";

        $cacheData = [
            'value' => $event->data['value'] ?? null,
            'change' => $event->data['change'] ?? null,
            'trend' => $event->data['trend'] ?? null,
            'timestamp' => $event->data['timestamp'] ?? now()->toISOString(),
            'updated_at' => now()
        ];

        \Illuminate\Support\Facades\Cache::put($cacheKey, $cacheData, 3600); // 1 hour TTL

        // Also update dashboard cache if this KPI is used in dashboards
        $this->updateDashboardCache($event);
    }

    /**
     * Update dashboard cache that uses this KPI
     */
    protected function updateDashboardCache(KPIUpdated $event): void
    {
        // Get dashboards that use this KPI
        $dashboardKPIs = \Illuminate\Support\Facades\Cache::get('dashboard_kpi_mappings', []);

        foreach ($dashboardKPIs as $dashboardId => $kpis) {
            if (in_array($event->kpiName, $kpis)) {
                $cacheKey = "dashboard:{$event->hospitalKey}:{$dashboardId}:data";
                $dashboardData = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

                // Update the specific KPI in dashboard data
                $dashboardData['kpis'][$event->kpiName] = [
                    'value' => $event->data['value'] ?? null,
                    'change' => $event->data['change'] ?? null,
                    'trend' => $event->data['trend'] ?? null,
                    'last_updated' => now()
                ];

                $dashboardData['last_updated'] = now();

                \Illuminate\Support\Facades\Cache::put($cacheKey, $dashboardData, 1800); // 30 min TTL
            }
        }
    }

    /**
     * Get the queue name for the job
     */
    public function viaQueue(): string
    {
        return 'realtime-updates';
    }

    /**
     * Get the middleware for the job
     */
    public function middleware(): array
    {
        return [
            // Add rate limiting or other middleware if needed
        ];
    }
}
