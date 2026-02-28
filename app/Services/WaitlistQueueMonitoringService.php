<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use Carbon\Carbon;

class WaitlistQueueMonitoringService
{
    /**
     * Get comprehensive queue health metrics
     */
    public function getQueueHealthMetrics(): array
    {
        return [
            'queue_status' => $this->getQueueStatus(),
            'job_counts' => $this->getJobCounts(),
            'processing_times' => $this->getProcessingTimes(),
            'failure_rates' => $this->getFailureRates(),
            'waitlist_backlog' => $this->getWaitlistBacklog(),
            'performance_indicators' => $this->getPerformanceIndicators(),
        ];
    }

    /**
     * Get status of all waitlist queues
     */
    private function getQueueStatus(): array
    {
        $queues = ['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'];

        $status = [];
        foreach ($queues as $queue) {
            $status[$queue] = [
                'active' => $this->isQueueActive($queue),
                'workers' => $this->getActiveWorkers($queue),
                'last_processed' => $this->getLastProcessedTime($queue),
                'stalled_jobs' => $this->getStalledJobsCount($queue),
            ];
        }

        return $status;
    }

    /**
     * Get job counts for each queue
     */
    private function getJobCounts(): array
    {
        try {
            $counts = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->whereIn('queue', ['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'])
                ->groupBy('queue')
                ->pluck('count', 'queue')
                ->toArray();

            // Ensure all queues are represented
            $allQueues = ['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'];
            foreach ($allQueues as $queue) {
                if (!isset($counts[$queue])) {
                    $counts[$queue] = 0;
                }
            }

            return $counts;
        } catch (\Exception $e) {
            Log::error('Failed to get job counts', ['error' => $e->getMessage()]);
            return array_fill_keys(['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'], 0);
        }
    }

    /**
     * Get processing time metrics
     */
    private function getProcessingTimes(): array
    {
        $times = [];

        // Get average processing times from recent jobs
        try {
            $recentJobs = DB::table('jobs')
                ->whereIn('queue', ['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'])
                ->where('created_at', '>=', now()->subHours(24))
                ->select('queue', 'created_at')
                ->get()
                ->groupBy('queue');

            foreach ($recentJobs as $queue => $jobs) {
                $processingTimes = [];
                foreach ($jobs as $job) {
                    // Estimate processing time (in a real scenario, you'd track actual start/end times)
                    $processingTimes[] = rand(1, 300); // Mock data for now
                }

                $times[$queue] = [
                    'average' => count($processingTimes) > 0 ? array_sum($processingTimes) / count($processingTimes) : 0,
                    'p95' => $this->calculatePercentile($processingTimes, 95),
                    'p99' => $this->calculatePercentile($processingTimes, 99),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get processing times', ['error' => $e->getMessage()]);
        }

        return $times;
    }

    /**
     * Get failure rates for queues
     */
    private function getFailureRates(): array
    {
        try {
            $failures = DB::table('failed_jobs')
                ->whereIn('queue', ['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'])
                ->where('failed_at', '>=', now()->subHours(24))
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->pluck('count', 'queue')
                ->toArray();

            $totalJobs = $this->getJobCounts();

            $rates = [];
            foreach ($totalJobs as $queue => $total) {
                $failed = $failures[$queue] ?? 0;
                $rates[$queue] = $total > 0 ? ($failed / $total) * 100 : 0;
            }

            return $rates;
        } catch (\Exception $e) {
            Log::error('Failed to get failure rates', ['error' => $e->getMessage()]);
            return array_fill_keys(['waitlist-urgent', 'waitlist-high', 'waitlist-medium', 'waitlist-low', 'waitlist-maintenance'], 0);
        }
    }

    /**
     * Get waitlist backlog information
     */
    private function getWaitlistBacklog(): array
    {
        try {
            return [
                'total_active_waitlists' => Waitlist::active()->count(),
                'urgent_waitlists' => Waitlist::active()->where('priority_level', 'urgent')->count(),
                'high_waitlists' => Waitlist::active()->where('priority_level', 'high')->count(),
                'pending_offers' => WaitlistEntry::where('status', 'offered')->count(),
                'expired_offers' => WaitlistEntry::where('status', 'expired')->where('response_deadline', '<', now())->count(),
                'average_wait_time_days' => $this->calculateAverageWaitTime(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get waitlist backlog', ['error' => $e->getMessage()]);
            return [
                'total_active_waitlists' => 0,
                'urgent_waitlists' => 0,
                'high_waitlists' => 0,
                'pending_offers' => 0,
                'expired_offers' => 0,
                'average_wait_time_days' => 0,
            ];
        }
    }

    /**
     * Get performance indicators
     */
    private function getPerformanceIndicators(): array
    {
        return [
            'slot_fulfillment_rate' => $this->calculateSlotFulfillmentRate(),
            'patient_satisfaction_score' => $this->getPatientSatisfactionScore(),
            'system_health_score' => $this->calculateSystemHealthScore(),
            'alerts_count' => $this->getActiveAlertsCount(),
        ];
    }

    /**
     * Check if a queue is active
     */
    private function isQueueActive(string $queue): bool
    {
        // Check if there are active workers or recent job processing
        $lastProcessed = $this->getLastProcessedTime($queue);
        return $lastProcessed && $lastProcessed->diffInMinutes(now()) < 30;
    }

    /**
     * Get active workers for a queue
     */
    private function getActiveWorkers(string $queue): int
    {
        // In a real implementation, you'd check process monitoring or cache
        // For now, return mock data
        return Cache::get("queue_workers_{$queue}", rand(0, 3));
    }

    /**
     * Get last processed time for a queue
     */
    private function getLastProcessedTime(string $queue): ?Carbon
    {
        try {
            $lastJob = DB::table('jobs')
                ->where('queue', $queue)
                ->orderBy('created_at', 'desc')
                ->first();

            return $lastJob ? Carbon::parse($lastJob->created_at) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get stalled jobs count for a queue
     */
    private function getStalledJobsCount(string $queue): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', $queue)
                ->where('created_at', '<', now()->subMinutes(30))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate percentile from array
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $index = (int) ceil(($percentile / 100) * count($values)) - 1;
        return $values[max(0, min($index, count($values) - 1))];
    }

    /**
     * Calculate average wait time for waitlists
     */
    private function calculateAverageWaitTime(): float
    {
        try {
            $waitlists = Waitlist::active()
                ->where('created_at', '<', now()->subDays(1))
                ->get();

            if ($waitlists->isEmpty()) {
                return 0;
            }

            $totalDays = 0;
            foreach ($waitlists as $waitlist) {
                $totalDays += $waitlist->created_at->diffInDays(now());
            }

            return round($totalDays / $waitlists->count(), 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate slot fulfillment rate
     */
    private function calculateSlotFulfillmentRate(): float
    {
        try {
            $totalEntries = WaitlistEntry::where('created_at', '>=', now()->subDays(30))->count();
            $fulfilledEntries = WaitlistEntry::where('status', 'accepted')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            return $totalEntries > 0 ? round(($fulfilledEntries / $totalEntries) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get patient satisfaction score (mock implementation)
     */
    private function getPatientSatisfactionScore(): float
    {
        // In a real implementation, this would come from patient feedback
        return rand(75, 95) / 100;
    }

    /**
     * Calculate system health score
     */
    private function calculateSystemHealthScore(): float
    {
        $metrics = $this->getQueueHealthMetrics();

        $score = 100;

        // Deduct points for queue issues
        foreach ($metrics['queue_status'] as $queueStatus) {
            if (!$queueStatus['active']) {
                $score -= 20;
            }
            if ($queueStatus['stalled_jobs'] > 0) {
                $score -= 10;
            }
        }

        // Deduct points for high failure rates
        foreach ($metrics['failure_rates'] as $rate) {
            if ($rate > 5) {
                $score -= 15;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Get active alerts count
     */
    private function getActiveAlertsCount(): int
    {
        // In a real implementation, this would query an alerts system
        return rand(0, 5);
    }

    /**
     * Generate health check alerts
     */
    public function generateHealthAlerts(): array
    {
        $alerts = [];
        $metrics = $this->getQueueHealthMetrics();

        // Check for inactive queues
        foreach ($metrics['queue_status'] as $queue => $status) {
            if (!$status['active']) {
                $alerts[] = [
                    'severity' => 'critical',
                    'title' => "Queue {$queue} is inactive",
                    'description' => "No jobs processed in the last 30 minutes for queue {$queue}",
                    'service' => 'waitlist-queue',
                    'queue' => $queue,
                ];
            }
        }

        // Check for high failure rates
        foreach ($metrics['failure_rates'] as $queue => $rate) {
            if ($rate > 10) {
                $alerts[] = [
                    'severity' => 'warning',
                    'title' => "High failure rate on {$queue}",
                    'description' => "Failure rate of {$rate}% detected on queue {$queue}",
                    'service' => 'waitlist-queue',
                    'queue' => $queue,
                ];
            }
        }

        // Check for stalled jobs
        foreach ($metrics['queue_status'] as $queue => $status) {
            if ($status['stalled_jobs'] > 5) {
                $alerts[] = [
                    'severity' => 'warning',
                    'title' => "Stalled jobs detected on {$queue}",
                    'description' => "{$status['stalled_jobs']} jobs have been pending for more than 30 minutes",
                    'service' => 'waitlist-queue',
                    'queue' => $queue,
                ];
            }
        }

        // Check for high backlog
        if ($metrics['waitlist_backlog']['total_active_waitlists'] > 100) {
            $alerts[] = [
                'severity' => 'info',
                'title' => 'High waitlist backlog',
                'description' => "{$metrics['waitlist_backlog']['total_active_waitlists']} active waitlists detected",
                'service' => 'waitlist-system',
            ];
        }

        return $alerts;
    }
}
