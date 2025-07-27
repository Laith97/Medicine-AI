<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ExcessCostBillingService;
use Illuminate\Console\Command;

class TestCostLimitSystem extends Command
{
    protected $signature = 'test:cost-limit-system';
    protected $description = 'Test the cost limit system functionality';

    public function handle()
    {
        $this->info('Testing Cost Limit System...');
        
        // Find a user with cost limit
        $user = User::where('monthly_cost_limit', '>', 0)->first();
        
        if (!$user) {
            $this->warn('No users found with cost limits set. Creating a test user...');
            
            // Create a test user
            $user = User::create([
                'name' => 'Test Doctor',
                'email' => 'test.doctor@example.com',
                'phone' => '+1234567890',
                'password' => bcrypt('password'),
                'monthly_cost_limit' => 50.00,
                'role' => 'doctor'
            ]);
            
            $user->setting()->create([
                'specialty' => 'General Practitioner',
                'criterion' => 'CDC'
            ]);
            
            $this->info("Created test user: {$user->name} with cost limit: $" . number_format($user->monthly_cost_limit, 2));
        }
        
        $this->info("Testing with user: {$user->name}");
        $this->info("Cost limit: $" . number_format($user->monthly_cost_limit, 2));
        
        // Test cost methods
        $monthlyCost = $user->getMonthlyCostEstimate();
        $usagePercentage = $user->getCostUsagePercentage();
        $excessCost = $user->getExcessCost();
        $remainingCost = $user->getRemainingCostAllowance();
        
        $this->info("Monthly cost: $" . number_format($monthlyCost, 2));
        $this->info("Usage percentage: " . number_format($usagePercentage, 2) . "%");
        $this->info("Excess cost: $" . number_format($excessCost, 2));
        $this->info("Remaining allowance: $" . number_format($remainingCost, 2));
        
        // Test billing service
        $billingService = new ExcessCostBillingService();
        $summary = $billingService->getUserExcessCostSummary($user);
        $warning = $billingService->getWarningMessage($user);
        
        $this->info("Has excess: " . ($summary['has_excess'] ? 'Yes' : 'No'));
        
        if ($warning) {
            $this->warn("Warning message: " . $warning);
        } else {
            $this->info("No warning message");
        }
        
        $this->info('Cost limit system test completed successfully!');
        
        return 0;
    }
}