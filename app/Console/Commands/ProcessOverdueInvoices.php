<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOverdueInvoices as ProcessOverdueInvoicesJob;
use Illuminate\Console\Command;

class ProcessOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process overdue invoices and send reminders or apply restrictions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing overdue invoices...');
        
        // Dispatch the job
        ProcessOverdueInvoicesJob::dispatch();
        
        $this->info('Overdue invoice processing job has been queued.');
        
        return 0;
    }
}
