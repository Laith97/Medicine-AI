<?php

namespace App\Jobs;

use App\Models\StripeInvoice;
use App\Services\StripeInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncStripeInvoices implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?StripeInvoice $specificInvoice = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoiceService = new StripeInvoiceService();

        if ($this->specificInvoice) {
            // Sync specific invoice
            try {
                $invoiceService->syncInvoiceStatus($this->specificInvoice);
                Log::info("Synced invoice {$this->specificInvoice->id}");
            } catch (\Exception $e) {
                Log::error("Failed to sync invoice {$this->specificInvoice->id}: " . $e->getMessage());
            }
        } else {
            // Sync all unpaid invoices
            $unpaidInvoices = StripeInvoice::unpaid()->get();
            
            Log::info("Syncing {$unpaidInvoices->count()} unpaid invoices");

            foreach ($unpaidInvoices as $invoice) {
                try {
                    $invoiceService->syncInvoiceStatus($invoice);
                    Log::info("Synced invoice {$invoice->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to sync invoice {$invoice->id}: " . $e->getMessage());
                }
            }
        }
    }
}
