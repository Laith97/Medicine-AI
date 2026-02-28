<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Models\SystemSetting;
use App\Services\MonthlyInvoiceService;
use App\Services\StripeInvoiceService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessExpiredTrials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trials:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired trials and set up billing for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing expired trials...');

        // Find users whose trial has expired but haven't been processed yet
        $expiredTrialUsers = User::where('trial_ends_at', '<=', now())
            ->where('trial_used', true)
            ->whereDoesntHave('monthlyInvoiceSetting', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        $processedCount = 0;

        foreach ($expiredTrialUsers as $user) {
            $this->processExpiredTrialUser($user);
            $processedCount++;
        }

        $this->info("Processed {$processedCount} expired trial users.");

        return 0;
    }

    /**
     * Process a single user whose trial has expired
     */
    private function processExpiredTrialUser(User $user): void
    {
        $this->info("Processing expired trial for user: {$user->name} (ID: {$user->id})");

        // Get default settings
        $defaultAmount = SystemSetting::get('default_monthly_amount', 99.00);
        $defaultGracePeriod = SystemSetting::get('default_grace_period', 7);

        // Create or update monthly invoice setting
        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        
        $setting->update([
            'billing_amount' => $defaultAmount,
            'grace_period_days' => $defaultGracePeriod,
            'is_active' => true,
            'is_restricted' => true, // Restrict immediately after trial expires
            'restricted_pages' => MonthlyInvoiceSetting::getDefaultRestrictedPages(),
            'subscription_starts_at' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);

        $this->info("Set up billing for user {$user->name}: \${$defaultAmount}/month");

        // Create the first invoice for the current month
        try {
            $monthlyInvoiceService = new MonthlyInvoiceService(new StripeInvoiceService());
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            // Check if invoice already exists for current month
            $existingInvoice = $user->getMonthlyInvoices($currentMonth, $currentYear)->first();
            
            if (!$existingInvoice) {
                $invoice = $monthlyInvoiceService->createMonthlyInvoice($user, $currentMonth, $currentYear);
                $this->info("Created first invoice for user {$user->name}: Invoice #{$invoice->id} - \${$invoice->amount_due}");
            } else {
                $this->info("Invoice already exists for user {$user->name} for current month");
            }
        } catch (\Exception $e) {
            $this->error("Failed to create invoice for user {$user->name}: " . $e->getMessage());
        }
    }
}
