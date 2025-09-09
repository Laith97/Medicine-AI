<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\HighRiskClaimAlert;
use App\Notifications\UnderpaymentAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendBillingAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The high risk claims that need alerts.
     */
    protected array $highRiskClaims;

    /**
     * The underpayment alerts that need notifications.
     */
    protected array $underpaymentAlerts;

    /**
     * Create a new job instance.
     */
    public function __construct(array $highRiskClaims, array $underpaymentAlerts)
    {
        $this->highRiskClaims = $highRiskClaims;
        $this->underpaymentAlerts = $underpaymentAlerts;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Sending billing alerts', [
            'high_risk_count' => count($this->highRiskClaims),
            'underpayment_count' => count($this->underpaymentAlerts),
        ]);

        // Get billing staff users (users with billing role or admin)
        $billingStaff = User::where('role', 'billing_staff')
            ->orWhere('role', 'admin')
            ->orWhere('role', 'hospital_admin')
            ->get();

        if ($billingStaff->isEmpty()) {
            Log::warning('No billing staff found to send alerts to');
            return;
        }

        // Send high risk claim alerts
        if (!empty($this->highRiskClaims)) {
            $this->sendHighRiskAlerts($billingStaff);
        }

        // Send underpayment alerts
        if (!empty($this->underpaymentAlerts)) {
            $this->sendUnderpaymentAlerts($billingStaff);
        }

        Log::info('Billing alerts sent successfully');
    }

    /**
     * Send alerts for high risk claims.
     */
    private function sendHighRiskAlerts($billingStaff): void
    {
        foreach ($this->highRiskClaims as $claimData) {
            try {
                Notification::send($billingStaff, new HighRiskClaimAlert($claimData));
                Log::info('High risk claim alert sent', [
                    'claim_id' => $claimData['claim_id'],
                    'claim_number' => $claimData['claim_number'],
                    'denial_risk' => $claimData['denial_risk'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send high risk claim alert', [
                    'claim_id' => $claimData['claim_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send alerts for underpayment issues.
     */
    private function sendUnderpaymentAlerts($billingStaff): void
    {
        foreach ($this->underpaymentAlerts as $alertData) {
            try {
                Notification::send($billingStaff, new UnderpaymentAlert($alertData));
                Log::info('Underpayment alert sent', [
                    'alert_id' => $alertData['alert_id'],
                    'claim_id' => $alertData['claim_id'],
                    'claim_number' => $alertData['claim_number'],
                    'variance' => $alertData['variance'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send underpayment alert', [
                    'alert_id' => $alertData['alert_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
