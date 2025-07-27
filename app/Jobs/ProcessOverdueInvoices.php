<?php

namespace App\Jobs;

use App\Services\MonthlyInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoices implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $monthlyInvoiceService = new MonthlyInvoiceService(new \App\Services\StripeInvoiceService());
        
        Log::info('Starting overdue invoice processing');

        $results = $monthlyInvoiceService->processOverdueInvoices();

        Log::info('Overdue invoice processing completed', $results);
    }
}