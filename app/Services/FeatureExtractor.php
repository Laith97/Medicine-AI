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
            $this->getLastVisitDays($patient, $appointment),
            $this->getPatientAge($patient),
            $this->getGenderEncoded($patient),
            $this->getChronicConditionCount($patient),
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
            ? Carbon::parse($appointment->appointment_date)->diffInDays($lastAppointment->appointment_date)
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
     * Get count of chronic conditions
     *
     * @param User $patient
     * @return int
     */
    private function getChronicConditionCount(User $patient): int
    {
        return $patient->patientDiagnoses()
            ->where(function($query) {
                foreach ($this->getHighRiskConditions() as $condition) {
                    $query->orWhere('diagnosis_text', 'ILIKE', '%' . $condition . '%');
                }
            })
            ->count();
    }

    /**
     * Check if patient has high-risk conditions
     *
     * @param User $patient
     * @return bool
     */
    public function hasHighRiskCondition(User $patient): bool
    {
        return $patient->patientDiagnoses()
            ->where(function($query) {
                foreach ($this->getHighRiskConditions() as $condition) {
                    $query->orWhere('diagnosis_text', 'ILIKE', '%' . $condition . '%');
                }
            })
            ->exists();
    }
}
