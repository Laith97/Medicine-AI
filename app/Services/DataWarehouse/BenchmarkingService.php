<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BenchmarkingService
{
    protected $cacheTtl = 3600; // 1 hour cache

    // Industry benchmark data (sample values - would be loaded from external source)
    protected $industryBenchmarks = [
        'patient_satisfaction_score' => [
            'excellent' => 4.5,
            'good' => 4.0,
            'average' => 3.5,
            'below_average' => 3.0
        ],
        'net_promoter_score' => [
            'excellent' => 50,
            'good' => 30,
            'average' => 10,
            'below_average' => -10
        ],
        'appointment_show_up_rate' => [
            'excellent' => 85,
            'good' => 80,
            'average' => 75,
            'below_average' => 70
        ],
        'average_wait_time_minutes' => [
            'excellent' => 10,
            'good' => 15,
            'average' => 20,
            'below_average' => 25
        ],
        'provider_utilization_rate' => [
            'excellent' => 85,
            'good' => 80,
            'average' => 75,
            'below_average' => 70
        ],
        'treatment_success_rate' => [
            'excellent' => 90,
            'good' => 85,
            'average' => 80,
            'below_average' => 75
        ],
        'readmission_rate_30_days' => [
            'excellent' => 5,
            'good' => 8,
            'average' => 12,
            'below_average' => 15
        ],
        'average_revenue_per_user' => [
            'excellent' => 150,
            'good' => 120,
            'average' => 90,
            'below_average' => 60
        ]
    ];

    // Specialty-specific benchmarks
    protected $specialtyBenchmarks = [
        'cardiology' => [
            'readmission_rate_30_days' => ['excellent' => 8, 'good' => 12, 'average' => 16],
            'treatment_success_rate' => ['excellent' => 88, 'good' => 82, 'average' => 76]
        ],
        'orthopedics' => [
            'readmission_rate_30_days' => ['excellent' => 6, 'good' => 10, 'average' => 14],
            'treatment_success_rate' => ['excellent' => 92, 'good' => 86, 'average' => 80]
        ],
        'pediatrics' => [
            'patient_satisfaction_score' => ['excellent' => 4.7, 'good' => 4.3, 'average' => 3.9],
            'appointment_show_up_rate' => ['excellent' => 82, 'good' => 78, 'average' => 74]
        ]
    ];

    /**
     * Get comprehensive benchmarking report for hospital
     */
    public function getBenchmarkingReport($hospitalKey = 1, $period = 'monthly')
    {
        $cacheKey = "benchmark_report_{$hospitalKey}_{$period}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($hospitalKey, $period) {
            return $this->generateBenchmarkingReport($hospitalKey, $period);
        });
    }

    /**
     * Generate comprehensive benchmarking report
     */
    private function generateBenchmarkingReport($hospitalKey, $period)
    {
        $currentMetrics = $this->getCurrentMetrics($hospitalKey, $period);
        $peerComparison = $this->getPeerComparison($hospitalKey, $period);

        $benchmarkResults = [];
        foreach ($this->industryBenchmarks as $kpi => $benchmarks) {
            if (isset($currentMetrics[$kpi])) {
                $benchmarkResults[$kpi] = $this->calculateBenchmarkPerformance(
                    $currentMetrics[$kpi],
                    $benchmarks,
                    $kpi
                );
            }
        }

        return [
            'hospital_key' => $hospitalKey,
            'period' => $period,
            'generated_at' => now(),
            'overall_performance_score' => $this->calculateOverallPerformanceScore($benchmarkResults),
            'performance_distribution' => $this->getPerformanceDistribution($benchmarkResults),
            'benchmark_results' => $benchmarkResults,
            'peer_comparison' => $peerComparison,
            'recommendations' => $this->generateRecommendations($benchmarkResults),
            'trending_kpis' => $this->getTrendingKPIs($hospitalKey)
        ];
    }

    /**
     * Get current metrics for benchmarking
     */
    private function getCurrentMetrics($hospitalKey, $period)
    {
        $date = $this->getPeriodDate($period);

        if ($period === 'monthly') {
            return DB::table('agg_monthly_kpis')
                ->where('hospital_key', $hospitalKey)
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->first();
        } else {
            // Daily metrics - get average of last 30 days
            return DB::table('agg_daily_kpis')
                ->where('hospital_key', $hospitalKey)
                ->where('date_key', '>=', (int)$date->copy()->subDays(30)->format('Ymd'))
                ->where('date_key', '<=', (int)$date->format('Ymd'))
                ->selectRaw('
                    AVG(total_revenue) as total_revenue,
                    AVG(patient_satisfaction_score) as patient_satisfaction_score,
                    AVG(net_promoter_score) as net_promoter_score,
                    AVG(appointment_show_up_rate) as appointment_show_up_rate,
                    AVG(average_wait_time_minutes) as average_wait_time_minutes,
                    AVG(provider_utilization_rate) as provider_utilization_rate,
                    AVG(treatment_success_rate) as treatment_success_rate,
                    AVG(readmission_rate_30_days) as readmission_rate_30_days,
                    AVG(average_revenue_per_user) as average_revenue_per_user
                ')
                ->first();
        }
    }

    /**
     * Calculate benchmark performance for a specific KPI
     */
    private function calculateBenchmarkPerformance($currentValue, $benchmarks, $kpiName)
    {
        if ($currentValue === null) {
            return [
                'current_value' => null,
                'performance_level' => 'no_data',
                'percentile_rank' => null,
                'gap_to_excellent' => null,
                'gap_to_average' => null,
                'trend' => 'unknown'
            ];
        }

        $performanceLevel = $this->determinePerformanceLevel($currentValue, $benchmarks, $kpiName);
        $percentileRank = $this->calculatePercentileRank($currentValue, $benchmarks);

        // Calculate gaps
        $gapToExcellent = $this->calculateGap($currentValue, $benchmarks['excellent'], $kpiName);
        $gapToAverage = $this->calculateGap($currentValue, $benchmarks['average'], $kpiName);

        return [
            'current_value' => $currentValue,
            'performance_level' => $performanceLevel,
            'percentile_rank' => $percentileRank,
            'gap_to_excellent' => $gapToExcellent,
            'gap_to_average' => $gapToAverage,
            'trend' => $this->getKPITrend($kpiName),
            'benchmarks' => $benchmarks
        ];
    }

    /**
     * Determine performance level based on benchmarks
     */
    private function determinePerformanceLevel($value, $benchmarks, $kpiName)
    {
        // For KPIs where higher is better
        $higherIsBetter = !in_array($kpiName, [
            'average_wait_time_minutes',
            'readmission_rate_30_days'
        ]);

        if ($higherIsBetter) {
            if ($value >= $benchmarks['excellent']) return 'excellent';
            if ($value >= $benchmarks['good']) return 'good';
            if ($value >= $benchmarks['average']) return 'average';
            return 'below_average';
        } else {
            if ($value <= $benchmarks['excellent']) return 'excellent';
            if ($value <= $benchmarks['good']) return 'good';
            if ($value <= $benchmarks['average']) return 'average';
            return 'below_average';
        }
    }

    /**
     * Calculate percentile rank
     */
    private function calculatePercentileRank($value, $benchmarks)
    {
        $levels = ['below_average', 'average', 'good', 'excellent'];
        $level = $this->determinePerformanceLevel($value, $benchmarks, '');

        $levelIndex = array_search($level, $levels);
        return ($levelIndex + 1) * 25; // Rough percentile estimation
    }

    /**
     * Calculate gap to benchmark
     */
    private function calculateGap($current, $benchmark, $kpiName)
    {
        if ($current == 0 && $benchmark == 0) return 0;

        $higherIsBetter = !in_array($kpiName, [
            'average_wait_time_minutes',
            'readmission_rate_30_days'
        ]);

        if ($higherIsBetter) {
            return $benchmark != 0 ? (($current - $benchmark) / $benchmark) * 100 : 0;
        } else {
            return $current != 0 ? (($benchmark - $current) / $current) * 100 : 0;
        }
    }

    /**
     * Get peer comparison data
     */
    private function getPeerComparison($hospitalKey, $period)
    {
        // This would typically query peer hospital data
        // For now, return mock peer comparison
        return [
            'peer_group' => 'similar_size_hospitals',
            'peer_count' => 25,
            'rankings' => [
                'patient_satisfaction' => 12, // 12th out of 25
                'revenue_efficiency' => 8,   // 8th out of 25
                'clinical_outcomes' => 15    // 15th out of 25
            ],
            'percentiles' => [
                'patient_satisfaction' => 52,
                'revenue_efficiency' => 68,
                'clinical_outcomes' => 40
            ]
        ];
    }

    /**
     * Calculate overall performance score
     */
    private function calculateOverallPerformanceScore($benchmarkResults)
    {
        $scores = [
            'excellent' => 100,
            'good' => 75,
            'average' => 50,
            'below_average' => 25,
            'no_data' => 0
        ];

        $totalScore = 0;
        $count = 0;

        foreach ($benchmarkResults as $result) {
            $level = $result['performance_level'];
            $totalScore += $scores[$level] ?? 0;
            $count++;
        }

        return $count > 0 ? round($totalScore / $count, 1) : 0;
    }

    /**
     * Get performance distribution
     */
    private function getPerformanceDistribution($benchmarkResults)
    {
        $distribution = [
            'excellent' => 0,
            'good' => 0,
            'average' => 0,
            'below_average' => 0,
            'no_data' => 0
        ];

        foreach ($benchmarkResults as $result) {
            $level = $result['performance_level'];
            $distribution[$level]++;
        }

        return $distribution;
    }

    /**
     * Generate recommendations based on benchmark results
     */
    private function generateRecommendations($benchmarkResults)
    {
        $recommendations = [];

        foreach ($benchmarkResults as $kpi => $result) {
            if ($result['performance_level'] === 'below_average') {
                $recommendations[] = [
                    'kpi' => $kpi,
                    'priority' => 'high',
                    'recommendation' => $this->getKPIRecommendation($kpi, $result),
                    'potential_impact' => 'significant'
                ];
            } elseif ($result['performance_level'] === 'average') {
                $recommendations[] = [
                    'kpi' => $kpi,
                    'priority' => 'medium',
                    'recommendation' => $this->getKPIRecommendation($kpi, $result),
                    'potential_impact' => 'moderate'
                ];
            }
        }

        // Sort by priority
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorityOrder[$b['priority']] <=> $priorityOrder[$a['priority']];
        });

        return array_slice($recommendations, 0, 5); // Top 5 recommendations
    }

    /**
     * Get specific recommendation for a KPI
     */
    private function getKPIRecommendation($kpi, $result)
    {
        $recommendations = [
            'patient_satisfaction_score' => 'Implement patient feedback systems and staff training programs to improve satisfaction scores.',
            'appointment_show_up_rate' => 'Enhance appointment reminder systems and reduce no-show rates through patient education.',
            'average_wait_time_minutes' => 'Optimize scheduling processes and increase provider capacity to reduce wait times.',
            'provider_utilization_rate' => 'Improve appointment scheduling and reduce administrative burden on providers.',
            'treatment_success_rate' => 'Enhance clinical protocols and provider training for better treatment outcomes.',
            'readmission_rate_30_days' => 'Strengthen care transition processes and post-discharge follow-up programs.',
            'average_revenue_per_user' => 'Optimize pricing strategies and improve service utilization rates.'
        ];

        return $recommendations[$kpi] ?? 'Review processes and implement best practices to improve performance.';
    }

    /**
     * Get trending KPIs (KPIs that are improving or declining significantly)
     */
    private function getTrendingKPIs($hospitalKey)
    {
        $trendService = app(TrendAnalysisService::class);

        $keyKPIs = [
            'patient_satisfaction_score',
            'total_revenue',
            'appointment_show_up_rate',
            'average_wait_time_minutes'
        ];

        $trending = [];
        foreach ($keyKPIs as $kpi) {
            $trend = $trendService->calculateTrend($kpi, 30, $hospitalKey);
            if ($trend['trend_direction'] !== 'stable' && $trend['trend_strength'] > 0.3) {
                $trending[] = [
                    'kpi' => $kpi,
                    'direction' => $trend['trend_direction'],
                    'strength' => $trend['trend_strength'],
                    'change_percentage' => $trend['change_percentage']
                ];
            }
        }

        return $trending;
    }

    /**
     * Get KPI trend (simplified version)
     */
    private function getKPITrend($kpiName)
    {
        // This would typically analyze historical data
        // For now, return mock trend
        $trends = [
            'patient_satisfaction_score' => 'improving',
            'total_revenue' => 'stable',
            'appointment_show_up_rate' => 'declining',
            'average_wait_time_minutes' => 'improving'
        ];

        return $trends[$kpiName] ?? 'stable';
    }

    /**
     * Get period date for calculations
     */
    private function getPeriodDate($period)
    {
        switch ($period) {
            case 'monthly':
                return Carbon::now()->startOfMonth();
            case 'quarterly':
                return Carbon::now()->startOfQuarter();
            case 'yearly':
                return Carbon::now()->startOfYear();
            default:
                return Carbon::now();
        }
    }

    /**
     * Get specialty-specific benchmarks
     */
    public function getSpecialtyBenchmarks($specialty)
    {
        return $this->specialtyBenchmarks[$specialty] ?? $this->industryBenchmarks;
    }

    /**
     * Update benchmark data (admin function)
     */
    public function updateBenchmarks(array $newBenchmarks)
    {
        $this->industryBenchmarks = array_merge($this->industryBenchmarks, $newBenchmarks);
        Cache::flush(); // Clear cache when benchmarks are updated
        return true;
    }
}
