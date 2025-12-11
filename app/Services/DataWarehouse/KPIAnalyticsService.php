<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KPIAnalyticsService
{
    protected $cacheTtl = 1800; // 30 minutes cache

    protected $calculationService;
    protected $trendService;
    protected $forecastingService;
    protected $benchmarkingService;
    protected $alertService;

    public function __construct()
    {
        $this->calculationService = app(KPICalculationService::class);
        $this->trendService = app(TrendAnalysisService::class);
        $this->forecastingService = app(ForecastingService::class);
        $this->benchmarkingService = app(BenchmarkingService::class);
        $this->alertService = app(KPIAlertService::class);
    }

    /**
     * Get comprehensive analytics dashboard data
     */
    public function getAnalyticsDashboard($hospitalKey = 1, $period = 'daily')
    {
        $cacheKey = "analytics_dashboard_{$hospitalKey}_{$period}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($hospitalKey, $period) {
            return $this->generateAnalyticsDashboard($hospitalKey, $period);
        });
    }

    /**
     * Generate comprehensive analytics dashboard
     */
    private function generateAnalyticsDashboard($hospitalKey, $period)
    {
        // Get current KPI values
        $currentKPIs = $this->getCurrentKPIs($hospitalKey, $period);

        // Get trend analysis for key KPIs
        $trends = $this->getKPITrends($hospitalKey);

        // Get forecasts for key KPIs
        $forecasts = $this->getKPIForecasts($hospitalKey);

        // Get benchmarking data
        $benchmarks = $this->benchmarkingService->getBenchmarkingReport($hospitalKey, $period);

        // Get active alerts
        $alerts = $this->alertService->getActiveAlerts($hospitalKey, 10);

        // Calculate dashboard insights
        $insights = $this->calculateDashboardInsights($currentKPIs, $trends, $forecasts, $benchmarks);

        return [
            'generated_at' => now(),
            'period' => $period,
            'hospital_key' => $hospitalKey,
            'current_kpis' => $currentKPIs,
            'trends' => $trends,
            'forecasts' => $forecasts,
            'benchmarks' => $benchmarks,
            'alerts' => $alerts,
            'insights' => $insights,
            'performance_score' => $this->calculatePerformanceScore($currentKPIs, $benchmarks)
        ];
    }

    /**
     * Get current KPI values
     */
    private function getCurrentKPIs($hospitalKey, $period)
    {
        switch ($period) {
            case 'daily':
                return $this->calculationService->calculateDailyKPIs(null, $hospitalKey);
            case 'monthly':
                return $this->calculationService->calculateMonthlyKPIs(null, null, $hospitalKey);
            default:
                return $this->calculationService->calculateDailyKPIs(null, $hospitalKey);
        }
    }

    /**
     * Get trend analysis for key KPIs
     */
    private function getKPITrends($hospitalKey)
    {
        $keyKPIs = [
            'patient_satisfaction_score',
            'total_revenue',
            'appointment_show_up_rate',
            'average_wait_time_minutes',
            'provider_utilization_rate',
            'treatment_success_rate'
        ];

        $trends = [];
        foreach ($keyKPIs as $kpi) {
            $trends[$kpi] = $this->trendService->calculateTrend($kpi, 30, $hospitalKey);
        }

        return $trends;
    }

    /**
     * Get forecasts for key KPIs
     */
    private function getKPIForecasts($hospitalKey)
    {
        $keyKPIs = [
            'total_revenue',
            'patient_satisfaction_score',
            'appointment_show_up_rate'
        ];

        $forecasts = [];
        foreach ($keyKPIs as $kpi) {
            $forecasts[$kpi] = $this->forecastingService->forecastKPI($kpi, 14, 60, $hospitalKey);
        }

        return $forecasts;
    }

    /**
     * Calculate dashboard insights
     */
    private function calculateDashboardInsights($currentKPIs, $trends, $forecasts, $benchmarks)
    {
        $insights = [];

        // Revenue insights
        if (isset($currentKPIs['total_revenue'])) {
            $revenueTrend = $trends['total_revenue'] ?? null;
            if ($revenueTrend && $revenueTrend['trend_direction'] === 'increasing') {
                $insights[] = [
                    'type' => 'positive',
                    'category' => 'revenue',
                    'title' => 'Revenue Growth',
                    'description' => "Revenue is trending upward with {$revenueTrend['change_percentage']}% growth over the last 30 days.",
                    'impact' => 'high'
                ];
            }
        }

        // Patient satisfaction insights
        if (isset($currentKPIs['patient_satisfaction_score'])) {
            $satisfactionTrend = $trends['patient_satisfaction_score'] ?? null;
            if ($satisfactionTrend && $satisfactionTrend['trend_direction'] === 'increasing') {
                $insights[] = [
                    'type' => 'positive',
                    'category' => 'patient_experience',
                    'title' => 'Improving Patient Satisfaction',
                    'description' => "Patient satisfaction scores are improving, indicating better care quality.",
                    'impact' => 'high'
                ];
            }
        }

        // Operational efficiency insights
        if (isset($currentKPIs['average_wait_time_minutes'])) {
            $waitTimeTrend = $trends['average_wait_time_minutes'] ?? null;
            if ($waitTimeTrend && $waitTimeTrend['trend_direction'] === 'decreasing') {
                $insights[] = [
                    'type' => 'positive',
                    'category' => 'operations',
                    'title' => 'Reduced Wait Times',
                    'description' => "Patient wait times are decreasing, improving operational efficiency.",
                    'impact' => 'medium'
                ];
            }
        }

        // Benchmarking insights
        if (isset($benchmarks['benchmark_results'])) {
            foreach ($benchmarks['benchmark_results'] as $kpi => $result) {
                if ($result['performance_level'] === 'below_average') {
                    $insights[] = [
                        'type' => 'warning',
                        'category' => 'benchmarking',
                        'title' => 'Below Industry Standard',
                        'description' => ucfirst(str_replace('_', ' ', $kpi)) . " is below industry average. Consider implementing improvement initiatives.",
                        'impact' => 'high',
                        'kpi' => $kpi
                    ];
                }
            }
        }

        // Forecast insights
        if (isset($forecasts['total_revenue'])) {
            $revenueForecast = $forecasts['total_revenue'];
            if (isset($revenueForecast['forecast_accuracy']) && $revenueForecast['forecast_accuracy'] > 20) {
                $insights[] = [
                    'type' => 'info',
                    'category' => 'forecasting',
                    'title' => 'Revenue Forecast Uncertainty',
                    'description' => "Revenue forecasting has higher than normal uncertainty. Monitor closely for the next 14 days.",
                    'impact' => 'medium'
                ];
            }
        }

        return $insights;
    }

    /**
     * Calculate overall performance score
     */
    private function calculatePerformanceScore($currentKPIs, $benchmarks)
    {
        $score = 0;
        $totalWeight = 0;

        $weights = [
            'patient_satisfaction_score' => 20,
            'total_revenue' => 25,
            'appointment_show_up_rate' => 15,
            'average_wait_time_minutes' => 10,
            'provider_utilization_rate' => 15,
            'treatment_success_rate' => 15
        ];

        foreach ($weights as $kpi => $weight) {
            if (isset($currentKPIs[$kpi]) && isset($benchmarks['benchmark_results'][$kpi])) {
                $benchmark = $benchmarks['benchmark_results'][$kpi];
                $kpiScore = $this->calculateKPIScore($currentKPIs[$kpi], $benchmark, $kpi);
                $score += $kpiScore * ($weight / 100);
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($score / ($totalWeight / 100), 1) : 0;
    }

    /**
     * Calculate individual KPI score based on benchmarks
     */
    private function calculateKPIScore($value, $benchmark, $kpiName)
    {
        if ($value === null || !isset($benchmark['performance_level'])) {
            return 50; // Neutral score for missing data
        }

        $levelScores = [
            'excellent' => 100,
            'good' => 80,
            'average' => 60,
            'below_average' => 40,
            'no_data' => 50
        ];

        $baseScore = $levelScores[$benchmark['performance_level']] ?? 50;

        // Adjust score based on gap to next level
        if (isset($benchmark['gap_to_excellent']) && $benchmark['gap_to_excellent'] < 10) {
            $baseScore += 5; // Close to excellent
        }

        return min(100, max(0, $baseScore));
    }

    /**
     * Get predictive insights for dashboard
     */
    public function getPredictiveInsights($hospitalKey = 1)
    {
        $cacheKey = "predictive_insights_{$hospitalKey}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($hospitalKey) {
            return $this->generatePredictiveInsights($hospitalKey);
        });
    }

    /**
     * Generate predictive insights
     */
    private function generatePredictiveInsights($hospitalKey)
    {
        $insights = [];

        // Revenue predictions
        $revenueForecast = $this->forecastingService->forecastKPI('total_revenue', 30, 90, $hospitalKey);
        if ($revenueForecast && isset($revenueForecast['forecast_methods']['ensemble'])) {
            $nextMonthPrediction = $revenueForecast['forecast_methods']['ensemble'][29] ?? null;
            if ($nextMonthPrediction) {
                $insights[] = [
                    'type' => 'forecast',
                    'category' => 'revenue',
                    'title' => 'Revenue Prediction',
                    'description' => "Expected revenue for next month: $" . number_format($nextMonthPrediction, 2),
                    'confidence' => $this->calculateForecastConfidence($revenueForecast),
                    'timeframe' => '30 days'
                ];
            }
        }

        // Patient satisfaction trends
        $satisfactionTrend = $this->trendService->calculateTrend('patient_satisfaction_score', 60, $hospitalKey);
        if ($satisfactionTrend && $satisfactionTrend['trend_direction'] === 'decreasing') {
            $insights[] = [
                'type' => 'warning',
                'category' => 'patient_experience',
                'title' => 'Patient Satisfaction Trend',
                'description' => "Patient satisfaction is trending downward. Consider implementing patient experience improvements.",
                'trend_strength' => $satisfactionTrend['trend_strength'],
                'timeframe' => '60 days'
            ];
        }

        // Operational predictions
        $utilizationTrend = $this->trendService->calculateTrend('provider_utilization_rate', 30, $hospitalKey);
        if ($utilizationTrend && $utilizationTrend['trend_direction'] === 'decreasing') {
            $insights[] = [
                'type' => 'alert',
                'category' => 'operations',
                'title' => 'Provider Utilization',
                'description' => "Provider utilization is declining. Review scheduling and capacity planning.",
                'trend_strength' => $utilizationTrend['trend_strength'],
                'timeframe' => '30 days'
            ];
        }

        return [
            'generated_at' => now(),
            'insights' => $insights,
            'hospital_key' => $hospitalKey
        ];
    }

    /**
     * Calculate forecast confidence level
     */
    private function calculateForecastConfidence($forecast)
    {
        $accuracy = $forecast['forecast_accuracy'] ?? null;
        if ($accuracy === null) return 'unknown';

        if ($accuracy < 10) return 'high';
        if ($accuracy < 20) return 'medium';
        return 'low';
    }

    /**
     * Get real-time KPI updates for dashboard
     */
    public function getRealtimeUpdates($hospitalKey = 1, $since = null)
    {
        $since = $since ?: Carbon::now()->subMinutes(5);

        $updates = DB::table('agg_daily_kpis')
            ->where('hospital_key', $hospitalKey)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();

        $alerts = $this->alertService->getActiveAlerts($hospitalKey, 5);

        return [
            'kpi_updates' => $updates,
            'alerts' => $alerts,
            'last_update' => now()
        ];
    }

    /**
     * Export analytics data
     */
    public function exportAnalyticsData($hospitalKey = 1, $format = 'json', $dateRange = null)
    {
        $startDate = $dateRange['start'] ?? Carbon::now()->subDays(30);
        $endDate = $dateRange['end'] ?? Carbon::now();

        $data = [
            'metadata' => [
                'hospital_key' => $hospitalKey,
                'date_range' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
                'exported_at' => now(),
                'format' => $format
            ],
            'kpi_data' => $this->getKPITimeSeriesData($hospitalKey, $startDate, $endDate),
            'benchmarks' => $this->benchmarkingService->getBenchmarkingReport($hospitalKey),
            'alerts' => $this->alertService->getActiveAlerts($hospitalKey, 100)
        ];

        return $data;
    }

    /**
     * Get time series data for KPIs
     */
    private function getKPITimeSeriesData($hospitalKey, Carbon $startDate, Carbon $endDate)
    {
        $dateKeys = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateKeys[] = (int)$date->format('Ymd');
        }

        return DB::table('agg_daily_kpis')
            ->whereIn('date_key', $dateKeys)
            ->where('hospital_key', $hospitalKey)
            ->orderBy('date_key')
            ->get()
            ->map(function ($record) {
                $record->date = Carbon::createFromFormat('Ymd', $record->date_key)->format('Y-m-d');
                return $record;
            });
    }

    /**
     * Clear analytics cache
     */
    public function clearCache($hospitalKey = null)
    {
        if ($hospitalKey) {
            Cache::forget("analytics_dashboard_{$hospitalKey}_daily");
            Cache::forget("analytics_dashboard_{$hospitalKey}_monthly");
            Cache::forget("predictive_insights_{$hospitalKey}");
        } else {
            Cache::flush();
        }

        return true;
    }
}
