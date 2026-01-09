<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClinicalIndicator;
use App\Models\EarlyWarningScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClinicalEarlyWarningService
{
    /**
     * Calculate NEWS2 (National Early Warning Score 2)
     * 
     * @param array $vitals [respiratory_rate, spo2, air_or_oxygen, systolic_bp, pulse, consciousness, temperature]
     * @return array [score, risk_level, breakdown]
     */
    public function calculateNEWS2(array $vitals): array
    {
        $score = 0;
        $breakdown = [];

        // 1. Respiratory Rate
        $rr = $vitals['respiratory_rate'] ?? $vitals['respiration_rate'] ?? null;
        if ($rr !== null) {
            $rrScore = 0;
            if ($rr <= 8 || $rr >= 25) $rrScore = 3;
            elseif ($rr >= 21 && $rr <= 24) $rrScore = 2;
            elseif ($rr >= 9 && $rr <= 11) $rrScore = 1;
            $score += $rrScore;
            $breakdown['respiratory_rate'] = $rrScore;
        }

        // 2. SpO2 (Scale 1 - default)
        $spo2 = $vitals['spo2'] ?? $vitals['oxygen_saturation'] ?? null;
        if ($spo2 !== null) {
            $spo2Score = 0;
            if ($spo2 <= 91) $spo2Score = 3;
            elseif ($spo2 >= 92 && $spo2 <= 93) $spo2Score = 2;
            elseif ($spo2 >= 94 && $spo2 <= 95) $spo2Score = 1;
            $score += $spo2Score;
            $breakdown['spo2'] = $spo2Score;
        }

        // 3. Air or Oxygen
        $oxygen = $vitals['air_or_oxygen'] ?? 'air';
        if ($oxygen === 'oxygen') {
            $score += 2;
            $breakdown['air_or_oxygen'] = 2;
        } else {
            $breakdown['air_or_oxygen'] = 0;
        }

        // 4. Systolic Blood Pressure
        $sbp = $vitals['systolic_bp'] ?? null;
        if ($sbp !== null) {
            $sbpScore = 0;
            if ($sbp <= 90 || $sbp >= 220) $sbpScore = 3;
            elseif ($sbp >= 91 && $sbp <= 100) $sbpScore = 2;
            elseif ($sbp >= 101 && $sbp <= 110) $sbpScore = 1;
            $score += $sbpScore;
            $breakdown['systolic_bp'] = $sbpScore;
        }

        // 5. Pulse
        $pulse = $vitals['pulse'] ?? $vitals['heart_rate'] ?? null;
        if ($pulse !== null) {
            $pulseScore = 0;
            if ($pulse <= 40 || $pulse >= 131) $pulseScore = 3;
            elseif ($pulse >= 111 && $pulse <= 130) $pulseScore = 2;
            elseif ($pulse >= 41 && $pulse <= 50 || $pulse >= 91 && $pulse <= 110) $pulseScore = 1;
            $score += $pulseScore;
            $breakdown['pulse'] = $pulseScore;
        }

        // 6. Consciousness (ACVPU)
        $consciousness = $vitals['consciousness'] ?? $vitals['avpu'] ?? 'A';
        if (!in_array($consciousness, ['A', 'Alert'])) {
            $score += 3;
            $breakdown['consciousness'] = 3;
        } else {
            $breakdown['consciousness'] = 0;
        }

        // 7. Temperature
        $temp = $vitals['temperature'] ?? null;
        if ($temp !== null) {
            $tempScore = 0;
            if ($temp <= 35.0) $tempScore = 3;
            elseif ($temp >= 39.1) $tempScore = 2;
            elseif ($temp >= 35.1 && $temp <= 36.0 || $temp >= 38.1 && $temp <= 39.0) $tempScore = 1;
            $score += $tempScore;
            $breakdown['temperature'] = $tempScore;
        }

        $riskLevel = 'low';
        if ($score >= 7) $riskLevel = 'high';
        elseif ($score >= 5) $riskLevel = 'medium';
        elseif ($score >= 1) $riskLevel = 'low-medium';

        return [
            'score' => $score,
            'risk_level' => $riskLevel,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Calculate NEWS2 Scale 2 (for patients with hypercapnic respiratory failure)
     * 
     * @param array $vitals
     * @return array
     */
    public function calculateNEWS2Scale2(array $vitals): array
    {
        $result = $this->calculateNEWS2($vitals);
        
        // Adjust SpO2 for Scale 2 (88-92%)
        $spo2 = $vitals['spo2'] ?? $vitals['oxygen_saturation'] ?? null;
        if ($spo2 !== null) {
            $spo2Score = 0;
            if ($spo2 <= 83) $spo2Score = 3;
            elseif ($spo2 >= 84 && $spo2 <= 85) $spo2Score = 2;
            elseif ($spo2 >= 86 && $spo2 <= 87) $spo2Score = 1;
            elseif ($spo2 >= 93 && $spo2 <= 94) $spo2Score = 1;
            elseif ($spo2 >= 95 && $spo2 <= 96) $spo2Score = 2;
            elseif ($spo2 >= 97) $spo2Score = 3;
            
            // Replace Scale 1 SpO2 score
            $result['score'] = $result['score'] - ($result['breakdown']['spo2'] ?? 0) + $spo2Score;
            $result['breakdown']['spo2_scale2'] = $spo2Score;
            unset($result['breakdown']['spo2']);
        }

        // Recalculate risk level
        $result['risk_level'] = $this->determineNEWS2RiskLevel($result['score']);
        
        return $result;
    }

    protected function determineNEWS2RiskLevel(int $score): string
    {
        if ($score >= 7) return 'high';
        if ($score >= 5) return 'medium';
        if ($score >= 1) return 'low-medium';
        return 'low';
    }

    /**
     * Sepsis Detection (qSOFA)
     */
    public function calculateQSOFA(array $vitals): array
    {
        $score = 0;
        $breakdown = [];

        // 1. Altered mental status (GCS < 15 or non-Alert AVPU)
        $gcs = $vitals['gcs'] ?? null;
        $avpu = $vitals['avpu'] ?? $vitals['consciousness'] ?? 'A';
        
        if (($gcs !== null && $gcs < 15) || !in_array($avpu, ['A', 'Alert'])) {
            $score += 1;
            $breakdown['altered_mental_status'] = 1;
        }

        // 2. Respiratory rate >= 22
        $rr = $vitals['respiratory_rate'] ?? $vitals['respiration_rate'] ?? 0;
        if ($rr >= 22) {
            $score += 1;
            $breakdown['respiratory_rate'] = 1;
        }

        // 3. Systolic BP <= 100
        $sbp = $vitals['systolic_bp'] ?? 200;
        if ($sbp <= 100) {
            $score += 1;
            $breakdown['systolic_bp'] = 1;
        }

        return [
            'score' => $score,
            'risk_level' => $score >= 2 ? 'high' : 'low',
            'breakdown' => $breakdown
        ];
    }

    /**
     * SIRS Criteria
     */
    public function calculateSIRS(array $vitals, array $labs = []): array
    {
        $score = 0;
        $breakdown = [];

        // 1. Temp > 38 or < 36
        $temp = $vitals['temperature'] ?? 37;
        if ($temp > 38 || $temp < 36) {
            $score += 1;
            $breakdown['temperature'] = 1;
        }

        // 2. HR > 90
        $hr = $vitals['pulse'] ?? 0;
        if ($hr > 90) {
            $score += 1;
            $breakdown['heart_rate'] = 1;
        }

        // 3. RR > 20 or PaCO2 < 32
        $rr = $vitals['respiratory_rate'] ?? 0;
        if ($rr > 20) {
            $score += 1;
            $breakdown['respiratory_rate'] = 1;
        }

        // 4. WBC > 12000 or < 4000 or > 10% bands
        $wbc = $labs['wbc'] ?? 7000;
        if ($wbc > 12000 || $wbc < 4000) {
            $score += 1;
            $breakdown['wbc'] = 1;
        }

        return [
            'score' => $score,
            'risk_level' => $score >= 2 ? 'high' : 'low',
            'breakdown' => $breakdown
        ];
    }

    /**
     * Cardiac Event Prediction (Enhanced Trend Analysis)
     */
    public function predictCardiacEvent(User $patient): array
    {
        // Fetch recent vitals and labs
        $recentVitals = ClinicalIndicator::where('patient_id', $patient->id)
            ->where('type', 'vital_sign')
            ->where('measured_at', '>=', now()->subHours(24))
            ->orderBy('measured_at', 'desc')
            ->get();

        $troponin = ClinicalIndicator::where('patient_id', $patient->id)
            ->where('name', 'troponin')
            ->orderBy('measured_at', 'desc')
            ->first();

        $score = 0;
        $breakdown = [];

        if ($troponin && (float)$troponin->value > 0.04) {
            $score += 5;
            $breakdown['elevated_troponin'] = 5;
        }

        // Check for tachycardia trend
        $hrTrend = $recentVitals->where('name', 'pulse')->take(5);
        if ($hrTrend->count() >= 3) {
            $avgHr = $hrTrend->avg('value');
            if ($avgHr > 110) {
                $score += 3;
                $breakdown['severe_tachycardia_trend'] = 3;
            } elseif ($avgHr > 100) {
                $score += 2;
                $breakdown['tachycardia_trend'] = 2;
            }
        }

        // Check for BP drop trend
        $bpTrend = $recentVitals->where('name', 'systolic_bp')->take(5);
        if ($bpTrend->count() >= 3) {
            $first = $bpTrend->last()->value;
            $last = $bpTrend->first()->value;
            if ($first - $last > 20) {
                $score += 2;
                $breakdown['significant_bp_drop'] = 2;
            }
        }

        return [
            'score' => $score,
            'risk_level' => $score >= 7 ? 'critical' : ($score >= 4 ? 'high' : ($score >= 2 ? 'medium' : 'low')),
            'breakdown' => $breakdown
        ];
    }

    /**
     * AKI Risk Detection (KDIGO Criteria)
     */
    public function calculateAKIRisk(User $patient): array
    {
        $recentCreatinine = ClinicalIndicator::where('patient_id', $patient->id)
            ->where('name', 'creatinine')
            ->where('measured_at', '>=', now()->subDays(7))
            ->orderBy('measured_at', 'desc')
            ->get();

        if ($recentCreatinine->count() < 2) {
            return ['score' => 0, 'risk_level' => 'low', 'breakdown' => ['insufficient_data' => 0]];
        }

        $latest = (float)$recentCreatinine->first()->value;
        $baseline = (float)$recentCreatinine->last()->value; // Simplification: using oldest in 7 days as baseline

        $score = 0;
        $breakdown = [];

        // KDIGO Stage 1: 1.5-1.9x baseline or >= 0.3 mg/dL increase in 48h
        $increase = $latest - $baseline;
        $ratio = $latest / ($baseline ?: 1);

        if ($ratio >= 3.0) {
            $score = 3;
            $breakdown['kdigo_stage_3'] = 3;
        } elseif ($ratio >= 2.0) {
            $score = 2;
            $breakdown['kdigo_stage_2'] = 2;
        } elseif ($ratio >= 1.5 || $increase >= 0.3) {
            $score = 1;
            $breakdown['kdigo_stage_1'] = 1;
        }

        return [
            'score' => $score,
            'risk_level' => $score >= 3 ? 'critical' : ($score >= 2 ? 'high' : ($score >= 1 ? 'medium' : 'low')),
            'breakdown' => $breakdown
        ];
    }

    /**
     * Stroke Detection (FAST Criteria + Neuro Indicators)
     */
    public function detectStroke(User $patient, array $notes = []): array
    {
        $score = 0;
        $breakdown = [];

        // Analyze clinical notes for keywords
        $keywords = ['facial droop', 'arm weakness', 'speech difficulty', 'slurred speech', 'numbness'];
        foreach ($notes as $note) {
            foreach ($keywords as $keyword) {
                if (stripos($note, $keyword) !== false) {
                    $score += 2;
                    $breakdown['keyword_found: ' . $keyword] = 2;
                }
            }
        }

        return [
            'score' => $score,
            'risk_level' => $score >= 2 ? 'high' : 'low',
            'breakdown' => $breakdown
        ];
    }
}
