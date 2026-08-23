<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FeatureExtractor
{
    public const FEATURE_NAMES = [
        'no_show_count',
        'cancellation_count',
        'last_visit_days',
        'visit_frequency',
        'age',
        'gender_encoded',
        'chronic_conditions',
        'medication_count',
        'lead_time_days',
    ];

    public const FEATURE_RANGES = [
        'no_show_count' => [0, 20],
        'cancellation_count' => [0, 20],
        'last_visit_days' => [0, 730],
        'visit_frequency' => [0, 52],
        'age' => [0, 100],
        'gender_encoded' => [0, 1],
        'chronic_conditions' => [0, 10],
        'medication_count' => [0, 15],
        'lead_time_days' => [0, 90],
    ];

    private function getDefaultLastVisitDays(): int
    {
        return config('predictive-analytics.defaults.last_visit_days', 365);
    }

    private function getDefaultAge(): int
    {
        return config('predictive-analytics.defaults.age', 30);
    }

    private function getHighRiskConditions(): array
    {
        return config('predictive-analytics.high_risk_conditions', [
            'diabetes', 'hypertension', 'heart disease', 'cancer', 'stroke', 'kidney disease'
        ]);
    }

    public function extractFeatures(User $patient, Appointment $appointment): array
    {
        try {
            $features = [
                $this->getNoShowCount($patient, $appointment),
                $this->getCancellationCount($patient, $appointment),
                $this->getLastVisitDays($patient, $appointment),
                $this->getVisitFrequency($patient, $appointment),
                $this->getPatientAge($patient),
                $this->getGenderEncoded($patient),
                $this->getChronicConditionCount($patient),
                $this->getCurrentMedicationCount($patient),
                $this->getAppointmentLeadTime($appointment),
            ];
            // Clamp to valid ranges for model stability
            return $this->clampFeatures($features);
        } catch (\Exception $e) {
            Log::warning('FeatureExtractor failed, returning defaults', [
                'patient_id' => $patient->id ?? null,
                'appointment_id' => $appointment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultFeatures($appointment);
        }
    }

    public function getFeatureNames(): array
    {
        return self::FEATURE_NAMES;
    }

    public function validateFeatures(array $features): bool
    {
        if (count($features) !== 9) return false;
        foreach ($features as $i => $v) {
            if (!is_numeric($v)) return false;
            $name = self::FEATURE_NAMES[$i];
            [$min, $max] = self::FEATURE_RANGES[$name];
            if ($v < $min - 1 || $v > $max + 50) return false; // allow slight overflow before clamp
        }
        return true;
    }

    private function clampFeatures(array $features): array
    {
        foreach ($features as $i => $v) {
            $name = self::FEATURE_NAMES[$i];
            [$min, $max] = self::FEATURE_RANGES[$name];
            $features[$i] = max($min, min($max, (float) $v));
            // integer features
            if (in_array($name, ['no_show_count','cancellation_count','last_visit_days','age','gender_encoded','chronic_conditions','medication_count','lead_time_days'])) {
                $features[$i] = (int) round($features[$i]);
            }
        }
        return $features;
    }

    private function getDefaultFeatures(Appointment $appointment): array
    {
        return [
            0, 0, $this->getDefaultLastVisitDays(), 1.0, $this->getDefaultAge(), 0, 0, 0, 7
        ];
    }

    private function getNoShowCount(User $patient, Appointment $appointment): int
    {
        if (!$patient->id || !$appointment->appointment_date) return 0;
        return (int) Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['missed', 'no_show'])
            ->where('appointment_date', '<', $appointment->appointment_date)
            ->count();
    }

    private function getLastVisitDays(User $patient, Appointment $appointment): int
    {
        if (!$patient->id || !$appointment->appointment_date) return $this->getDefaultLastVisitDays();
        $last = Appointment::where('patient_id', $patient->id)
            ->where('appointment_date', '<', $appointment->appointment_date)
            ->orderBy('appointment_date', 'desc')
            ->first();
        if (!$last || !$last->appointment_date) return $this->getDefaultLastVisitDays();
        try {
            $days = (int) abs(Carbon::parse($appointment->appointment_date)->diffInDays(Carbon::parse($last->appointment_date)));
            return min(730, max(0, $days));
        } catch (\Exception $e) {
            return $this->getDefaultLastVisitDays();
        }
    }

    private function getPatientAge(User $patient): int
    {
        try {
            if (isset($patient->age) && is_numeric($patient->age)) return (int) max(0, min(100, $patient->age));
            if (!empty($patient->date_of_birth)) return (int) max(0, min(100, Carbon::parse($patient->date_of_birth)->age));
        } catch (\Exception $e) {}
        return $this->getDefaultAge();
    }

    private function getGenderEncoded(User $patient): int
    {
        return strtolower(trim($patient->gender ?? '')) === 'male' ? 1 : 0;
    }

    private function getChronicConditionCount(User $patient): int
    {
        try {
            $diagnoses = \App\Models\Diagnosis::where('patient_id', $patient->id)->whereNotNull('diagnosis_text')->get();
            $found = [];
            foreach ($diagnoses as $d) {
                $text = strtolower(trim($d->diagnosis_text ?? ''));
                if ($text === '') continue;
                // Prefer severity flag for production ground truth
                if (isset($d->requires_hospitalization) && $d->requires_hospitalization) {
                    // still count as chronic but via severity
                }
                foreach ($this->getHighRiskConditions() as $cond) {
                    if (strpos($text, strtolower($cond)) !== false) $found[$cond] = true;
                }
                // also count critical severity as chronic
                if (($d->severity ?? null) === 'critical') $found['critical_severity'] = true;
            }
            return min(10, count($found));
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function hasHighRiskCondition(User $patient): bool
    {
        return $this->getChronicConditionCount($patient) > 0;
    }

    // Production: true hospitalization label from appointments + diagnoses
    public function hasHospitalizationHistory(User $patient): bool
    {
        try {
            // 1) explicit appointment flag (if column exists)
            if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'was_hospitalized')) {
                $hasHospAppointment = Appointment::where('patient_id', $patient->id)->where('was_hospitalized', true)->exists();
                if ($hasHospAppointment) return true;
            }
        } catch (\Exception $e) {
            // column missing in prod before migration - fallback
        }
        try {
            // 2) diagnosis with requires_hospitalization or critical severity (if columns exist)
            if (\Illuminate\Support\Facades\Schema::hasColumn('diagnoses', 'requires_hospitalization')) {
                $hasHospDiagnosis = \App\Models\Diagnosis::where('patient_id', $patient->id)
                    ->where(function($q){ $q->where('requires_hospitalization', true)->orWhere('severity','critical'); })->exists();
                if ($hasHospDiagnosis) return true;
            }
        } catch (\Exception $e) {
        }
        return $this->hasHighRiskCondition($patient);
    }

    private function getCancellationCount(User $patient, Appointment $appointment): int
    {
        if (!$patient->id || !$appointment->appointment_date) return 0;
        return (int) Appointment::where('patient_id', $patient->id)
            ->where('status', 'cancelled')
            ->where('appointment_date', '<', $appointment->appointment_date)
            ->count();
    }

    private function getVisitFrequency(User $patient, Appointment $appointment): float
    {
        try {
            $first = Appointment::where('patient_id', $patient->id)->orderBy('appointment_date','asc')->first();
            if (!$first || !$first->appointment_date) return 0.0;
            // Use appointment_date as reference for determinism, not now()
            $ref = $appointment->appointment_date ? Carbon::parse($appointment->appointment_date) : Carbon::now();
            $firstDate = Carbon::parse($first->appointment_date);
            $days = max(1, abs($ref->diffInDays($firstDate)));
            $total = Appointment::where('patient_id', $patient->id)->where('appointment_date','<=',$ref)->count();
            return round(min(52, ($total / $days) * 365), 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function getCurrentMedicationCount(User $patient): int
    {
        try {
            $pd = $patient->patientData ?? null;
            if (!$pd || !isset($pd->past_medications)) return 0;
            $meds = $pd->past_medications;
            if (is_array($meds)) return min(15, count(array_filter($meds)));
            if (is_string($meds)) return min(15, count(array_filter(array_map('trim', explode(',', $meds)))));
            if (is_numeric($meds)) return min(15, (int)$meds);
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAppointmentLeadTime(Appointment $appointment): int
    {
        if (!$appointment->created_at || !$appointment->appointment_date) return 7;
        try {
            $days = (int) Carbon::parse($appointment->appointment_date)->diffInDays(Carbon::parse($appointment->created_at));
            return max(0, min(90, $days));
        } catch (\Exception $e) {
            return 7;
        }
    }
}
