<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Services\SubscriptionLifecycleService;
use App\Services\MonthlyInvoiceService;
use App\Services\StripeInvoiceService;
use Carbon\Carbon;

class TestEmailAutomationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-automation {--user-id=} {--period=grace} {--email=laythfares99@gmail.com} {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test automated email system by creating test scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $period = $this->option('period');
        $email = $this->option('email');
        $reset = $this->option('reset');

        $this->info("🧪 Testing Email Automation System");
        $this->newLine();

        // Step 1: Get or create test user
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("❌ User with ID $userId not found");
                return 1;
            }
            $this->info("✅ Using existing user: {$user->name} ({$user->email})");
        } else {
            $user = $this->createTestUser($email);
            $this->info("✅ Created test user: {$user->name} ({$user->email})");
        }

        // Step 2: Setup test scenario
        $setting = $this->setupTestScenario($user, $period, $reset);
        $this->displayScenarioInfo($setting);

        // Step 3: Test the automated system
        $this->info("🔄 Running automated lifecycle processor...");
        $this->newLine();

        try {
            $monthlyInvoiceService = new MonthlyInvoiceService(new StripeInvoiceService());
            $lifecycleService = new SubscriptionLifecycleService($monthlyInvoiceService);
            
            $results = $lifecycleService->processSubscriptionLifecycle();
            
            $this->displayResults($results);
            
            // Step 4: Show current status after processing
            $setting->refresh();
            $this->newLine();
            $this->info("📊 Status After Processing:");
            $this->displayScenarioInfo($setting);
            
            $this->newLine();
            $this->info("✅ Test completed!");
            $this->info("💡 Check your email: $email");
            $this->info("📝 Check logs: storage/logs/laravel.log");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    private function createTestUser($email)
    {
        return User::create([
            'name' => 'Email Test User',
            'email' => $email,
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
    }

    private function setupTestScenario(User $user, string $period, bool $reset)
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if (!$setting) {
            $setting = new MonthlyInvoiceSetting();
            $setting->user_id = $user->id;
        }

        // Reset reminder timestamp if requested
        if ($reset) {
            $setting->last_reminder_sent_at = null;
            $this->info("🔄 Reset reminder timestamp");
        }

        // Configure based on test period
        switch ($period) {
            case 'grace':
                // Subscription expired 3 days ago, still in 7-day grace period
                $setting->fill([
                    'billing_amount' => 99.99,
                    'monthly_price' => 99.99,
                    'yearly_price' => 999.99,
                    'subscription_period_months' => 1,
                    'subscription_starts_at' => now()->subDays(33), // Started 33 days ago
                    'subscription_ends_at' => now()->subDays(3),    // Expired 3 days ago
                    'grace_period_days' => 7,                       // 7-day grace (4 days left)
                    'warning_period_days' => 3,                     // 3-day warning
                    'reminder_frequency_days' => 3,                 // Remind every 3 days
                    'is_restricted' => false,
                    'is_active' => true,
                ]);
                break;

            case 'warning':
                // Grace period ended, now in warning period
                $setting->fill([
                    'billing_amount' => 99.99,
                    'monthly_price' => 99.99,
                    'yearly_price' => 999.99,
                    'subscription_period_months' => 1,
                    'subscription_starts_at' => now()->subDays(40), // Started 40 days ago
                    'subscription_ends_at' => now()->subDays(10),   // Expired 10 days ago
                    'grace_period_days' => 7,                       // Grace ended 3 days ago
                    'warning_period_days' => 5,                     // 5-day warning (2 days left)
                    'reminder_frequency_days' => 3,
                    'is_restricted' => false,
                    'is_active' => true,
                ]);
                break;

            case 'restrict':
                // All periods ended, should be restricted
                $setting->fill([
                    'billing_amount' => 99.99,
                    'monthly_price' => 99.99,
                    'yearly_price' => 999.99,
                    'subscription_period_months' => 1,
                    'subscription_starts_at' => now()->subDays(45), // Started 45 days ago
                    'subscription_ends_at' => now()->subDays(15),   // Expired 15 days ago
                    'grace_period_days' => 7,                       // Grace ended 8 days ago
                    'warning_period_days' => 3,                     // Warning ended 5 days ago
                    'reminder_frequency_days' => 3,
                    'is_restricted' => false,                       // Not yet restricted
                    'is_active' => true,
                ]);
                break;

            case 'active':
                // Active subscription (no emails should be sent)
                $setting->fill([
                    'billing_amount' => 99.99,
                    'monthly_price' => 99.99,
                    'yearly_price' => 999.99,
                    'subscription_period_months' => 1,
                    'subscription_starts_at' => now()->subDays(15), // Started 15 days ago
                    'subscription_ends_at' => now()->addDays(15),   // Expires in 15 days
                    'grace_period_days' => 7,
                    'warning_period_days' => 3,
                    'reminder_frequency_days' => 3,
                    'is_restricted' => false,
                    'is_active' => true,
                ]);
                break;

            default:
                $this->error("❌ Invalid period: $period. Use: grace, warning, restrict, or active");
                exit(1);
        }

        $setting->save();
        return $setting;
    }

    private function displayScenarioInfo(MonthlyInvoiceSetting $setting)
    {
        $status = $setting->getSubscriptionStatus();
        $now = now();
        
        $info = [
            ['Field', 'Value'],
            ['User ID', $setting->user_id],
            ['User Email', $setting->user->email],
            ['Subscription Status', $status],
            ['Subscription Started', $setting->subscription_starts_at ? $setting->subscription_starts_at->format('Y-m-d H:i') : 'N/A'],
            ['Subscription Ended', $setting->subscription_ends_at ? $setting->subscription_ends_at->format('Y-m-d H:i') : 'N/A'],
            ['Grace Period Days', $setting->grace_period_days],
            ['Warning Period Days', $setting->warning_period_days],
            ['Reminder Frequency', $setting->reminder_frequency_days . ' days'],
            ['Last Reminder Sent', $setting->last_reminder_sent_at ? $setting->last_reminder_sent_at->format('Y-m-d H:i') : 'Never'],
            ['Is Restricted', $setting->is_restricted ? 'Yes' : 'No'],
            ['Is Active', $setting->is_active ? 'Yes' : 'No'],
        ];

        // Add period calculations
        if ($setting->subscription_ends_at) {
            $daysExpired = $now->diffInDays($setting->subscription_ends_at, false);
            $info[] = ['Days Since Expiry', $daysExpired > 0 ? $daysExpired . ' days ago' : 'Not expired'];
            
            if ($setting->getGracePeriodEndDate()) {
                $graceDays = $now->diffInDays($setting->getGracePeriodEndDate(), false);
                $info[] = ['Grace Period Status', $graceDays > 0 ? $graceDays . ' days ago' : $graceDays . ' days remaining'];
            }
        }

        $this->table($info[0], array_slice($info, 1));

        // Show what should happen
        $this->newLine();
        $this->info("🎯 Expected Behavior:");
        switch ($status) {
            case 'grace_period':
                $shouldSend = !$setting->last_reminder_sent_at || 
                             $setting->last_reminder_sent_at->addDays($setting->reminder_frequency_days)->isPast();
                $this->line("  • Should send grace period reminder: " . ($shouldSend ? '✅ YES' : '❌ NO'));
                break;
            case 'warning_period':
                $shouldSend = !$setting->last_reminder_sent_at || 
                             $setting->last_reminder_sent_at->addDay()->isPast();
                $this->line("  • Should send warning reminder: " . ($shouldSend ? '✅ YES' : '❌ NO'));
                break;
            case 'should_be_restricted':
                $this->line("  • Should restrict account: ✅ YES");
                break;
            case 'active':
                $this->line("  • Should send any emails: ❌ NO");
                break;
            default:
                $this->line("  • No action needed for status: $status");
        }
    }

    private function displayResults(array $results)
    {
        $this->info("📊 Processing Results:");
        $this->table(['Metric', 'Count'], [
            ['Grace Reminders Sent', $results['grace_reminders_sent']],
            ['Warning Reminders Sent', $results['warning_reminders_sent']],
            ['Accounts Restricted', $results['accounts_restricted']],
            ['Renewal Invoices Created', $results['renewal_invoices_created']],
            ['Errors', count($results['errors'])],
        ]);

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn("⚠️  Errors encountered:");
            foreach ($results['errors'] as $error) {
                $this->line("  • $error");
            }
        }

        $totalActions = $results['grace_reminders_sent'] + 
                       $results['warning_reminders_sent'] + 
                       $results['accounts_restricted'];

        if ($totalActions > 0) {
            $this->newLine();
            $this->info("✅ $totalActions action(s) performed successfully!");
        } else {
            $this->newLine();
            $this->warn("ℹ️  No actions performed (this might be expected based on the scenario)");
        }
    }
}