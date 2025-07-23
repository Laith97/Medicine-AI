<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\StripeService;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Invoice;

class SyncStripeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing Stripe subscriptions and invoices with local database';

    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing Stripe data with local database...');
        $this->newLine();

        // Initialize Stripe
        $stripeSecret = config('stripe.secret');
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            $this->error('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
            return 1;
        }

        Stripe::setApiKey($stripeSecret);

        // Get all users with Stripe customer IDs
        $users = User::whereNotNull('stripe_customer_id')->get();
        
        if ($users->isEmpty()) {
            $this->warn('No users with Stripe customer IDs found.');
            return 0;
        }

        $this->info("Found {$users->count()} users with Stripe customer IDs");
        $this->newLine();

        foreach ($users as $user) {
            $this->info("Processing user: {$user->name} ({$user->email})");
            
            try {
                // Get customer from Stripe
                $customer = Customer::retrieve($user->stripe_customer_id);
                $this->line("  ✅ Customer found: {$customer->id}");

                // Get all subscriptions for this customer
                $subscriptions = Subscription::all(['customer' => $customer->id]);
                
                foreach ($subscriptions->data as $subscription) {
                    $this->line("  📋 Processing subscription: {$subscription->id}");
                    
                    // Handle subscription creation
                    $this->stripeService->handleSubscriptionCreated($subscription->toArray());
                    $this->line("    ✅ Subscription synced");

                    // Get invoices for this subscription
                    $invoices = Invoice::all(['subscription' => $subscription->id]);
                    
                    foreach ($invoices->data as $invoice) {
                        $this->line("    💰 Processing invoice: {$invoice->id}");
                        
                        // Handle invoice creation
                        $this->stripeService->handleInvoiceCreated($invoice->toArray());
                        
                        // Handle payment status
                        if ($invoice->status === 'paid') {
                            $this->stripeService->handleInvoicePaymentSucceeded($invoice->toArray());
                            $this->line("      ✅ Invoice marked as paid");
                        } else {
                            $this->line("      ⏳ Invoice status: {$invoice->status}");
                        }
                    }
                }

                $this->line("  ✅ User processing complete");
                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  ❌ Error processing user {$user->name}: " . $e->getMessage());
                $this->newLine();
            }
        }

        // Show summary
        $subscriptionCount = \App\Models\Subscription::count();
        $invoiceCount = \App\Models\StripeInvoice::count();
        
        $this->info('🎉 Sync completed successfully!');
        $this->newLine();
        $this->info("Summary:");
        $this->line("- Subscriptions in database: {$subscriptionCount}");
        $this->line("- Invoices in database: {$invoiceCount}");
        $this->newLine();

        return 0;
    }
}