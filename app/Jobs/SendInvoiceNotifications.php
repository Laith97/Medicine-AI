<?php

namespace App\Jobs;

use App\Models\StripeInvoice;
use App\Notifications\InvoiceDueSoon;
use App\Notifications\InvoiceOverdue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendInvoiceNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting invoice notification job');

        // Send due soon notifications (3 days before due date)
        $dueSoonInvoices = StripeInvoice::dueSoon()->with('user')->get();
        
        foreach ($dueSoonInvoices as $invoice) {
            try {
                // Check if we already sent a due soon notification today
                $alreadyNotified = $invoice->user->notifications()
                    ->where('type', InvoiceDueSoon::class)
                    ->where('data->invoice_id', $invoice->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$alreadyNotified) {
                    $invoice->user->notify(new InvoiceDueSoon($invoice));
                    Log::info("Sent due soon notification for invoice {$invoice->id} to user {$invoice->user_id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send due soon notification for invoice {$invoice->id}: " . $e->getMessage());
            }
        }

        // Send overdue notifications
        $overdueInvoices = StripeInvoice::overdue()->with('user')->get();
        
        foreach ($overdueInvoices as $invoice) {
            try {
                // Check if we already sent an overdue notification today
                $alreadyNotified = $invoice->user->notifications()
                    ->where('type', InvoiceOverdue::class)
                    ->where('data->invoice_id', $invoice->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$alreadyNotified) {
                    $invoice->user->notify(new InvoiceOverdue($invoice));
                    Log::info("Sent overdue notification for invoice {$invoice->id} to user {$invoice->user_id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send overdue notification for invoice {$invoice->id}: " . $e->getMessage());
            }
        }

        Log::info("Completed invoice notification job. Due soon: {$dueSoonInvoices->count()}, Overdue: {$overdueInvoices->count()}");
    }
}
