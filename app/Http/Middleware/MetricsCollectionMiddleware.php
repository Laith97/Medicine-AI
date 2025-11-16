<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MetricsCollectionMiddleware
{
    /**
     * Handle an incoming request and collect metrics
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Calculate metrics
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;

        // Collect request metrics
        $this->collectRequestMetrics($request, $response, $duration, $memoryUsed);

        // Log slow requests
        if ($duration > 1000) { // More than 1 second
            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => round($duration, 2),
                'memory_kb' => round($memoryUsed / 1024, 2),
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }

        // Log error responses
        if ($response->getStatusCode() >= 500) {
            Log::error('Server error response', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    /**
     * Collect request metrics for monitoring
     */
    private function collectRequestMetrics(Request $request, Response $response, float $duration, int $memoryUsed): void
    {
        try {
            $method = $request->method();
            $statusCode = $response->getStatusCode();
            $route = $request->route() ? $request->route()->getName() : 'unknown';

            // Increment request counters
            $this->incrementCounter("http_requests_total{method=\"$method\",status=\"$statusCode\"}");

            // Record response time histograms
            $this->recordHistogram('http_request_duration_seconds', $duration / 1000); // Convert to seconds

            // Record memory usage
            $this->recordHistogram('http_request_memory_bytes', $memoryUsed);

            // Track active users (rough estimate)
            if ($request->user()) {
                $this->incrementCounter('active_users_total');
            }

            // Track API usage by route
            if (str_starts_with($request->path(), 'api/')) {
                $this->incrementCounter("api_requests_total{route=\"$route\",method=\"$method\"}");
            }

            // Clean up old metrics periodically (every 1000 requests)
            $requestCount = Cache::increment('metrics_request_count');
            if ($requestCount % 1000 === 0) {
                $this->cleanupOldMetrics();
            }

        } catch (\Exception $e) {
            // Don't let metrics collection break the application
            Log::error('Metrics collection failed', [
                'error' => $e->getMessage(),
                'request' => $request->fullUrl(),
            ]);
        }
    }

    /**
     * Increment a counter metric
     */
    private function incrementCounter(string $metricKey): void
    {
        $current = Cache::get($metricKey, 0);
        Cache::put($metricKey, $current + 1, now()->addHours(24));
    }

    /**
     * Record a histogram value
     */
    private function recordHistogram(string $metricName, float $value): void
    {
        $key = "histogram_{$metricName}";
        $histogram = Cache::get($key, [
            'count' => 0,
            'sum' => 0,
            'values' => [],
        ]);

        $histogram['count']++;
        $histogram['sum'] += $value;
        $histogram['values'][] = $value;

        // Keep only last 1000 values for memory efficiency
        if (count($histogram['values']) > 1000) {
            array_shift($histogram['values']);
        }

        Cache::put($key, $histogram, now()->addHours(24));
    }

    /**
     * Clean up old metrics to prevent memory bloat
     */
    private function cleanupOldMetrics(): void
    {
        try {
            // Simple cleanup - just reset counters periodically
            // In production, implement proper cleanup based on your cache store
            $oldMetrics = [
                'http_requests_total',
                'active_users_total',
                'api_requests_total',
            ];

            foreach ($oldMetrics as $metric) {
                // Reset counters that are too old (simplified approach)
                $key = $metric . '_reset_time';
                $lastReset = Cache::get($key);
                if (!$lastReset || now()->diffInHours($lastReset) > 24) {
                    Cache::put($key, now(), now()->addDays(7));
                    // Don't actually reset - let them expire naturally
                }
            }
        } catch (\Exception $e) {
            Log::warning('Metrics cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get histogram statistics
     */
    public static function getHistogramStats(string $metricName): array
    {
        $key = "histogram_{$metricName}";
        $histogram = Cache::get($key, ['count' => 0, 'sum' => 0, 'values' => []]);

        if (empty($histogram['values'])) {
            return ['count' => 0, 'avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0];
        }

        sort($histogram['values']);
        $count = count($histogram['values']);

        return [
            'count' => $histogram['count'],
            'avg' => $histogram['sum'] / $histogram['count'],
            'p50' => $histogram['values'][intval($count * 0.5)] ?? 0,
            'p95' => $histogram['values'][intval($count * 0.95)] ?? 0,
            'p99' => $histogram['values'][intval($count * 0.99)] ?? 0,
        ];
    }

    /**
     * Get counter value
     */
    public static function getCounterValue(string $metricKey): int
    {
        return Cache::get($metricKey, 0);
    }
}
