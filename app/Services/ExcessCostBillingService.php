<?php

namespace App\Services;

use App\Models\User;
use App\Models\StripeInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExcessCostBillingService
{
    /**
     * Check and process excess costs for all users
     */
    public function processMonthlyExcessCosts(): void
    {
        $users = User::where('monthly_cost_limit', '>', 0)->get();
        
        foreach ($users as $user) {
            $this->processUserExcessCost($user);
        }
    }

    /**
     * Process excess cost for a specific user
     */
    public function processUserExcessCost(User $user): void
    {
        if ($user->monthly_cost_limit <= 0) {
            return; // No limit set
        }

        $excessCost = $user->getExcessCost();
        
        if ($excessCost <= 0) {
            return; // No excess cost
        }

        // Check if we already billed for this month
        $currentMonth = now()->format('Y-m');
        $existingInvoice = StripeInvoice::where('user_id', $user->id)
            ->where('description', 'LIKE', "%Excess AI Usage Cost - {$currentMonth}%")
            ->first();

        if ($existingInvoice) {
            return; // Already billed for this month
        }

        // Create invoice for excess cost
        $this->createExcessCostInvoice($user, $excessCost);
    }

    /**
     * Create an invoice for excess cost
     */
    private function createExcessCostInvoice(User $user, float $excessCost): void
    {
        try {
            $currentMonth = now()->format('F Y');
            $monthlyCost = $user->getMonthlyCostEstimate();
            $costLimit = $user->monthly_cost_limit;
            
            $description = "Excess AI Usage Cost - {$currentMonth}";
            $details = "Monthly usage: $" . number_format($monthlyCost, 2) . 
                      " | Limit: $" . number_format($costLimit, 2) . 
                      " | Excess: $" . number_format($excessCost, 2);

            // Create invoice record
            StripeInvoice::create([
                'user_id' => $user->id,
                'stripe_invoice_id' => 'excess_' . $user->id . '_' . now()->format('Ym'),
                'amount_due' => $excessCost * 100, // Convert to cents
                'amount_paid' => 0,
                'currency' => 'usd',
                'status' => 'open',
                'description' => $description,
                'invoice_pdf' => null,
                'hosted_invoice_url' => null,
                'created_at' => now(),
                'due_date' => now()->addDays(30),
                'metadata' => json_encode([
                    'type' => 'excess_cost',
                    'month' => now()->format('Y-m'),
                    'monthly_usage' => $monthlyCost,
                    'cost_limit' => $costLimit,
                    'excess_amount' => $excessCost,
                    'details' => $details
                ])
            ]);

            Log::info("Excess cost invoice created for user {$user->id}: $" . number_format($excessCost, 2));

        } catch (\Exception $e) {
            Log::error("Failed to create excess cost invoice for user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Get excess cost summary for a user
     */
    public function getUserExcessCostSummary(User $user): array
    {
        $monthlyCost = $user->getMonthlyCostEstimate();
        $costLimit = $user->monthly_cost_limit;
        $excessCost = $user->getExcessCost();
        $usagePercentage = $user->getCostUsagePercentage();

        return [
            'monthly_cost' => $monthlyCost,
            'cost_limit' => $costLimit,
            'excess_cost' => $excessCost,
            'usage_percentage' => $usagePercentage,
            'has_excess' => $excessCost > 0,
            'remaining_allowance' => $user->getRemainingCostAllowance()
        ];
    }

    /**
     * Check if user should be warned about approaching limit
     */
    public function shouldWarnUser(User $user): bool
    {
        if ($user->monthly_cost_limit <= 0) {
            return false;
        }

        $usagePercentage = $user->getCostUsagePercentage();
        return $usagePercentage >= 80; // Warn at 80% usage
    }

    /**
     * Get warning message for user
     */
    public function getWarningMessage(User $user): ?string
    {
        if (!$this->shouldWarnUser($user)) {
            return null;
        }

        $usagePercentage = $user->getCostUsagePercentage();
        $excessCost = $user->getExcessCost();
        $remainingCost = $user->getRemainingCostAllowance();

        if ($excessCost > 0) {
            return "You have exceeded your monthly cost limit by $" . number_format($excessCost, 2) . ". This excess will be added to your next invoice.";
        }

        if ($usagePercentage >= 95) {
            return "You are approaching your monthly cost limit. Only $" . number_format($remainingCost, 2) . " remaining.";
        }

        if ($usagePercentage >= 80) {
            return "You have used " . number_format($usagePercentage, 1) . "% of your monthly cost allowance.";
        }

        return null;
    }
}