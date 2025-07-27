<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\OpenAIUsage;
use Illuminate\Console\Command;

class TestOpenAITracking extends Command
{
    protected $signature = 'test:openai-tracking';
    protected $description = 'Test OpenAI usage tracking by creating sample records';

    public function handle()
    {
        $this->info('Testing OpenAI usage tracking...');
        
        // Find a user to test with
        $user = User::where('monthly_cost_limit', '>', 0)->first();
        
        if (!$user) {
            $this->error('No user found with cost limit. Please set a cost limit for a user first.');
            return 1;
        }
        
        $this->info("Testing with user: {$user->name} (ID: {$user->id})");
        
        // Create some sample OpenAI usage records
        $sampleUsages = [
            [
                'model_used' => 'gpt-4o',
                'prompt_tokens' => 1000,
                'completion_tokens' => 500,
                'total_tokens' => 1500,
            ],
            [
                'model_used' => 'gpt-4o-mini',
                'prompt_tokens' => 800,
                'completion_tokens' => 300,
                'total_tokens' => 1100,
            ],
            [
                'model_used' => 'gpt-4',
                'prompt_tokens' => 600,
                'completion_tokens' => 400,
                'total_tokens' => 1000,
            ]
        ];
        
        foreach ($sampleUsages as $usage) {
            $costEstimate = OpenAIUsage::calculateCost(
                $usage['total_tokens'], 
                $usage['model_used'], 
                $usage['prompt_tokens'], 
                $usage['completion_tokens']
            );
            
            OpenAIUsage::create([
                'user_id' => $user->id,
                'request_type' => 'test',
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
                'total_tokens' => $usage['total_tokens'],
                'cost_estimate' => $costEstimate,
                'model_used' => $usage['model_used'],
                'request_metadata' => [
                    'test' => true,
                    'timestamp' => now()->toISOString(),
                ]
            ]);
            
            $this->info("Created usage record: {$usage['model_used']}, {$usage['total_tokens']} tokens, $" . number_format($costEstimate, 4));
        }
        
        // Show updated user stats
        $monthlyCost = $user->getMonthlyCostEstimate();
        $usagePercentage = $user->getCostUsagePercentage();
        $excessCost = $user->getExcessCost();
        
        $this->info("\nUpdated user stats:");
        $this->info("Monthly cost: $" . number_format($monthlyCost, 4));
        $this->info("Cost limit: $" . number_format($user->monthly_cost_limit, 2));
        $this->info("Usage percentage: " . number_format($usagePercentage, 2) . "%");
        $this->info("Excess cost: $" . number_format($excessCost, 4));
        
        $this->info("\nOpenAI usage tracking test completed!");
        
        return 0;
    }
}