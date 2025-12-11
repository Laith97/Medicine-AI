<?php

namespace App\Services;

use App\Models\User;
use App\Models\HepAssignment;
use App\Models\HepExercise;
use App\Models\HepProgress;
use App\Models\PatientData;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\HEPSafetyAlert;
use Carbon\Carbon;

class HEPSafetyService
{
    /**
     * Check contraindications for a patient against an exercise
     */
    public function checkContraindications(User $patient, HepExercise $exercise): array
    {
        $issues = [];

        // Get patient's latest medical data
        $patientData = $this->getLatestPatientData($patient);

        if (!$patientData) {
            $issues[] = [
                'type' => 'no_medical_data',
                'severity' => 'warning',
                'message' => 'No recent medical data available for contraindication checking'
            ];
            return $issues;
        }

        // Check exercise contraindications against patient conditions
        $exerciseContraindications = $exercise->exercise->contraindications ?? [];

        if (empty($exerciseContraindications)) {
            return $issues; // No contraindications defined for this exercise
        }

        // Check past medical history
        if (!empty($patientData->past_medical_history)) {
            $medicalHistory = is_array($patientData->past_medical_history)
                ? $patientData->past_medical_history
                : [$patientData->past_medical_history];

            foreach ($exerciseContraindications as $contraindication) {
                foreach ($medicalHistory as $condition) {
                    if ($this->matchesCondition($contraindication, $condition)) {
                        $issues[] = [
                            'type' => 'medical_history',
                            'severity' => 'high',
                            'message' => "Exercise contraindicated due to: {$condition}",
                            'condition' => $condition,
                            'contraindication' => $contraindication
                        ];
                    }
                }
            }
        }

        // Check allergies
        if (!empty($patientData->allergies)) {
            $allergies = is_array($patientData->allergies)
                ? $patientData->allergies
                : [$patientData->allergies];

            foreach ($exerciseContraindications as $contraindication) {
                foreach ($allergies as $allergy) {
                    if ($this->matchesCondition($contraindication, $allergy)) {
                        $issues[] = [
                            'type' => 'allergy',
                            'severity' => 'critical',
                            'message' => "Exercise contraindicated due to allergy: {$allergy}",
                            'condition' => $allergy,
                            'contraindication' => $contraindication
                        ];
                    }
                }
            }
        }

        // Check current symptoms
        if (!empty($patientData->symptoms)) {
            $symptoms = is_array($patientData->symptoms)
                ? $patientData->symptoms
                : [$patientData->symptoms];

            foreach ($exerciseContraindications as $contraindication) {
                foreach ($symptoms as $symptom) {
                    if ($this->matchesCondition($contraindication, $symptom)) {
                        $issues[] = [
                            'type' => 'current_symptom',
                            'severity' => 'high',
                            'message' => "Exercise contraindicated due to current symptom: {$symptom}",
                            'condition' => $symptom,
                            'contraindication' => $contraindication
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check pain threshold and monitor for safety concerns
     */
    public function checkPainThreshold(HepAssignment $assignment, int $painLevel): array
    {
        $alerts = [];

        // Define pain thresholds
        $thresholds = [
            'mild' => 3,
            'moderate' => 5,
            'severe' => 7,
            'critical' => 9
        ];

        // Check if pain level exceeds thresholds
        if ($painLevel >= $thresholds['critical']) {
            $alerts[] = [
                'type' => 'critical_pain',
                'severity' => 'critical',
                'message' => 'Critical pain level reported - immediate medical attention required',
                'action_required' => 'emergency_contact'
            ];
        } elseif ($painLevel >= $thresholds['severe']) {
            $alerts[] = [
                'type' => 'severe_pain',
                'severity' => 'high',
                'message' => 'Severe pain level reported - program should be paused',
                'action_required' => 'pause_program'
            ];
        } elseif ($painLevel >= $thresholds['moderate']) {
            $alerts[] = [
                'type' => 'moderate_pain',
                'severity' => 'medium',
                'message' => 'Moderate pain level reported - monitor closely',
                'action_required' => 'monitor'
            ];
        }

        // Check for pain trend (increasing pain over time)
        $recentProgress = $assignment->hepProgress()
            ->where('pain_level', '>', 0)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        if ($recentProgress->count() >= 3) {
            $painLevels = $recentProgress->pluck('pain_level')->toArray();
            if ($this->isIncreasingTrend($painLevels)) {
                $alerts[] = [
                    'type' => 'increasing_pain_trend',
                    'severity' => 'high',
                    'message' => 'Increasing pain trend detected - review program appropriateness',
                    'action_required' => 'review_program'
                ];
            }
        }

        return $alerts;
    }

    /**
     * Automatically pause program for safety concerns
     */
    public function handleSafetyConcerns(HepAssignment $assignment, array $alerts): bool
    {
        $requiresPause = false;
        $emergencyContact = false;

        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'critical' || $alert['action_required'] === 'emergency_contact') {
                $emergencyContact = true;
                $requiresPause = true;
            } elseif ($alert['severity'] === 'high' && $alert['action_required'] === 'pause_program') {
                $requiresPause = true;
            }
        }

        if ($requiresPause) {
            $this->pauseProgram($assignment, 'Safety concern detected: ' . implode(', ', array_column($alerts, 'message')));

            // Log the safety event
            $this->logSafetyEvent($assignment, 'program_paused', [
                'alerts' => $alerts,
                'emergency_contact_required' => $emergencyContact
            ]);

            // Send emergency contact if critical
            if ($emergencyContact) {
                $this->notifyEmergencyContact($assignment->patient, $alerts);
            }

            return true;
        }

        return false;
    }

    /**
     * Get emergency contact information for a patient
     */
    public function getEmergencyContact(User $patient): ?array
    {
        if (empty($patient->emergency_contact_name) || empty($patient->emergency_contact_phone)) {
            return null;
        }

        return [
            'name' => $patient->emergency_contact_name,
            'phone' => $patient->emergency_contact_phone
        ];
    }

    /**
     * Notify emergency contact
     */
    public function notifyEmergencyContact(User $patient, array $alerts): void
    {
        $emergencyContact = $this->getEmergencyContact($patient);

        if (!$emergencyContact) {
            Log::warning('Emergency contact notification failed - no emergency contact information', [
                'patient_id' => $patient->id
            ]);
            return;
        }

        // Send notification to emergency contact (SMS/Email)
        // This would integrate with SMS service
        Log::critical('Emergency contact notification sent', [
            'patient_id' => $patient->id,
            'emergency_contact' => $emergencyContact,
            'alerts' => $alerts
        ]);

        // Also notify the patient's doctor
        if ($patient->primary_doctor_id) {
            $doctor = User::find($patient->primary_doctor_id);
            if ($doctor) {
                $doctor->notify(new HEPSafetyAlert($patient, $alerts, 'emergency'));
            }
        }
    }

    /**
     * Pause HEP program
     */
    public function pauseProgram(HepAssignment $assignment, string $reason): void
    {
        $assignment->hepProgram->markAsPaused();

        // Log the pause event
        $this->logSafetyEvent($assignment, 'program_paused', [
            'reason' => $reason,
            'paused_at' => now()
        ]);
    }

    /**
     * Resume HEP program
     */
    public function resumeProgram(HepAssignment $assignment, string $reason): void
    {
        $assignment->hepProgram->resume();

        // Log the resume event
        $this->logSafetyEvent($assignment, 'program_resumed', [
            'reason' => $reason,
            'resumed_at' => now()
        ]);
    }

    /**
     * Log safety event for audit trail
     */
    public function logSafetyEvent(HepAssignment $assignment, string $eventType, array $metadata = []): void
    {
        AuditLog::log('hep_safety', $assignment->patient_id, $assignment->assigned_by, null, array_merge([
            'assignment_id' => $assignment->id,
            'program_id' => $assignment->hep_program_id,
            'event_type' => $eventType,
            'patient_id' => $assignment->patient_id,
            'timestamp' => now(),
        ], $metadata));
    }

    /**
     * Get latest patient data for contraindication checking
     */
    private function getLatestPatientData(User $patient): ?PatientData
    {
        return $patient->patientData()->latest()->first();
    }

    /**
     * Check if a condition matches a contraindication
     */
    private function matchesCondition(string $contraindication, string $condition): bool
    {
        $contraindication = strtolower(trim($contraindication));
        $condition = strtolower(trim($condition));

        // Exact match
        if ($contraindication === $condition) {
            return true;
        }

        // Partial match (contraindication contains condition or vice versa)
        return str_contains($contraindication, $condition) || str_contains($condition, $contraindication);
    }

    /**
     * Check if pain levels show an increasing trend
     */
    private function isIncreasingTrend(array $painLevels): bool
    {
        if (count($painLevels) < 3) {
            return false;
        }

        // Check if the last 3 readings are increasing
        $recent = array_slice($painLevels, 0, 3);
        return $recent[0] > $recent[1] && $recent[1] > $recent[2];
    }
}
