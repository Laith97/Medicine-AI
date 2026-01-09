<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClinicalIndicator;
use App\Models\EarlyWarningScore;
use App\Models\ClinicalAlert;
use App\Models\ClinicalAlertRule;
use App\Events\ClinicalAlertTriggered;
use Illuminate\Support\Facades\Log;

class RiskCalculationEngine
{
    protected $ewsService;
    protected $predictiveService;

    public function __construct(ClinicalEarlyWarningService $ewsService, PredictiveAnalyticsService $predictiveService)
    {
        $this->ewsService = $ewsService;
        $this->predictiveService = $predictiveService;
    }

    /**
     * Process new clinical data and recalculate risks
     */
    public function processPatientData(User $patient)
    {
        // 1. Fetch latest vitals
        $vitals = $this->getLatestVitals($patient);
        
        // 2. Calculate NEWS2
        $news2 = $this->ewsService->calculateNEWS2($vitals);
        $this->saveScore($patient, 'news2', $news2);

        // 3. Calculate qSOFA/Sepsis
        $qsofa = $this->ewsService->calculateQSOFA($vitals);
        $this->saveScore($patient, 'sepsis', $qsofa);

        // 4. Check for alerts
        $this->evaluateAlertRules($patient, 'news2', $news2);
        $this->evaluateAlertRules($patient, 'sepsis', $qsofa);

        // 5. Integrate with PredictiveAnalyticsService for enhanced modeling
        // (Assuming predictRisks returns an array of risks)
        // $enhancedRisks = $this->predictiveService->predictRisks($patient, $latestAppointment);
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
