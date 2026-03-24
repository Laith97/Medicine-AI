<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;

class FeatureExtractor
{
    /**
     * Get default last visit days from config
     */
    private function getDefaultLastVisitDays(): int
    {
        return config('predictive-analytics.defaults.last_visit_days', 365);
    }

    /**
     * Get default age from config
     */
    private function getDefaultAge(): int
    {
        return config('predictive-analytics.defaults.age', 30);
    }

    /**
     * Get high risk conditions from config
     */
    private function getHighRiskConditions(): array
    {
        return config('predictive-analytics.high_risk_conditions', [
            'diabetes',
            'hypertension',
            'heart disease',
            'cancer',
            'stroke',
            'kidney disease'
        ]);
    }

    /**
     * Extract features for ML prediction
     *
     * @param User $patient
     * @param Appointment $appointment
     * @return array
     */
    public function extractFeatures(User $patient, Appointment $appointment): array
    {
        return [
            $this->getNoShowCount($patient, $appointment),
            $this->getCancellationCount($patient, $appointment),
            $this->getLastVisitDays($patient, $appointment),
            $this->getVisitFrequency($patient),
            $this->getPatientAge($patient),
            $this->getGenderEncoded($patient),
            $this->getChronicConditionCount($patient),
            $this->getCurrentMedicationCount($patient),
            $this->getAppointmentLeadTime($appointment),
        ];
    }

    /**
     * Get count of previous no-show appointments
     *
     * @param User $patient
     * @param Appointment $appointment
     * @return int
     */
    private function getNoShowCount(User $patient, Appointment $appointment): int
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['missed', 'no_show'])
            ->where('appointment_date', '<', $appointment->appointment_date)
            ->count();
    }

    /**
     * Get days since last appointment
     *
     * @param User $patient
     * @param Appointment $appointment
     * @return int
     */
    private function getLastVisitDays(User $patient, Appointment $appointment): int
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $lastAppointment = Appointment::where('patient_id', $patient->id)
            ->where('appointment_date', '<', $appointment->appointment_date)
            ->orderBy('appointment_date', 'desc')
            ->first();

        return $lastAppointment
            ? (int) abs(Carbon::parse($appointment->appointment_date)->diffInDays($lastAppointment->appointment_date))
            : $this->getDefaultLastVisitDays();
    }

    /**
     * Get patient age
     *
     * @param User $patient
     * @return int
     */
    private function getPatientAge(User $patient): int
    {
        return $patient->age ?? ($patient->date_of_birth
            ? Carbon::parse($patient->date_of_birth)->age
            : $this->getDefaultAge());
    }

    /**
     * Get gender encoded (1 for male, 0 for female/other)
     *
     * @param User $patient
     * @return int
     */
    private function getGenderEncoded(User $patient): int
    {
        return strtolower($patient->gender ?? '') === 'male' ? 1 : 0;
    }

    /**
     * Get count of chronic conditions from DOCTOR-VERIFIED diagnosis records
     * Uses actual diagnosis data instead of unreliable text search in appointment reasons
     *
     * @param User $patient
     * @return int
     */
    private function getChronicConditionCount(User $patient): int
    {
        // Get all diagnosis records for this patient
        $diagnoses = \App\Models\Diagnosis::where('patient_id', $patient->id)
            ->whereNotNull('diagnosis_text')
            ->get();

        $chronicConditionsFound = [];

        foreach ($diagnoses as $diagnosis) {
            // Only use doctor-written diagnosis, not AI-generated content
            $diagnosisText = strtolower($diagnosis->diagnosis_text ?? '');
            
            // Skip if this is purely AI-generated (not doctor-verified)
            $aiAnalysis = strtolower($diagnosis->ai_analysis ?? '');
            if (!empty($aiAnalysis) && trim($diagnosisText) === trim($aiAnalysis)) {
                continue; // Skip AI-only diagnoses
            }

            // Check for high-risk conditions in doctor-verified diagnosis
            foreach ($this->getHighRiskConditions() as $condition) {
                if (strpos($diagnosisText, strtolower($condition)) !== false) {
                    $chronicConditionsFound[$condition] = true;
                }
            }
        }

        return count($chronicConditionsFound);
    }

    /**
     * Check if patient has high-risk conditions from appointment history
     *
     * @param User $patient
     * @return bool
     */
    public function hasHighRiskCondition(User $patient): bool
    {
        return $this->getChronicConditionCount($patient) > 0;
    }

    /**
     * Get count of cancelled appointments
     */
    private function getCancellationCount(User $patient, Appointment $appointment): int
    {
        return Appointment::where('patient_id', $patient->id)
            ->where('status', 'cancelled')
            ->where('appointment_date', '<', $appointment->appointment_date)
            ->count();
    }

    /**
     * Get visit frequency (appointments per year)
     */
    private function getVisitFrequency(User $patient): float
    {
        $firstAppointment = Appointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'asc')
            ->first();

        if (!$firstAppointment) {
            return 0.0;
        }

        $daysSinceFirst = abs(Carbon::now()->diffInDays($firstAppointment->appointment_date));
        $totalAppointments = Appointment::where('patient_id', $patient->id)->count();

        return $daysSinceFirst > 0 ? ($totalAppointments / $daysSinceFirst) * 365 : 0.0;
    }

    /**
     * Get current medication count from patient data
     */
    private function getCurrentMedicationCount(User $patient): int
    {
        $patientData = $patient->patientData ?? null;
        
        if (!$patientData || !isset($patientData->past_medications)) {
            return 0;
        }

        $medications = $patientData->past_medications;
        
        if (is_array($medications)) {
            return count($medications);
        }
        
        if (is_string($medications)) {
            return count(array_filter(explode(',', $medications)));
        }

        return 0;
    }

    /**
     * Get appointment lead time (days between booking and appointment)
     */
    private function getAppointmentLeadTime(Appointment $appointment): int
    {
        if (!$appointment->created_at || !$appointment->appointment_date) {
            return 7; // Default to 1 week
        }

        return max(0, Carbon::parse($appointment->appointment_date)
            ->diffInDays(Carbon::parse($appointment->created_at)));
    }
}
