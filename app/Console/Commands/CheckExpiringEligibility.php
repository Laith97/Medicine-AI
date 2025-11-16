<?php

namespace App\Console\Commands;

use App\Models\EligibilityCheck;
use App\Models\PatientInsurance;
use App\Notifications\EligibilityExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringEligibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eligibility:check-expiring {--days=30 : Days before expiry to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring insurance eligibility and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $expiryDate = now()->addDays($days);

        $this->info("Checking for eligibility expiring within {$days} days...");

        // Find eligibility checks that expire soon
        $expiringChecks = EligibilityCheck::where('expires_at', '<=', $expiryDate)
            ->where('expires_at', '>', now())
            ->whereIn('eligibility_status', ['eligible', 'ineligible'])
            ->with(['patientInsurance.patient.user', 'patientInsurance.insuranceProvider'])
            ->get();

        $notificationsSent = 0;

        foreach ($expiringChecks as $check) {
            try {
                $patientInsurance = $check->patientInsurance;
                $patient = $patientInsurance->patient;
                $user = $patient->user;

                if (!$user) {
                    Log::warning("No user found for patient insurance {$patientInsurance->id}");
                    continue;
                }

                $daysUntilExpiry = now()->diffInDays($check->expires_at, false);

                // Send notification
                $user->notify(new EligibilityExpiringNotification($patientInsurance, abs($daysUntilExpiry)));

                $notificationsSent++;

                Log::info("Sent expiring eligibility notification", [
                    'user_id' => $user->id,
                    'patient_insurance_id' => $patientInsurance->id,
                    'days_until_expiry' => $daysUntilExpiry,
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to send expiring eligibility notification", [
                    'check_id' => $check->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$notificationsSent} expiring eligibility notifications.");
        return 0;
    }
}
