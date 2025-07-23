<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\StripeInvoiceService;
use App\Notifications\InvoiceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateMonthlyInvoices implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null
    ) {
        $this->startDate = $startDate ?? now()->subMonth()->startOfMonth();
        $this->endDate = $endDate ?? now()->subMonth()->endOfMonth();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoiceService = new StripeInvoiceService();
        
        // Get all users who are not admins and have token usage in the period
        $users = User::where('is_admin', false)
            ->whereHas('openaiUsages', function ($query) {
                $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
            })
            ->get();

        Log::info("Creating monthly invoices for {$users->count()} users for period {$this->startDate->format('Y-m-d')} to {$this->endDate->format('Y-m-d')}");

        foreach ($users as $user) {
            try {
                // Check if invoice already exists for this period
                $existingInvoice = $user->stripeInvoices()
                    ->where('metadata->period_start', $this->startDate->toDateString())
                    ->where('metadata->period_end', $this->endDate->toDateString())
                    ->first();

                if ($existingInvoice) {
                    Log::info("Invoice already exists for user {$user->id} for this period");
                    continue;
                }

                $invoice = $invoiceService->createTokenUsageInvoice($user, $this->startDate, $this->endDate);
                
                if ($invoice) {
                    // Send notification
                    $user->notify(new InvoiceCreated($invoice));
                    Log::info("Created invoice {$invoice->id} for user {$user->id}");
                } else {
                    Log::info("No token usage found for user {$user->id} in the specified period");
                }

            } catch (\Exception $e) {
                Log::error("Failed to create invoice for user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
