<?php

namespace App\Jobs;

use App\Services\SubscriptionLifecycleService;
use App\Services\MonthlyInvoiceService;
use App\Services\StripeInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionLifecycle implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting subscription lifecycle processing');

        $monthlyInvoiceService = new MonthlyInvoiceService(new StripeInvoiceService());
        $lifecycleService = new SubscriptionLifecycleService($monthlyInvoiceService);

        $results = $lifecycleService->processSubscriptionLifecycle();

        Log::info('Subscription lifecycle processing completed', $results);

        // Log summary for admin monitoring
        if ($results['grace_reminders_sent'] > 0 || 
            $results['warning_reminders_sent'] > 0 || 
            $results['accounts_restricted'] > 0) {
            
            Log::info('Subscription lifecycle summary', [
                'grace_reminders' => $results['grace_reminders_sent'],
                'warning_reminders' => $results['warning_reminders_sent'],
                'accounts_restricted' => $results['accounts_restricted'],
                'renewal_invoices_created' => $results['renewal_invoices_created'],
                'errors_count' => count($results['errors'])
            ]);
        }
    }
}
