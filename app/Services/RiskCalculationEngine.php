<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClinicalIndicator;
use App\Models\EarlyWarningScore;
use App\Models\ClinicalAlert;
use App\Models\ClinicalAlertRule;
use App\Events\ClinicalAlertTriggered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RiskCalculationEngine
{
    protected $ewsService;
    protected $predictiveService;
    protected $predictiveRiskEngine;
    protected $cdsService;

    public function __construct(
        ClinicalEarlyWarningService $ewsService, 
        PredictiveAnalyticsService $predictiveService,
        PredictiveRiskEngine $predictiveRiskEngine,
        ClinicalDecisionSupportService $cdsService
    ) {
        $this->ewsService = $ewsService;
        $this->predictiveService = $predictiveService;
        $this->predictiveRiskEngine = $predictiveRiskEngine;
        $this->cdsService = $cdsService;
    }

    /**
     * Process new clinical data and recalculate risks
     */
    public function processPatientData(User $patient)
    {
        DB::beginTransaction();
        try {
            // 1. Fetch latest vitals
            $vitals = $this->getLatestVitals($patient);
            
            // 2. Calculate NEWS2 (Scale 1 and Scale 2)
            $news2 = $this->ewsService->calculateNEWS2($vitals);
            $this->saveScore($patient, 'news2', $news2);

            // 3. Calculate qSOFA/Sepsis
            $qsofa = $this->ewsService->calculateQSOFA($vitals);
            $this->saveScore($patient, 'sepsis', $qsofa);

            // 4. Calculate AKI Risk
            $aki = $this->ewsService->calculateAKIRisk($patient);
            $this->saveScore($patient, 'aki', $aki);

            // 5. Predictive Trends
            $news2Trend = $this->predictiveRiskEngine->predictTrend($patient, 'news2_score');
            $rapidDeterioration = $this->predictiveRiskEngine->detectRapidDeterioration($patient);

            // 6. Check for alerts
            $this->evaluateAlertRules($patient, 'news2', $news2);
            $this->evaluateAlertRules($patient, 'sepsis', $qsofa);
            $this->evaluateAlertRules($patient, 'aki', $aki);

            foreach ($rapidDeterioration as $deterioration) {
                $this->triggerPredictiveAlert($patient, $deterioration);
            }

            // 7. Generate AI Insights if risk is high
            if ($news2['score'] >= 5 || $qsofa['score'] >= 2 || !empty($rapidDeterioration)) {
                $this->cdsService->generateClinicalInsights($patient, [
                    'news2' => $news2,
                    'qsofa' => $qsofa,
                    'aki' => $aki,
                    'trend' => $news2Trend,
                    'rapid_deterioration' => $rapidDeterioration
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing clinical data for patient {$patient->id}: " . $e->getMessage());
            throw $e; // Re-throw to allow job retry if applicable
        }
    }

    protected function triggerPredictiveAlert(User $patient, array $deterioration)
    {
        $alert = ClinicalAlert::create([
            'patient_id' => $patient->id,
            'severity' => $deterioration['severity'],
            'status' => 'triggered',
            'message' => $deterioration['message'],
            'trigger_data' => $deterioration,
            'triggered_at' => now(),
        ]);

        event(new ClinicalAlertTriggered($alert));
    }

    protected function getLatestVitals(User $patient): array
    {
        $indicators = ClinicalIndicator::where('patient_id', $patient->id)
            ->where('type', 'vital_sign')
            ->where('measured_at', '>=', now()->subHours(4))
            ->orderBy('measured_at', 'desc')
            ->get()
            ->groupBy('name');

        $vitals = [];
        foreach ($indicators as $name => $group) {
            $vitals[$name] = (float)$group->first()->value;
        }

        return $vitals;
    }

    protected function saveScore(User $patient, string $type, array $result)
    {
        EarlyWarningScore::create([
            'patient_id' => $patient->id,
            'algorithm_type' => $type,
            'score' => $result['score'],
            'risk_level' => $result['risk_level'],
            'contributing_factors' => $result['breakdown'],
            'calculated_at' => now(),
        ]);
    }

    protected function evaluateAlertRules(User $patient, string $type, array $result)
    {
        $rules = ClinicalAlertRule::where('algorithm_type', $type)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $triggered = false;
            if ($rule->threshold_min !== null && $result['score'] >= $rule->threshold_min) {
                $triggered = true;
            }
            if ($rule->threshold_max !== null && $result['score'] <= $rule->threshold_max) {
                $triggered = true;
            }

            if ($triggered) {
                $this->triggerAlert($patient, $rule, $result);
            }
        }
    }

    protected function triggerAlert(User $patient, ClinicalAlertRule $rule, array $result)
    {
        $alert = ClinicalAlert::create([
            'patient_id' => $patient->id,
            'rule_id' => $rule->id,
            'severity' => $rule->severity,
            'status' => 'triggered',
            'message' => "High risk detected for {$patient->name} using {$rule->algorithm_type}. Score: {$result['score']}",
            'trigger_data' => $result,
            'triggered_at' => now(),
        ]);

        // Broadcast the alert
        event(new ClinicalAlertTriggered($alert));
    }
}
