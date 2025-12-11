<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ForecastingService
{
    protected $cacheTtl = 3600; // 1 hour cache

    /**
     * Generate forecast for a specific KPI
     */
    public function forecastKPI($kpiName, $forecastDays = 30, $historicalDays = 90, $hospitalKey = 1)
    {
        $cacheKey = "forecast_{$kpiName}_{$forecastDays}_{$historicalDays}_{$hospitalKey}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($kpiName, $forecastDays, $historicalDays, $hospitalKey) {
            return $this->generateForecast($kpiName, $forecastDays, $historicalDays, $hospitalKey);
        });
    }

    /**
     * Generate comprehensive forecast using multiple methods
     */
    private function generateForecast($kpiName, $forecastDays, $historicalDays, $hospitalKey)
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($historicalDays);

        $historicalData = $this->getHistoricalData($kpiName, $startDate, $endDate, $hospitalKey);

        if ($historicalData->isEmpty()) {
            return $this->getEmptyForecastResult();
        }

        $values = $historicalData->pluck('value')->filter()->values();

        // Generate forecasts using different methods
        $linearRegression = $this->linearRegressionForecast($values, $forecastDays);
        $movingAverage = $this->movingAverageForecast($values, $forecastDays);
        $exponentialSmoothing = $this->exponentialSmoothingForecast($values, $forecastDays);
        $seasonalNaive = $this->seasonalNaiveForecast($values, $forecastDays);

        // Ensemble forecast (weighted average)
        $ensemble = $this->calculateEnsembleForecast([
            $linearRegression,
            $movingAverage,
            $exponentialSmoothing,
            $seasonalNaive
        ]);

        return [
            'kpi_name' => $kpiName,
            'forecast_period_days' => $forecastDays,
            'historical_period_days' => $historicalDays,
            'forecast_methods' => [
                'linear_regression' => $linearRegression,
                'moving_average' => $movingAverage,
                'exponential_smoothing' => $exponentialSmoothing,
                'seasonal_naive' => $seasonalNaive,
                'ensemble' => $ensemble
            ],
            'forecast_accuracy' => $this->calculateForecastAccuracy($values),
            'confidence_intervals' => $this->calculateConfidenceIntervals($ensemble, $values),
            'historical_data' => $historicalData,
            'recommendation' => $this->getForecastRecommendation($linearRegression, $movingAverage, $exponentialSmoothing, $seasonalNaive)
        ];
    }

    /**
     * Get historical data for forecasting
     */
    private function getHistoricalData($kpiName, Carbon $startDate, Carbon $endDate, $hospitalKey)
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
     * Linear regression forecasting
     */
    private function linearRegressionForecast(Collection $values, $forecastDays)
    {
        if ($values->count() < 2) return array_fill(0, $forecastDays, null);

        $slope = $this->calculateLinearRegressionSlope($values);
        $intercept = $this->calculateLinearRegressionIntercept($values, $slope);

        $forecast = [];
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecast[] = max(0, $intercept + ($slope * ($values->count() + $i - 1)));
        }

        return $forecast;
    }

    /**
     * Moving average forecasting
     */
    private function movingAverageForecast(Collection $values, $forecastDays, $window = 7)
    {
        if ($values->count() < $window) return array_fill(0, $forecastDays, null);

        $recentValues = $values->slice(-$window);
        $average = $recentValues->avg();

        return array_fill(0, $forecastDays, $average);
    }

    /**
     * Exponential smoothing forecasting
     */
    private function exponentialSmoothingForecast(Collection $values, $forecastDays, $alpha = 0.3)
    {
        if ($values->isEmpty()) return array_fill(0, $forecastDays, null);

        $smoothed = [$values->first()];

        foreach ($values->slice(1) as $value) {
            $smoothed[] = $alpha * $value + (1 - $alpha) * end($smoothed);
        }

        $lastSmoothed = end($smoothed);
        return array_fill(0, $forecastDays, $lastSmoothed);
    }

    /**
     * Seasonal naive forecasting (weekly pattern)
     */
    private function seasonalNaiveForecast(Collection $values, $forecastDays)
    {
        if ($values->count() < 7) return array_fill(0, $forecastDays, null);

        $forecast = [];
        for ($i = 0; $i < $forecastDays; $i++) {
            $seasonalIndex = $i % 7;
            $historicalIndex = $values->count() - 7 + $seasonalIndex;
            $forecast[] = $historicalIndex >= 0 ? $values->get($historicalIndex) : $values->avg();
        }

        return $forecast;
    }

    /**
     * Calculate ensemble forecast (weighted average of all methods)
     */
    private function calculateEnsembleForecast(array $forecasts)
    {
        $forecastDays = count($forecasts[0]);
        $ensemble = [];

        for ($i = 0; $i < $forecastDays; $i++) {
            $validValues = array_filter(array_column($forecasts, $i), function($value) {
                return $value !== null;
            });

            if (empty($validValues)) {
                $ensemble[] = null;
            } else {
                $ensemble[] = array_sum($validValues) / count($validValues);
            }
        }

        return $ensemble;
    }

    /**
     * Calculate forecast accuracy using historical data
     */
    private function calculateForecastAccuracy(Collection $values)
    {
        if ($values->count() < 14) return null;

        // Use last 7 days as test data
        $testData = $values->slice(-7);
        $trainingData = $values->slice(0, -7);

        if ($trainingData->isEmpty()) return null;

        // Simple moving average forecast for testing
        $window = min(7, $trainingData->count());
        $recentValues = $trainingData->slice(-$window);
        $predictedAverage = $recentValues->avg();

        $predictions = array_fill(0, $testData->count(), $predictedAverage);

        // Calculate MAPE (Mean Absolute Percentage Error)
        $mape = 0;
        $validCount = 0;

        foreach ($testData as $i => $actual) {
            if ($actual > 0) {
                $mape += abs(($actual - $predictions[$i]) / $actual) * 100;
                $validCount++;
            }
        }

        return $validCount > 0 ? $mape / $validCount : null;
    }

    /**
     * Calculate confidence intervals for forecast
     */
    private function calculateConfidenceIntervals(array $forecast, Collection $historicalValues)
    {
        if (empty($forecast) || $historicalValues->isEmpty()) {
            return ['lower' => [], 'upper' => []];
        }

        $stdDev = $this->calculateStandardDeviation($historicalValues);
        $confidenceMultiplier = 1.96; // 95% confidence interval

        $lower = [];
        $upper = [];

        foreach ($forecast as $value) {
            if ($value === null) {
                $lower[] = null;
                $upper[] = null;
            } else {
                $lower[] = max(0, $value - ($confidenceMultiplier * $stdDev));
                $upper[] = $value + ($confidenceMultiplier * $stdDev);
            }
        }

        return ['lower' => $lower, 'upper' => $upper];
    }

    /**
     * Get forecast recommendation based on method performance
     */
    private function getForecastRecommendation($linear, $moving, $exp, $seasonal)
    {
        // Simple recommendation based on forecast stability
        $methods = [
            'linear_regression' => $this->calculateForecastVariance($linear),
            'moving_average' => $this->calculateForecastVariance($moving),
            'exponential_smoothing' => $this->calculateForecastVariance($exp),
            'seasonal_naive' => $this->calculateForecastVariance($seasonal)
        ];

        asort($methods); // Sort by variance (lower is better)

        return [
            'recommended_method' => array_key_first($methods),
            'method_rankings' => array_keys($methods),
            'reasoning' => 'Based on forecast stability (lower variance indicates more consistent predictions)'
        ];
    }

    /**
     * Calculate forecast variance for stability assessment
     */
    private function calculateForecastVariance(array $forecast)
    {
        $validValues = array_filter($forecast, function($value) {
            return $value !== null;
        });

        if (count($validValues) < 2) return PHP_FLOAT_MAX;

        $mean = array_sum($validValues) / count($validValues);
        $variance = 0;

        foreach ($validValues as $value) {
            $variance += pow($value - $mean, 2);
        }

        return $variance / count($validValues);
    }

    /**
     * Helper methods for statistical calculations
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

    private function calculateLinearRegressionIntercept(Collection $values, $slope)
    {
        $meanY = $values->avg();
        $meanX = ($values->count() - 1) / 2;

        return $meanY - ($slope * $meanX);
    }

    private function calculateStandardDeviation(Collection $values)
    {
        $mean = $values->avg();
        $variance = $values->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();

        return sqrt($variance);
    }

    /**
     * Get empty forecast result structure
     */
    private function getEmptyForecastResult()
    {
        return [
            'kpi_name' => null,
            'forecast_period_days' => 0,
            'historical_period_days' => 0,
            'forecast_methods' => [],
            'forecast_accuracy' => null,
            'confidence_intervals' => ['lower' => [], 'upper' => []],
            'historical_data' => collect(),
            'recommendation' => null
        ];
    }
}
