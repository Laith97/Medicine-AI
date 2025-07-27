<?php

namespace App\Console\Commands;

use App\Jobs\CreateMonthlyInvoices;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly {--month= : Month in YYYY-MM format (default: current month)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for all active users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month') ?: now()->format('Y-m');
        
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Invalid month format. Use YYYY-MM format.');
            return 1;
        }
        
        $this->info("Generating monthly invoices for {$month}...");
        
        // Dispatch the job
        CreateMonthlyInvoices::dispatch($month);
        
        $this->info('Monthly invoice generation job has been queued.');
        
        return 0;
    }
}
