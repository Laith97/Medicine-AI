<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrendAnalysisService
{
    protected $cacheTtl = 3600; // 1 hour cache

    /**
     * Calculate trend analysis for a specific KPI over time
     */
    public function calculateTrend($kpiName, $days = 30, $hospitalKey = 1)
    {
        $cacheKey = "trend_{$kpiName}_{$days}_{$hospitalKey}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($kpiName, $days, $hospitalKey) {
            return $this->analyzeTrend($kpiName, $days, $hospitalKey);
        });
    }

    /**
     * Analyze trend with statistical calculations
     */
    private function analyzeTrend($kpiName, $days, $hospitalKey)
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        $data = $this->getKPITimeSeries($kpiName, $startDate, $endDate, $hospitalKey);

        if ($data->isEmpty()) {
            return $this->getEmptyTrendResult();
        }

        $values = $data->pluck('value')->filter()->values();

        return [
            'kpi_name' => $kpiName,
            'period_days' => $days,
            'data_points' => $data->count(),
            'trend_direction' => $this->calculateTrendDirection($values),
            'trend_strength' => $this->calculateTrendStrength($values),
            'volatility' => $this->calculateVolatility($values),
            'seasonal_pattern' => $this->detectSeasonalPattern($data),
            'forecast_next_value' => $this->simpleLinearRegressionForecast($values),
            'moving_average_7d' => $this->calculateMovingAverage($values, 7),
            'moving_average_30d' => $this->calculateMovingAverage($values, 30),
            'change_percentage' => $this->calculatePeriodChange($values),
            'data' => $data
        ];
    }

    /**
     * Get KPI time series data
     */
    private function getKPITimeSeries($kpiName, Carbon $startDate, Carbon $endDate, $hospitalKey)
    {
        $dateKeys = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateKeys[] = (int)$date->format('Ymd');
        }

        return DB::table('agg_daily_kpis')
            ->whereIn('date_key', $dateKeys)
            ->where('hospital_key', $hospitalKey)
            ->whereNotNull($kpiName)
            ->orderBy('date_key')
            ->select('date_key', "{$kpiName} as value")
            ->get()
            ->map(function ($item) {
                $item->date = Carbon::createFromFormat('Ymd', $item->date_key)->format('Y-m-d');
                return $item;
            });
    }

    /**
     * Calculate trend direction using linear regression slope
     */
    private function calculateTrendDirection(Collection $values)
    {
        if ($values->count() < 2) return 'insufficient_data';

        $slope = $this->calculateLinearRegressionSlope($values);

        if ($slope > 0.01) return 'increasing';
        if ($slope < -0.01) return 'decreasing';
        return 'stable';
    }

    /**
     * Calculate trend strength (R-squared value)
     */
    private function calculateTrendStrength(Collection $values)
    {
        if ($values->count() < 2) return 0;

        return $this->calculateRSquared($values);
    }

    /**
     * Calculate volatility (coefficient of variation)
     */
    private function calculateVolatility(Collection $values)
    {
        if ($values->count() < 2) return 0;

        $mean = $values->avg();
        if ($mean == 0) return 0;

        $stdDev = $this->calculateStandardDeviation($values);
        return ($stdDev / $mean) * 100;
    }

    /**
     * Detect seasonal patterns (basic weekday vs weekend)
     */
    private function detectSeasonalPattern(Collection $data)
    {
        if ($data->count() < 14) return 'insufficient_data';

        $weekdayValues = [];
        $weekendValues = [];

        foreach ($data as $item) {
            $date = Carbon::parse($item->date);
            if ($date->isWeekend()) {
                $weekendValues[] = $item->value;
            } else {
                $weekdayValues[] = $item->value;
            }
        }

        if (empty($weekdayValues) || empty($weekendValues)) {
            return 'no_pattern_detected';
        }

        $weekdayAvg = array_sum($weekdayValues) / count($weekdayValues);
        $weekendAvg = array_sum($weekendValues) / count($weekendValues);

        $difference = (($weekendAvg - $weekdayAvg) / $weekdayAvg) * 100;

        if (abs($difference) > 10) {
            return $difference > 0 ? 'weekend_higher' : 'weekday_higher';
        }

        return 'no_pattern_detected';
    }

    /**
     * Simple linear regression forecast
     */
    private function simpleLinearRegressionForecast(Collection $values)
    {
        if ($values->count() < 2) return null;

        $slope = $this->calculateLinearRegressionSlope($values);
        $intercept = $this->calculateLinearRegressionIntercept($values, $slope);

        return $intercept + ($slope * ($values->count() + 1));
    }

    /**
     * Calculate moving average
     */
    private function calculateMovingAverage(Collection $values, $window)
    {
        if ($values->count() < $window) return null;

        $recentValues = $values->slice(-$window);
        return $recentValues->avg();
    }

    /**
     * Calculate period-over-period change
     */
    private function calculatePeriodChange(Collection $values)
    {
        if ($values->count() < 2) return 0;

        $firstHalf = $values->slice(0, $values->count() / 2)->avg();
        $secondHalf = $values->slice($values->count() / 2)->avg();

        if ($firstHalf == 0) return 0;

        return (($secondHalf - $firstHalf) / $firstHalf) * 100;
    }

    /**
     * Calculate linear regression slope
     */
    private function calculateLinearRegressionSlope(Collection $values)
    {
        $n = $values->count();
        $sumX = $n * ($n - 1) / 2;
        $sumY = $values->sum();
        $sumXY = 0;
        $sumXX = $n * ($n - 1) * (2 * $n - 1) / 6;

        foreach ($values as $i => $value) {
            $sumXY += $i * $value;
        }

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = $n * $sumXX - $sumX * $sumX;

        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Calculate linear regression intercept
     */
    private function calculateLinearRegressionIntercept(Collection $values, $slope)
    {
        $meanY = $values->avg();
        $meanX = ($values->count() - 1) / 2;

        return $meanY - ($slope * $meanX);
    }

    /**
     * Calculate R-squared (coefficient of determination)
     */
    private function calculateRSquared(Collection $values)
    {
        if ($values->count() < 2) return 0;

        $slope = $this->calculateLinearRegressionSlope($values);
        $intercept = $this->calculateLinearRegressionIntercept($values, $slope);

        $mean = $values->avg();
        $ssRes = 0;
        $ssTot = 0;

        foreach ($values as $i => $value) {
            $predicted = $intercept + ($slope * $i);
            $ssRes += pow($value - $predicted, 2);
            $ssTot += pow($value - $mean, 2);
        }

        return $ssTot != 0 ? 1 - ($ssRes / $ssTot) : 0;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation(Collection $values)
    {
        $mean = $values->avg();
        $variance = $values->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();

        return sqrt($variance);
    }

    /**
     * Get empty trend result structure
     */
    private function getEmptyTrendResult()
    {
        return [
            'kpi_name' => null,
            'period_days' => 0,
            'data_points' => 0,
            'trend_direction' => 'insufficient_data',
            'trend_strength' => 0,
            'volatility' => 0,
            'seasonal_pattern' => 'insufficient_data',
            'forecast_next_value' => null,
            'moving_average_7d' => null,
            'moving_average_30d' => null,
            'change_percentage' => 0,
            'data' => collect()
        ];
    }

    /**
     * Calculate multiple KPI trends at once
     */
    public function calculateMultipleTrends(array $kpiNames, $days = 30, $hospitalKey = 1)
    {
        $results = [];
        foreach ($kpiNames as $kpiName) {
            $results[$kpiName] = $this->calculateTrend($kpiName, $days, $hospitalKey);
        }
        return $results;
    }
}
