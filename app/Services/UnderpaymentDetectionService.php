<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\BillingUnderpaymentAlert;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class UnderpaymentDetectionService
{
    /**
     * Get the underpayment threshold percentage from config.
     */
    public function getThresholdPercentage(): float
    {
        return Config::get('billing.underpayment_threshold', 10.0);
    }

    /**
     * Calculate variance between expected and paid amounts.
     */
    public function calculateVariance(float $expected, float $paid): float
    {
        return $expected - $paid;
    }

    /**
     * Calculate variance percentage.
     */
    public function calculateVariancePercentage(float $expected, float $paid): float
    {
        if ($expected == 0) {
            return 0;
        }
        return (($expected - $paid) / $expected) * 100;
    }

    /**
     * Check if a claim has underpayment based on threshold.
     */
    public function isUnderpayment(Claim $claim, ?float $threshold = null): bool
    {
        // Validate expected amount
        if ($claim->expected_amount <= 0) {
            return false; // Cannot have underpayment if expected amount is zero or negative
        }

        $threshold = $threshold ?? $this->getThresholdPercentage();
        $variancePercentage = $this->calculateVariancePercentage($claim->expected_amount, $claim->paid_amount);

        return $variancePercentage > $threshold;
    }

    /**
     * Detect and flag underpayments for a single claim.
     */
    public function detectAndFlagUnderpayment(Claim $claim): ?BillingUnderpaymentAlert
    {
        if (!$this->isUnderpayment($claim)) {
            return null;
        }

        // Check if alert already exists - use claim's primary key (id), not external claim_id
        $existingAlert = BillingUnderpaymentAlert::where('claim_id', $claim->id)
            ->where('status', 'active')
            ->first();

        if ($existingAlert) {
            return $existingAlert;
        }

        // Create new alert
        $alert = BillingUnderpaymentAlert::create([
            'claim_id' => $claim->id,
            'expected_amount' => $claim->expected_amount,
            'paid_amount' => $claim->paid_amount,
            'variance' => $this->calculateVariance($claim->expected_amount, $claim->paid_amount),
            'threshold_percentage' => $this->getThresholdPercentage(),
            'flagged_at' => now(),
            'status' => 'active',
        ]);

        Log::info('Underpayment alert created', [
            'claim_id' => $claim->id,
            'external_claim_id' => $claim->claim_id,
            'expected_amount' => $claim->expected_amount,
            'paid_amount' => $claim->paid_amount,
            'variance' => $alert->variance,
            'threshold_percentage' => $this->getThresholdPercentage(),
        ]);

        return $alert;
    }

    /**
     * Detect underpayments for multiple claims.
     */
    public function detectUnderpaymentsForClaims(array $claimIds): array
    {
        $claims = Claim::whereIn('id', $claimIds)->get();
        $alerts = [];

        foreach ($claims as $claim) {
            $alert = $this->detectAndFlagUnderpayment($claim);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Get underpayment data for a claim.
     */
    public function getUnderpaymentData(Claim $claim): array
    {
        return [
            'claim_id' => $claim->claim_id,
            'expected' => $claim->expected_amount,
            'paid' => $claim->paid_amount,
            'variance' => $this->calculateVariance($claim->expected_amount, $claim->paid_amount),
        ];
    }
}
