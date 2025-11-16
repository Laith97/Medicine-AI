<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\EligibilityServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshRecurringAppointmentEligibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eligibility:refresh-recurring {--days=7 : Days ahead to check appointments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh eligibility for upcoming recurring appointments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $futureDate = now()->addDays($days);

        $this->info("Refreshing eligibility for appointments within {$days} days...");

        // Find upcoming appointments with patient insurance
        $appointments = Appointment::where('appointment_date', '<=', $futureDate)
            ->where('appointment_date', '>', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('patient.patientInsurances')
            ->with(['patient.patientInsurances.insuranceProvider', 'patient.user'])
            ->get();

        $refreshed = 0;
        $failed = 0;

        foreach ($appointments as $appointment) {
            try {
                $patient = $appointment->patient;
                $patientInsurances = $patient->patientInsurances;

                foreach ($patientInsurances as $insurance) {
                    // Determine service type from appointment type
                    $serviceType = $this->mapAppointmentTypeToService($appointment->appointment_type);

                    // Check eligibility
                    $eligibilityService = app(EligibilityServiceFactory::class)
                        ->getServiceForProvider($insurance->insuranceProvider);

                    $result = $eligibilityService->checkEligibility($insurance, $serviceType);

                    if ($result['status'] === 'ineligible') {
                        // Send notification about ineligibility
                        $user = $patient->user;
                        if ($user) {
                            $user->notify(new \App\Notifications\SystemAlertNotification(
                                'Appointment Eligibility Issue',
                                "Your upcoming appointment on {$appointment->appointment_date->format('M j, Y')} may be affected by insurance eligibility issues.",
                                'warning',
                                [
                                    'link' => route('appointments.show', $appointment),
                                    'link_text' => 'View Appointment',
                                    'related_type' => 'appointment',
                                    'related_id' => $appointment->id,
                                ]
                            ));
                        }

                        Log::info("Found ineligible appointment", [
                            'appointment_id' => $appointment->id,
                            'patient_insurance_id' => $insurance->id,
                        ]);
                    }
                }

                $refreshed++;

            } catch (\Exception $e) {
                Log::error("Failed to refresh eligibility for appointment {$appointment->id}", [
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("Refreshed eligibility for {$refreshed} appointments. {$failed} failed.");
        return 0;
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
