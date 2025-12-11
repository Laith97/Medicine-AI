<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\EligibilityCheck;
use App\Models\Claim;
use App\Models\PatientInsurance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentStatusSynchronizationService
{
    /**
     * Handle appointment status change and synchronize related entities
     */
    public function handleAppointmentStatusChange(Appointment $appointment, string $oldStatus, string $newStatus): void
    {
        DB::transaction(function () use ($appointment, $oldStatus, $newStatus) {
            switch ($newStatus) {
                case 'completed':
                    $this->handleAppointmentCompleted($appointment);
                    break;
                case 'cancelled':
                    $this->handleAppointmentCancelled($appointment);
                    break;
                case 'confirmed':
                    $this->handleAppointmentConfirmed($appointment);
                    break;
                case 'no_show':
                    $this->handleAppointmentNoShow($appointment);
                    break;
            }
        });
    }

    /**
     * Handle appointment completion - create claims if needed
     */
    private function handleAppointmentCompleted(Appointment $appointment): void
    {
        // Only create claims for registered patients with insurance
        if (!$appointment->patient_id) {
            return;
        }

        $patient = $appointment->patient;
        if (!$patient) {
            return;
        }

        // Check if patient has insurance
        $patientInsurances = $patient->patientInsurances;
        if ($patientInsurances->isEmpty()) {
            Log::info("No insurance found for patient {$patient->id}, skipping claim creation");
            return;
        }

        // Check if a claim already exists for this appointment
        $existingClaim = Claim::where('patient_id', $appointment->patient_id)
            ->where('appointment_id', $appointment->id)
            ->first();

        if ($existingClaim) {
            Log::info("Claim already exists for appointment {$appointment->id}");
            return;
        }

        // Create claim for each insurance
        foreach ($patientInsurances as $insurance) {
            try {
                $this->createClaimForAppointment($appointment, $insurance);
            } catch (\Exception $e) {
                Log::error("Failed to create claim for appointment {$appointment->id}, insurance {$insurance->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create a claim for an appointment
     */
    private function createClaimForAppointment(Appointment $appointment, PatientInsurance $insurance): void
    {
        // Get eligibility information for this insurance
        $eligibilityCheck = EligibilityCheck::where('patient_insurance_id', $insurance->id)
            ->where('service_type', $this->mapAppointmentTypeToService($appointment->appointment_type))
            ->where('expires_at', '>', now())
            ->whereIn('eligibility_status', ['eligible', 'ineligible'])
            ->orderBy('check_date', 'desc')
            ->first();

        $claimData = [
            'claim_id' => 'CLM-' . strtoupper(uniqid()),
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'diagnosis_text' => $appointment->reason ?? 'Medical consultation',
            'procedure_text' => $appointment->appointment_type,
            'payer' => $insurance->insuranceProvider->name ?? 'Unknown',
            'claim_status' => 'pending',
            'service_date' => $appointment->appointment_date->toDateString(),
            'submission_date' => now()->toDateString(),
            'expected_amount' => $appointment->consultation_fee ?? 0,
            'eligibility_warning' => $eligibilityCheck && $eligibilityCheck->eligibility_status === 'ineligible'
                ? 'Patient may not be eligible for this service'
                : null,
        ];

        Claim::create($claimData);

        Log::info("Created claim for completed appointment", [
            'appointment_id' => $appointment->id,
            'patient_insurance_id' => $insurance->id,
            'claim_id' => $claimData['claim_id']
        ]);
    }

    /**
     * Handle appointment cancellation - update related claims
     */
    private function handleAppointmentCancelled(Appointment $appointment): void
    {
        // Update any pending claims for this appointment to cancelled
        Claim::where('appointment_id', $appointment->id)
            ->where('claim_status', 'pending')
            ->update([
                'claim_status' => 'cancelled',
                'denial_reason' => 'Appointment cancelled'
            ]);

        Log::info("Cancelled pending claims for appointment {$appointment->id}");
    }

    /**
     * Handle appointment confirmation - re-validate eligibility if needed
     */
    private function handleAppointmentConfirmed(Appointment $appointment): void
    {
        // If appointment was previously unconfirmed, we might want to re-check eligibility
        // This is optional as eligibility is checked during booking
        if ($appointment->patient_id) {
            $patient = $appointment->patient;
            if ($patient && $patient->patientInsurances->isNotEmpty()) {
                // Trigger eligibility re-check in background
                dispatch(function () use ($appointment) {
                    $eligibilityService = app(EligibilityServiceFactory::class);
                    foreach ($appointment->patient->patientInsurances as $insurance) {
                        try {
                            $service = $eligibilityService->getServiceForProvider($insurance->insuranceProvider);
                            $service->checkEligibility($insurance, $this->mapAppointmentTypeToService($appointment->appointment_type));
                        } catch (\Exception $e) {
                            Log::warning("Failed to re-check eligibility for confirmed appointment {$appointment->id}", [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                })->afterCommit();
            }
        }
    }

    /**
     * Handle no-show appointments
     */
    private function handleAppointmentNoShow(Appointment $appointment): void
    {
        // Mark any pending claims as denied due to no-show
        Claim::where('appointment_id', $appointment->id)
            ->where('claim_status', 'pending')
            ->update([
                'claim_status' => 'denied',
                'denial_reason' => 'Patient did not show up for appointment'
            ]);

        Log::info("Marked claims as denied for no-show appointment {$appointment->id}");
    }

    /**
     * Map appointment type to service type for claims/eligibility
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
     * Get appointment statistics for reporting
     */
    public function getAppointmentStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'total_appointments' => Appointment::whereBetween('appointment_date', [$startDate, $endDate])->count(),
            'completed_appointments' => Appointment::whereBetween('appointment_date', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'cancelled_appointments' => Appointment::whereBetween('appointment_date', [$startDate, $endDate])
                ->where('status', 'cancelled')->count(),
            'no_show_appointments' => Appointment::whereBetween('appointment_date', [$startDate, $endDate])
                ->where('status', 'no_show')->count(),
            'claims_created' => Claim::whereBetween('service_date', [$startDate, $endDate])->count(),
            'claims_paid' => Claim::whereBetween('service_date', [$startDate, $endDate])
                ->where('claim_status', 'paid')->count(),
            'claims_denied' => Claim::whereBetween('service_date', [$startDate, $endDate])
                ->where('claim_status', 'denied')->count(),
        ];
    }
}
