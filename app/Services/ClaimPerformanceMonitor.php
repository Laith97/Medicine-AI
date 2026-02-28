<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClearinghouseSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ClaimPerformanceMonitor
{
    protected $cachePrefix = 'claim_performance';
    protected $cacheTtl = 3600; // 1 hour

    /**
     * Get comprehensive performance metrics for claims
     */
    public function getPerformanceMetrics(int $hospitalId, array $dateRange = null): array
    {
        $cacheKey = $this->getCacheKey('metrics', $hospitalId, $dateRange);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($hospitalId, $dateRange) {
            return [
                'processing_times' => $this->getProcessingTimeMetrics($hospitalId, $dateRange),
                'clearinghouse_performance' => $this->getClearinghousePerformanceMetrics($hospitalId, $dateRange),
                'denial_rates' => $this->getDenialRateMetrics($hospitalId, $dateRange),
                'payment_timeliness' => $this->getPaymentTimelinessMetrics($hospitalId, $dateRange),
                'error_rates' => $this->getErrorRateMetrics($hospitalId, $dateRange),
                'system_performance' => $this->getSystemPerformanceMetrics($hospitalId, $dateRange),
            ];
        });
    }

    /**
     * Get claim processing time metrics
     */
    protected function getProcessingTimeMetrics(int $hospitalId, array $dateRange = null): array
    {
        $query = Claim::where('hospital_id', $hospitalId)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $claims = $query->select([
            'created_at',
            'updated_at',
            'status',
            'clearinghouse_submitted_at',
            'clearinghouse_response_received_at'
        ])->get();

        if ($claims->isEmpty()) {
            return [
                'average_submission_time' => 0,
                'average_clearinghouse_time' => 0,
                'average_total_processing_time' => 0,
                'percentile_95_submission' => 0,
                'percentile_95_total' => 0,
            ];
        }

        $submissionTimes = [];
        $clearinghouseTimes = [];
        $totalProcessingTimes = [];

        foreach ($claims as $claim) {
            // Time from creation to submission
            if ($claim->clearinghouse_submitted_at) {
                $submissionTime = $claim->created_at->diffInMinutes($claim->clearinghouse_submitted_at);
                $submissionTimes[] = $submissionTime;
            }

            // Time from submission to response
            if ($claim->clearinghouse_submitted_at && $claim->clearinghouse_response_received_at) {
                $clearinghouseTime = $claim->clearinghouse_submitted_at->diffInMinutes($claim->clearinghouse_response_received_at);
                $clearinghouseTimes[] = $clearinghouseTime;
            }

            // Total processing time
            if ($claim->clearinghouse_response_received_at) {
                $totalTime = $claim->created_at->diffInMinutes($claim->clearinghouse_response_received_at);
                $totalProcessingTimes[] = $totalTime;
            }
        }

        return [
            'average_submission_time' => $this->calculateAverage($submissionTimes),
            'average_clearinghouse_time' => $this->calculateAverage($clearinghouseTimes),
            'average_total_processing_time' => $this->calculateAverage($totalProcessingTimes),
            'percentile_95_submission' => $this->calculatePercentile($submissionTimes, 95),
            'percentile_95_total' => $this->calculatePercentile($totalProcessingTimes, 95),
            'sample_size' => count($claims),
        ];
    }

    /**
     * Get clearinghouse performance metrics
     */
    protected function getClearinghousePerformanceMetrics(int $hospitalId, array $dateRange = null): array
    {
        $query = ClearinghouseSubmission::with('clearinghouseAccount')
            ->whereHas('claims', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            });

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $submissions = $query->get();

        if ($submissions->isEmpty()) {
            return [
                'success_rate' => 0,
                'average_batch_size' => 0,
                'average_response_time' => 0,
                'error_rate' => 0,
                'provider_performance' => [],
            ];
        }

        $successful = $submissions->where('status', 'completed');
        $failed = $submissions->whereIn('status', ['failed', 'rejected']);

        $responseTimes = $submissions->whereNotNull('response_received_at')
            ->map(function ($submission) {
                return $submission->submitted_at->diffInMinutes($submission->response_received_at);
            })->toArray();

        $providerPerformance = $submissions->groupBy('clearinghouseAccount.provider')
            ->map(function ($providerSubmissions) {
                $total = $providerSubmissions->count();
                $successful = $providerSubmissions->where('status', 'completed')->count();
                return [
                    'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                    'total_submissions' => $total,
                    'average_response_time' => $this->calculateAverage(
                        $providerSubmissions->whereNotNull('response_received_at')
                            ->map(fn($s) => $s->submitted_at->diffInMinutes($s->response_received_at))
                            ->toArray()
                    ),
                ];
            });

        return [
            'success_rate' => $submissions->count() > 0 ?
                round(($successful->count() / $submissions->count()) * 100, 2) : 0,
            'average_batch_size' => round($submissions->avg('claim_count'), 1),
            'average_response_time' => $this->calculateAverage($responseTimes),
            'error_rate' => $submissions->count() > 0 ?
                round(($failed->count() / $submissions->count()) * 100, 2) : 0,
            'provider_performance' => $providerPerformance->toArray(),
        ];
    }

    /**
     * Get denial rate metrics
     */
    protected function getDenialRateMetrics(int $hospitalId, array $dateRange = null): array
    {
        $query = Claim::where('hospital_id', $hospitalId);

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $totalClaims = $query->count();
        $deniedClaims = (clone $query)->where('claim_status', 'denied')->count();
        $approvedClaims = (clone $query)->where('claim_status', 'approved')->count();

        // Denial reasons breakdown
        $denialReasons = (clone $query)->where('claim_status', 'denied')
            ->whereNotNull('normalized_denial_category')
            ->select('normalized_denial_category', DB::raw('count(*) as count'))
            ->groupBy('normalized_denial_category')
            ->get()
            ->pluck('count', 'normalized_denial_category')
            ->toArray();

        return [
            'overall_denial_rate' => $totalClaims > 0 ?
                round(($deniedClaims / $totalClaims) * 100, 2) : 0,
            'approval_rate' => $totalClaims > 0 ?
                round(($approvedClaims / $totalClaims) * 100, 2) : 0,
            'denial_reasons_breakdown' => $denialReasons,
            'total_claims_analyzed' => $totalClaims,
        ];
    }

    /**
     * Get payment timeliness metrics
     */
    protected function getPaymentTimelinessMetrics(int $hospitalId, array $dateRange = null): array
    {
        $query = Claim::where('hospital_id', $hospitalId)
            ->where('claim_status', 'paid')
            ->whereNotNull('service_date')
            ->whereNotNull('payment_date');

        if ($dateRange) {
            $query->whereBetween('service_date', $dateRange);
        }

        $paidClaims = $query->get();

        if ($paidClaims->isEmpty()) {
            return [
                'average_payment_time' => 0,
                'median_payment_time' => 0,
                'payments_within_30_days' => 0,
                'payments_within_60_days' => 0,
                'payments_over_90_days' => 0,
            ];
        }

        $paymentTimes = $paidClaims->map(function ($claim) {
            return $claim->service_date->diffInDays($claim->payment_date);
        })->toArray();

        $within30 = collect($paymentTimes)->filter(fn($days) => $days <= 30)->count();
        $within60 = collect($paymentTimes)->filter(fn($days) => $days <= 60)->count();
        $over90 = collect($paymentTimes)->filter(fn($days) => $days > 90)->count();

        return [
            'average_payment_time' => round($this->calculateAverage($paymentTimes), 1),
            'median_payment_time' => $this->calculateMedian($paymentTimes),
            'payments_within_30_days' => round(($within30 / count($paymentTimes)) * 100, 2),
            'payments_within_60_days' => round(($within60 / count($paymentTimes)) * 100, 2),
            'payments_over_90_days' => round(($over90 / count($paymentTimes)) * 100, 2),
            'sample_size' => count($paidClaims),
        ];
    }

    /**
     * Get error rate metrics
     */
    protected function getErrorRateMetrics(int $hospitalId, array $dateRange = null): array
    {
        // This would typically pull from error logs or a dedicated error tracking table
        // For now, we'll use clearinghouse submission failures as a proxy

        $query = ClearinghouseSubmission::whereHas('claims', function ($q) use ($hospitalId) {
            $q->where('hospital_id', $hospitalId);
        });

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $totalSubmissions = $query->count();
        $failedSubmissions = (clone $query)->whereIn('status', ['failed', 'rejected'])->count();

        // Error types breakdown
        $errorTypes = (clone $query)->whereIn('status', ['failed', 'rejected'])
            ->whereNotNull('error_message')
            ->selectRaw("
                CASE
                    WHEN error_message LIKE '%timeout%' THEN 'timeout'
                    WHEN error_message LIKE '%authentication%' THEN 'authentication'
                    WHEN error_message LIKE '%validation%' THEN 'validation'
                    WHEN error_message LIKE '%network%' THEN 'network'
                    ELSE 'other'
                END as error_category,
                COUNT(*) as count
            ")
            ->groupBy('error_category')
            ->pluck('count', 'error_category')
            ->toArray();

        return [
            'overall_error_rate' => $totalSubmissions > 0 ?
                round(($failedSubmissions / $totalSubmissions) * 100, 2) : 0,
            'error_types_breakdown' => $errorTypes,
            'total_submissions' => $totalSubmissions,
        ];
    }

    /**
     * Get system performance metrics
     */
    protected function getSystemPerformanceMetrics(int $hospitalId, array $dateRange = null): array
    {
        // Database query performance (simplified)
        $startTime = microtime(true);

        $query = Claim::where('hospital_id', $hospitalId);
        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }
        $count = $query->count();

        $queryTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Memory usage
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // MB

        return [
            'database_query_time_ms' => round($queryTime, 2),
            'memory_usage_mb' => round($memoryUsage, 2),
            'records_processed' => $count,
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];
    }

    /**
     * Calculate average of array values
     */
    protected function calculateAverage(array $values): float
    {
        return empty($values) ? 0 : round(array_sum($values) / count($values), 2);
    }

    /**
     * Calculate median of array values
     */
    protected function calculateMedian(array $values): float
    {
        if (empty($values)) return 0;

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        return ($count % 2) ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Calculate percentile of array values
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) return 0;

        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);

        if ($index == floor($index)) {
            return $values[$index];
        } else {
            $lower = floor($index);
            $upper = ceil($index);
            $weight = $index - $lower;
            return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
        }
    }

    /**
     * Get cache hit rate (simplified implementation)
     */
    protected function getCacheHitRate(): float
    {
        // This would need a proper cache monitoring implementation
        // For now, return a placeholder
        return 85.5; // 85.5% cache hit rate
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, int $hospitalId, array $dateRange = null): string
    {
        $key = "{$this->cachePrefix}:{$type}:{$hospitalId}";

        if ($dateRange) {
            $key .= ':' . md5(serialize($dateRange));
        }

        return $key;
    }

    /**
     * Clear performance cache
     */
    public function clearCache(int $hospitalId = null): void
    {
        if ($hospitalId) {
            Cache::forget($this->getCacheKey('metrics', $hospitalId));
        } else {
            Cache::flush(); // Clear all cache - use with caution
        }
    }

    /**
     * Log performance alert if metrics exceed thresholds
     */
    public function checkPerformanceThresholds(array $metrics, int $hospitalId): void
    {
        $alerts = [];

        // Check processing time thresholds
        if ($metrics['processing_times']['average_total_processing_time'] > 1440) { // 24 hours
            $alerts[] = 'Average claim processing time exceeds 24 hours';
        }

        // Check denial rate thresholds
        if ($metrics['denial_rates']['overall_denial_rate'] > 25) {
            $alerts[] = 'Claim denial rate exceeds 25%';
        }

        // Check error rate thresholds
        if ($metrics['error_rates']['overall_error_rate'] > 10) {
            $alerts[] = 'Claim error rate exceeds 10%';
        }

        // Log alerts
        foreach ($alerts as $alert) {
            Log::warning('Claim Performance Alert', [
                'hospital_id' => $hospitalId,
                'alert' => $alert,
                'metrics' => $metrics,
                'timestamp' => now(),
            ]);
        }
    }
}
