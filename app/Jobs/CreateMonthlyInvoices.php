<?php

namespace App\Jobs;

use App\Services\MonthlyInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateMonthlyInvoices implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? now();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $monthlyInvoiceService = new MonthlyInvoiceService(new \App\Services\StripeInvoiceService());
        
        Log::info('Starting monthly invoice creation', [
            'date' => $this->date->toDateString(),
            'month' => $this->date->month,
            'year' => $this->date->year,
        ]);

        $results = $monthlyInvoiceService->generateMonthlyInvoices($this->date);

        Log::info('Monthly invoice creation completed', $results);
    }
}
