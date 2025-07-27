<?php

namespace App\Console\Commands;

use App\Services\ExcessCostBillingService;
use Illuminate\Console\Command;

class ProcessMonthlyExcessCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-excess-costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly excess costs for users who exceeded their AI usage limits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing monthly excess costs...');
        
        $billingService = new ExcessCostBillingService();
        $billingService->processMonthlyExcessCosts();
        
        $this->info('Monthly excess costs processed successfully.');
    }
}
