<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClinicalIndicator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PredictiveRiskEngine
{
    /**
     * Predict future risk score based on recent trends
     * 
     * @param User $patient
     * @param string $indicatorName
     * @param int $hoursAhead
     * @return array [predicted_value, trend_direction, confidence]
     */
    public function predictTrend(User $patient, string $indicatorName, int $hoursAhead = 4): array
    {
        try {
            $data = ClinicalIndicator::where('patient_id', $patient->id)
                ->where('name', $indicatorName)
                ->where('measured_at', '>=', now()->subHours(12))
                ->orderBy('measured_at', 'asc')
                ->get();

            if ($data->count() < 3) {
                return [
                    'predicted_value' => null,
                    'trend_direction' => 'stable',
                    'confidence' => 'low',
                    'reason' => 'insufficient_data'
                ];
            }

            $points = $data->map(function ($item) {
                return [
                    'x' => Carbon::parse($item->measured_at)->timestamp,
                    'y' => (float)$item->value
                ];
            });

            $regression = $this->linearRegression($points->toArray());
            
            $futureTimestamp = now()->addHours($hoursAhead)->timestamp;
            $predictedValue = $regression['slope'] * $futureTimestamp + $regression['intercept'];
            
            // Sanity check for predicted value (don't allow negative or extreme values)
            $predictedValue = max(0, min($predictedValue, 1000)); // Adjust max based on indicator

            $trend = 'stable';
            if ($regression['slope'] > 0.00001) $trend = 'rising'; // Adjusted for timestamp scale
            elseif ($regression['slope'] < -0.00001) $trend = 'falling';

            return [
                'predicted_value' => $predictedValue,
                'trend_direction' => $trend,
                'slope' => $regression['slope'],
                'r_squared' => $regression['r_squared'],
                'confidence' => $regression['r_squared'] > 0.7 ? 'high' : ($regression['r_squared'] > 0.4 ? 'medium' : 'low')
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error predicting trend for {$indicatorName} (Patient: {$patient->id}): " . $e->getMessage());
            return [
                'predicted_value' => null,
                'trend_direction' => 'stable',
                'confidence' => 'error'
            ];
        }
    }

    /**
     * Identify "Rapid Deterioration" patterns
     */
    public function detectRapidDeterioration(User $patient): array
    {
        try {
            $recentVitals = ClinicalIndicator::where('patient_id', $patient->id)
                ->where('type', 'vital_sign')
                ->where('measured_at', '>=', now()->subHours(2))
                ->orderBy('measured_at', 'desc')
                ->get()
                ->groupBy('name');

            $alerts = [];

            // Pattern 1: Shock Index (HR / SBP) > 1.0
            $hr = $recentVitals->get('pulse')?->first()?->value ?? $recentVitals->get('heart_rate')?->first()?->value;
            $sbp = $recentVitals->get('systolic_bp')?->first()?->value;

            if ($hr && $sbp && $sbp > 0 && ($hr / $sbp) > 1.0) {
                $alerts[] = [
                    'type' => 'rapid_deterioration',
                    'pattern' => 'elevated_shock_index',
                    'message' => 'High Shock Index detected (HR/SBP > 1.0), suggesting potential occult shock.',
                    'severity' => 'high'
                ];
            }

            // Pattern 2: Sudden RR increase (> 5 bpm in 1h)
            $rrData = $recentVitals->get('respiratory_rate') ?? $recentVitals->get('respiration_rate');
            if ($rrData && $rrData->count() >= 2) {
                $latest = (float)$rrData->first()->value;
                $previous = (float)$rrData->last()->value;
                if ($latest - $previous >= 5) {
                    $alerts[] = [
                        'type' => 'rapid_deterioration',
                        'pattern' => 'tachypnea_surge',
                        'message' => 'Rapid increase in respiratory rate detected.',
                        'severity' => 'medium'
                    ];
                }
            }

            return $alerts;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error detecting rapid deterioration (Patient: {$patient->id}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Simple Linear Regression
     */
    protected function linearRegression(array $points): array
    {
        $n = count($points);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        foreach ($points as $p) {
            $sumX += $p['x'];
            $sumY += $p['y'];
            $sumXY += ($p['x'] * $p['y']);
            $sumX2 += ($p['x'] * $p['x']);
            $sumY2 += ($p['y'] * $p['y']);
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator == 0) return ['slope' => 0, 'intercept' => $sumY / $n, 'r_squared' => 0];

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate R-squared
        $yMean = $sumY / $n;
        $ssTot = 0;
        $ssRes = 0;
        foreach ($points as $p) {
            $yPred = $slope * $p['x'] + $intercept;
            $ssTot += pow($p['y'] - $yMean, 2);
            $ssRes += pow($p['y'] - $yPred, 2);
        }
        
        $rSquared = $ssTot == 0 ? 0 : 1 - ($ssRes / $ssTot);

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => $rSquared
        ];
    }
}
