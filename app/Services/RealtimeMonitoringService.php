<?php

namespace App\Services;

use App\Models\PatientVital;
use App\Models\ClinicalAlertRule;
use App\Notifications\ClinicalAlertNotification;

class RealtimeMonitoringService
{
    public function processVital(PatientVital $vital): void
    {
        $rules = ClinicalAlertRule::where('patient_id', $vital->patient_id)
            ->where('vital_type', $vital->vital_type)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->shouldTriggerAlert($vital, $rule)) {
                $vital->patient->notify(new ClinicalAlertNotification($vital, $rule));
            }
        }
    }

    private function shouldTriggerAlert(PatientVital $vital, ClinicalAlertRule $rule): bool
    {
        $value = (float) $vital->value;
        $threshold = (float) $rule->threshold;

        switch ($rule->condition) {
            case '>':
                return $value > $threshold;
            case '<':
                return $value < $threshold;
            case '>=':
                return $value >= $threshold;
            case '<=':
                return $value <= $threshold;
            case '==':
                return $value == $threshold;
            case '!=':
                return $value != $threshold;
            default:
                return false;
        }
    }
}
