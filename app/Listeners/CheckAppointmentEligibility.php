<?php

namespace App\Listeners;

use App\Events\AppointmentBookedEvent;
use App\Services\EligibilityServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckAppointmentEligibility implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentBookedEvent $event): void
    {
        $appointment = $event->appointment;

        // Skip if no patient (guest appointment)
        if (!$appointment->patient_id) {
            return;
        }

        try {
            $patient = $appointment->patient;

            // Check if patient has insurance
            $patientInsurances = $patient->patientInsurances;
            if ($patientInsurances->isEmpty()) {
                Log::info("No insurance found for patient {$patient->id}, skipping eligibility check");
                return;
            }

            // Map appointment type to service type
            $serviceType = $this->mapAppointmentTypeToService($appointment->appointment_type);

            foreach ($patientInsurances as $insurance) {
                try {
                    $eligibilityService = app(EligibilityServiceFactory::class)
                        ->getServiceForProvider($insurance->insuranceProvider);

                    $result = $eligibilityService->checkEligibility($insurance, $serviceType);

                    // Log eligibility status
                    Log::info("Appointment eligibility check completed", [
                        'appointment_id' => $appointment->id,
                        'patient_insurance_id' => $insurance->id,
                        'service_type' => $serviceType,
                        'status' => $result['status'],
                    ]);

                } catch (\Exception $e) {
                    Log::error("Eligibility check failed for appointment", [
                        'appointment_id' => $appointment->id,
                        'patient_insurance_id' => $insurance->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to process appointment eligibility check", [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map appointment type to service type for eligibility checking
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
}
