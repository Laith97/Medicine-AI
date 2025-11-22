<?php

namespace App\Services;

use App\Models\Kiosk;
use App\Models\KioskSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KioskPerformanceMonitor
{
    /**
     * Record kiosk session start
     */
    public static function recordSessionStart(int $kioskId, int $sessionId): void
    {
        $metrics = self::getMetrics($kioskId);
        $metrics['active_sessions'] = ($metrics['active_sessions'] ?? 0) + 1;
        $metrics['total_sessions_started'] = ($metrics['total_sessions_started'] ?? 0) + 1;

        self::updateMetrics($kioskId, $metrics);

        Log::info('Kiosk session started', [
            'kiosk_id' => $kioskId,
            'session_id' => $sessionId,
            'active_sessions' => $metrics['active_sessions'],
        ]);
    }

    /**
     * Record kiosk session end
     */
    public static function recordSessionEnd(int $kioskId, int $sessionId, string $status, ?int $duration = null): void
    {
        $metrics = self::getMetrics($kioskId);
        $metrics['active_sessions'] = max(0, ($metrics['active_sessions'] ?? 0) - 1);

        if ($status === 'completed') {
            $metrics['completed_sessions'] = ($metrics['completed_sessions'] ?? 0) + 1;
        } elseif ($status === 'abandoned') {
            $metrics['abandoned_sessions'] = ($metrics['abandoned_sessions'] ?? 0) + 1;
        } elseif ($status === 'error') {
            $metrics['error_sessions'] = ($metrics['error_sessions'] ?? 0) + 1;
        }

        if ($duration) {
            $metrics['total_session_duration'] = ($metrics['total_session_duration'] ?? 0) + $duration;
            $metrics['session_count_for_avg'] = ($metrics['session_count_for_avg'] ?? 0) + 1;
        }

        self::updateMetrics($kioskId, $metrics);

        Log::info('Kiosk session ended', [
            'kiosk_id' => $kioskId,
            'session_id' => $sessionId,
            'status' => $status,
            'duration' => $duration,
            'active_sessions' => $metrics['active_sessions'],
        ]);
    }

    /**
     * Record kiosk check-in
     */
    public static function recordCheckin(int $kioskId, string $verificationMethod): void
    {
        $metrics = self::getMetrics($kioskId);
        $metrics['total_checkins'] = ($metrics['total_checkins'] ?? 0) + 1;

        $methodKey = 'checkins_' . $verificationMethod;
        $metrics[$methodKey] = ($metrics[$methodKey] ?? 0) + 1;

        self::updateMetrics($kioskId, $metrics);
    }

    /**
     * Record kiosk payment
     */
    public static function recordPayment(int $kioskId, float $amount, string $status): void
    {
        $metrics = self::getMetrics($kioskId);
        $metrics['total_payments_attempted'] = ($metrics['total_payments_attempted'] ?? 0) + 1;

        if ($status === 'succeeded') {
            $metrics['successful_payments'] = ($metrics['successful_payments'] ?? 0) + 1;
            $metrics['total_revenue'] = ($metrics['total_revenue'] ?? 0) + $amount;
        } elseif ($status === 'failed') {
            $metrics['failed_payments'] = ($metrics['failed_payments'] ?? 0) + 1;
        }

        self::updateMetrics($kioskId, $metrics);
    }

    /**
     * Record kiosk error
     */
    public static function recordError(int $kioskId, string $errorType, string $message): void
    {
        $metrics = self::getMetrics($kioskId);
        $metrics['total_errors'] = ($metrics['total_errors'] ?? 0) + 1;

        $errorKey = 'errors_' . $errorType;
        $metrics[$errorKey] = ($metrics[$errorKey] ?? 0) + 1;

        self::updateMetrics($kioskId, $metrics);

        Log::warning('Kiosk error recorded', [
            'kiosk_id' => $kioskId,
            'error_type' => $errorType,
            'message' => $message,
        ]);
    }

    /**
     * Record kiosk performance metric
     */
    public static function recordPerformanceMetric(int $kioskId, string $metric, $value): void
    {
        $metrics = self::getMetrics($kioskId);
        $metrics[$metric] = $value;
        self::updateMetrics($kioskId, $metrics);
    }

    /**
     * Get kiosk performance metrics
     */
    public static function getMetrics(int $kioskId): array
    {
        $cacheKey = "kiosk_metrics_{$kioskId}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Update kiosk metrics in cache
     */
    private static function updateMetrics(int $kioskId, array $metrics): void
    {
        $cacheKey = "kiosk_metrics_{$kioskId}";
        Cache::put($cacheKey, $metrics, now()->addHours(24)); // Cache for 24 hours
    }

    /**
     * Get aggregated performance report for a kiosk
     */
    public static function getPerformanceReport(int $kioskId): array
    {
        $metrics = self::getMetrics($kioskId);

        // Calculate derived metrics
        $totalSessions = $metrics['total_sessions_started'] ?? 0;
        $completedSessions = $metrics['completed_sessions'] ?? 0;
        $abandonedSessions = $metrics['abandoned_sessions'] ?? 0;

        $completionRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 2) : 0;
        $abandonmentRate = $totalSessions > 0 ? round(($abandonedSessions / $totalSessions) * 100, 2) : 0;

        $totalPayments = $metrics['total_payments_attempted'] ?? 0;
        $successfulPayments = $metrics['successful_payments'] ?? 0;
        $paymentSuccessRate = $totalPayments > 0 ? round(($successfulPayments / $totalPayments) * 100, 2) : 0;

        $sessionCount = $metrics['session_count_for_avg'] ?? 0;
        $avgSessionDuration = $sessionCount > 0 ? round(($metrics['total_session_duration'] ?? 0) / $sessionCount, 1) : 0;

        return [
            'kiosk_id' => $kioskId,
            'active_sessions' => $metrics['active_sessions'] ?? 0,
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'abandoned_sessions' => $abandonedSessions,
            'error_sessions' => $metrics['error_sessions'] ?? 0,
            'completion_rate' => $completionRate,
            'abandonment_rate' => $abandonmentRate,
            'total_checkins' => $metrics['total_checkins'] ?? 0,
            'checkin_methods' => [
                'qr_code' => $metrics['checkins_qr_code'] ?? 0,
                'id_card' => $metrics['checkins_id_card'] ?? 0,
                'biometric' => $metrics['checkins_biometric'] ?? 0,
                'manual' => $metrics['checkins_manual'] ?? 0,
            ],
            'payment_metrics' => [
                'total_attempted' => $totalPayments,
                'successful' => $successfulPayments,
                'failed' => $metrics['failed_payments'] ?? 0,
                'success_rate' => $paymentSuccessRate,
                'total_revenue' => $metrics['total_revenue'] ?? 0,
            ],
            'average_session_duration' => $avgSessionDuration,
            'total_errors' => $metrics['total_errors'] ?? 0,
            'error_breakdown' => [
                'validation' => $metrics['errors_validation'] ?? 0,
                'payment' => $metrics['errors_payment'] ?? 0,
                'system' => $metrics['errors_system'] ?? 0,
                'network' => $metrics['errors_network'] ?? 0,
            ],
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Get system-wide kiosk performance summary
     */
    public static function getSystemPerformanceSummary(): array
    {
        $kiosks = Kiosk::active()->get();
        $summary = [
            'total_kiosks' => $kiosks->count(),
            'active_kiosks' => 0,
            'total_sessions' => 0,
            'active_sessions' => 0,
            'total_revenue' => 0,
            'total_errors' => 0,
            'average_completion_rate' => 0,
        ];

        $completionRates = [];

        foreach ($kiosks as $kiosk) {
            $report = self::getPerformanceReport($kiosk->id);

            if ($report['active_sessions'] > 0) {
                $summary['active_kiosks']++;
            }

            $summary['total_sessions'] += $report['total_sessions'];
            $summary['active_sessions'] += $report['active_sessions'];
            $summary['total_revenue'] += $report['payment_metrics']['total_revenue'];
            $summary['total_errors'] += $report['total_errors'];

            if ($report['completion_rate'] > 0) {
                $completionRates[] = $report['completion_rate'];
            }
        }

        $summary['average_completion_rate'] = !empty($completionRates)
            ? round(array_sum($completionRates) / count($completionRates), 2)
            : 0;

        return $summary;
    }

    /**
     * Clear metrics for a kiosk (useful for maintenance)
     */
    public static function clearMetrics(int $kioskId): void
    {
        $cacheKey = "kiosk_metrics_{$kioskId}";
        Cache::forget($cacheKey);

        Log::info('Kiosk metrics cleared', ['kiosk_id' => $kioskId]);
    }

    /**
     * Export metrics for backup/analysis
     */
    public static function exportMetrics(int $kioskId): array
    {
        return self::getMetrics($kioskId);
    }
}
