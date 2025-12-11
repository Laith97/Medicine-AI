<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PatientInsurance;
use App\Models\EligibilityCheck;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BusinessRulesService
{
    /**
     * Validate appointment cancellation business rules
     */
    public function validateAppointmentCancellation(Appointment $appointment, string $reason = null): array
    {
        $errors = [];
        $warnings = [];

        // Rule 1: Cannot cancel completed appointments
        if ($appointment->isCompleted()) {
            $errors[] = 'Cannot cancel a completed appointment';
        }

        // Rule 2: Cannot cancel past appointments (with grace period)
        if ($appointment->appointment_date->isPast()) {
            $graceHours = config('app.appointment_cancellation_grace_hours', 2);
            $gracePeriodEnd = $appointment->appointment_date->copy()->addHours($graceHours);

            if (now()->isAfter($gracePeriodEnd)) {
                $errors[] = 'Cannot cancel appointments that have already passed';
            } else {
                $warnings[] = 'Appointment is in the past but within grace period for cancellation';
            }
        }

        // Rule 3: Require cancellation reason for short-notice cancellations
        $hoursBefore = $appointment->appointment_date->diffInHours(now());
        if ($hoursBefore < 24 && empty($reason)) {
            $errors[] = 'Cancellation reason is required for appointments less than 24 hours away';
        }

        // Rule 4: Check for insurance implications
        if ($appointment->patient_id) {
            $insuranceImplications = $this->checkInsuranceCancellationImplications($appointment);
            if (!empty($insuranceImplications)) {
                $warnings = array_merge($warnings, $insuranceImplications);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate insurance information for appointment booking
     */
    public function validateInsuranceForAppointment(Appointment $appointment, ?PatientInsurance $insurance = null): array
    {
        $errors = [];
        $warnings = [];

        // If insurance is provided, validate it
        if ($insurance) {
            // Check if insurance is active
            if ($insurance->termination_date && $insurance->termination_date->isPast()) {
                $errors[] = 'Patient insurance has expired';
            }

            if ($insurance->effective_date && $insurance->effective_date->isFuture()) {
                $warnings[] = 'Patient insurance is not yet effective';
            }

            // Check eligibility
            $eligibilityResult = $this->checkEligibilityForAppointment($appointment, $insurance);
            if ($eligibilityResult['status'] === 'ineligible') {
                $warnings[] = 'Patient may not be eligible for this service type';
            } elseif ($eligibilityResult['status'] === 'error') {
                $warnings[] = 'Unable to verify insurance eligibility - manual verification recommended';
            }
        } elseif ($appointment->patient_id) {
            // Check if patient has any insurance on file
            $patientInsurances = $appointment->patient->patientInsurances ?? collect();
            if ($patientInsurances->isEmpty()) {
                $warnings[] = 'Patient has no insurance information on file';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check insurance implications of appointment cancellation
     */
    private function checkInsuranceCancellationImplications(Appointment $appointment): array
    {
        $warnings = [];

        if (!$appointment->patient_id) {
            return $warnings;
        }

        // Eager load patient insurance and eligibility checks to prevent N+1 queries
        if (!$appointment->relationLoaded('patient.patientInsurances')) {
            $appointment->load('patient.patientInsurances.eligibilityChecks');
        }

        $patientInsurances = $appointment->patient->patientInsurances ?? collect();

        foreach ($patientInsurances as $insurance) {
            // Check if there are recent claims that might be affected
            $recentClaims = $insurance->eligibilityChecks()
                ->where('check_date', '>', now()->subDays(30))
                ->where('eligibility_status', 'eligible')
                ->exists();

            if ($recentClaims) {
                $warnings[] = 'Cancellation may affect recent eligibility determinations';
            }

            // Check for copay/deductible implications
            $copayInfo = $insurance->copay_info;
            if ($copayInfo && isset($copayInfo['no_show_fee'])) {
                $warnings[] = 'Insurance may charge a no-show fee for late cancellations';
            }
        }

        return $warnings;
    }

    /**
     * Batch validate multiple appointments to prevent N+1 queries
     */
    public function batchValidateAppointmentsCancellation(array $appointments, string $reason = null): array
    {
        $results = [];

        // Load all necessary relationships in a single query
        $appointmentIds = array_column($appointments, 'id');
        $loadedAppointments = Appointment::with([
            'patient.patientInsurances.eligibilityChecks',
            'doctor'
        ])->whereIn('id', $appointmentIds)->get();

        foreach ($appointments as $appointment) {
            $loadedAppointment = $loadedAppointments->firstWhere('id', $appointment->id);
            if ($loadedAppointment) {
                $results[$appointment->id] = $this->validateAppointmentCancellation($loadedAppointment, $reason);
            }
        }

        return $results;
    }

    /**
     * Check eligibility for a specific appointment
     */
    private function checkEligibilityForAppointment(Appointment $appointment, PatientInsurance $insurance): array
    {
        try {
            $serviceType = $this->mapAppointmentTypeToService($appointment->appointment_type);

            // Check for recent eligibility result
            $recentCheck = EligibilityCheck::where('patient_insurance_id', $insurance->id)
                ->where('service_type', $serviceType)
                ->where('expires_at', '>', now())
                ->whereIn('eligibility_status', ['eligible', 'ineligible'])
                ->orderBy('check_date', 'desc')
                ->first();

            if ($recentCheck) {
                return [
                    'status' => $recentCheck->eligibility_status,
                    'cached' => true,
                    'check_date' => $recentCheck->check_date,
                ];
            }

            // If no recent check, return unknown status
            return [
                'status' => 'unknown',
                'message' => 'No recent eligibility check found',
            ];

        } catch (\Exception $e) {
            Log::error('Error checking eligibility for appointment', [
                'appointment_id' => $appointment->id,
                'insurance_id' => $insurance->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate appointment rescheduling business rules
     */
    public function validateAppointmentRescheduling(Appointment $appointment, Carbon $newDate): array
    {
        $errors = [];
        $warnings = [];

        // Rule 1: Cannot reschedule completed or cancelled appointments
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            $errors[] = 'Cannot reschedule completed or cancelled appointments';
        }

        // Rule 2: New date must be in the future
        if ($newDate->isPast()) {
            $errors[] = 'New appointment date must be in the future';
        }

        // Rule 3: Check doctor's availability
        $doctor = $appointment->doctor;
        if ($doctor) {
            $availableSlots = $doctor->getAvailableSlots($newDate->format('Y-m-d'));
            $requestedSlot = $availableSlots->first(function ($slot) use ($newDate) {
                return Carbon::parse($slot['datetime'])->equalTo($newDate);
            });

            if (!$requestedSlot) {
                $errors[] = 'Requested time slot is not available';
            }
        }

        // Rule 4: Check insurance implications for rescheduling
        if ($appointment->patient_id) {
            $hoursBefore = $appointment->appointment_date->diffInHours(now());
            if ($hoursBefore < 24) {
                $warnings[] = 'Late rescheduling may incur insurance penalties';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Map appointment type to service type for eligibility/claims
     */
    private function mapAppointmentTypeToService(string $appointmentType): string
    {
        return match($appointmentType) {
            'in_person' => 'office_visit',
            'video_call' => 'telehealth',
            'phone_call' => 'phone_consultation',
            default => 'medical',
        };
    }

    /**
     * Get business rules summary for auditing
     */
    public function getBusinessRulesSummary(): array
    {
        return [
            'appointment_cancellation' => [
                'cannot_cancel_completed' => true,
                'cannot_cancel_past' => true,
                'grace_period_hours' => config('app.appointment_cancellation_grace_hours', 2),
                'require_reason_under_hours' => 24,
            ],
            'appointment_rescheduling' => [
                'cannot_reschedule_completed' => true,
                'cannot_reschedule_cancelled' => true,
                'must_be_future_date' => true,
            ],
            'insurance_validation' => [
                'check_eligibility_on_booking' => true,
                'validate_expiration_dates' => true,
                'warn_on_missing_insurance' => true,
            ],
        ];
    }
}
