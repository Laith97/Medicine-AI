<?php

namespace App\Services;

use App\Models\ClinicalDecisionRule;
use App\Models\TreatmentPathway;
use App\Models\User;
use App\Models\ClinicalIndicator;
use App\Events\ClinicalAlertTriggered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ClinicalDecisionSupportService
{
    /**
     * Evaluate clinical rules for a patient
     */
    public function evaluateRules(int $patientId): array
    {
        $patient = User::findOrFail($patientId);
        $activeRules = ClinicalDecisionRule::active()->orderBy('priority', 'desc')->get();
        $triggeredAlerts = [];

        foreach ($activeRules as $rule) {
            if ($this->shouldTrigger($rule, $patientId)) {
                $alert = $this->triggerAction($rule, $patientId);
                $triggeredAlerts[] = $alert;
            }
        }

        return $triggeredAlerts;
    }

    /**
     * Check if a rule should be triggered based on patient data
     */
    private function shouldTrigger(ClinicalDecisionRule $rule, int $patientId): bool
    {
        $conditions = $rule->trigger_conditions;
        if (empty($conditions)) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $patientId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition against patient data
     */
    private function evaluateCondition(array $condition, int $patientId): bool
    {
        $type = $condition['type'] ?? null;
        $indicatorName = $condition['indicator'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (!$type || !$indicatorName || !$operator) {
            return false;
        }

        $latestIndicator = ClinicalIndicator::where('patient_id', $patientId)
            ->where('name', $indicatorName)
            ->orderBy('measured_at', 'desc')
            ->first();

        if (!$latestIndicator) {
            return false;
        }

        switch ($operator) {
            case '>':
                return $latestIndicator->value > $value;
            case '<':
                return $latestIndicator->value < $value;
            case '>=':
                return $latestIndicator->value >= $value;
            case '<=':
                return $latestIndicator->value <= $value;
            case '==':
                return $latestIndicator->value == $value;
            default:
                return false;
        }
    }

    /**
     * Trigger the action defined in the rule
     */
    private function triggerAction(ClinicalDecisionRule $rule, int $patientId): array
    {
        $payload = $rule->action_payload;
        $payload['rule_id'] = $rule->id;
        $payload['rule_name'] = $rule->name;
        $payload['patient_id'] = $patientId;

        if ($rule->action_type === 'alert') {
            $alert = \App\Models\ClinicalAlert::create([
                'patient_id' => $patientId,
                'decision_rule_id' => $rule->id,
                'severity' => $payload['severity'] ?? 'yellow',
                'status' => 'triggered',
                'message' => $payload['message'] ?? "Clinical rule triggered: {$rule->name}",
                'trigger_data' => $payload,
                'triggered_at' => now(),
            ]);

            event(new ClinicalAlertTriggered($alert));
        }

        Log::info("Clinical Rule Triggered: {$rule->name} for patient {$patientId}");

        return $payload;
    }

    /**
     * Get active treatment pathway for a condition
     */
    public function getPathway(string $conditionCode): ?TreatmentPathway
    {
        return TreatmentPathway::active()
            ->byCondition($conditionCode)
            ->first();
    }

    /**
     * Monitor protocol adherence for a patient
     */
    public function monitorAdherence(int $patientId, string $conditionCode): array
    {
        $pathway = $this->getPathway($conditionCode);
        if (!$pathway) {
            return ['status' => 'no_pathway'];
        }

        // Simplified adherence monitoring logic
        return [
            'status' => 'monitoring',
            'pathway_name' => $pathway->name,
            'adherence_score' => 0.95, // Placeholder
            'next_steps' => $pathway->steps[0] ?? null
        ];
    }

    /**
     * Generate AI-powered clinical insights based on risk data
     */
    public function generateClinicalInsights(User $patient, array $riskData): array
    {
        $cacheKey = "clinical_insights_{$patient->id}_" . md5(json_encode($riskData));
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($patient, $riskData) {
            $news2 = $riskData['news2'] ?? null;
            $qsofa = $riskData['qsofa'] ?? null;
            $aki = $riskData['aki'] ?? null;
            $trend = $riskData['trend'] ?? null;
            $rapidDeterioration = $riskData['rapid_deterioration'] ?? [];

            $narrative = "Patient shows ";
            $actions = [];

            if ($news2 && $news2['score'] >= 5) {
                $narrative .= "high clinical risk (NEWS2: {$news2['score']}). ";
                $actions[] = "Increase monitoring frequency to every 30-60 minutes.";
                $actions[] = "Notify senior clinician/registrar immediately.";
            }

            if ($qsofa && $qsofa['score'] >= 2) {
                $narrative .= "potential sepsis risk (qSOFA: {$qsofa['score']}). ";
                $actions[] = "Initiate Sepsis Six bundle.";
                $actions[] = "Check lactate levels and start fluid resuscitation if indicated.";
            }

            if ($aki && $aki['risk_level'] !== 'low') {
                $narrative .= "risk of Acute Kidney Injury. ";
                $actions[] = "Monitor urine output hourly.";
                $actions[] = "Review nephrotoxic medications.";
            }

            if ($trend && $trend['trend_direction'] === 'rising') {
                $narrative .= "The risk score is currently on a rising trajectory. ";
                $actions[] = "Proactive clinical review recommended before further deterioration.";
            }

            foreach ($rapidDeterioration as $det) {
                $narrative .= "{$det['message']} ";
                if ($det['pattern'] === 'elevated_shock_index') {
                    $actions[] = "Assess for occult bleeding or sepsis.";
                }
            }

            if (empty($actions)) {
                $narrative = "Patient is currently stable with low risk scores.";
                $actions[] = "Continue routine monitoring.";
            }

            return [
                'narrative' => $narrative,
                'suggested_actions' => array_unique($actions),
                'generated_at' => now()->toIso8601String()
            ];
        });
    }
}
