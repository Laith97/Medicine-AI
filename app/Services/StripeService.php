<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\StripeInvoice;
use App\Models\SystemSetting;
use App\Mail\SubscriptionConfirmation;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\Subscription as StripeSubscription;
use Stripe\WebhookEndpoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create or retrieve a Stripe customer for a user
     */
    public function createOrGetCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            try {
                // Use cache to avoid repeated API calls for same customer
                return Cache::remember("stripe_customer_{$user->stripe_customer_id}", 3600, function() use ($user) {
                    return Customer::retrieve($user->stripe_customer_id);
                });
            } catch (\Exception $e) {
                Log::warning("Failed to retrieve Stripe customer {$user->stripe_customer_id}: " . $e->getMessage());
                // Clear invalid customer ID
                $user->update(['stripe_customer_id' => null]);
            }
        }

        // Create new customer with optimized payload
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string)$user->id,
                'role' => $user->role,
            ],
        ]);

        // Update user with new customer ID
        $user->update(['stripe_customer_id' => $customer->id]);
        
        // Cache the new customer
        Cache::put("stripe_customer_{$customer->id}", $customer, 3600);

        return $customer;
    }

    /**
     * Create a checkout session for subscription
     */
    public function createCheckoutSession(User $user, string $planName, string $billingCycle = 'monthly'): Session
    {
        // Validate Stripe configuration
        $this->validateStripeConfiguration();
        
        $customer = $this->createOrGetCustomer($user);
        $planConfig = config("stripe.plans.{$planName}");
        
        if (!$planConfig) {
            throw new \InvalidArgumentException("Invalid plan: {$planName}. Available plans: " . implode(', ', array_keys(config('stripe.plans', []))));
        }

        $priceId = $billingCycle === 'yearly' 
            ? $planConfig['stripe_price_id_yearly'] 
            : $planConfig['stripe_price_id_monthly'];

        if (!$priceId || $priceId === 'price_basic_monthly_id' || strpos($priceId, 'your_') !== false || $priceId === '1' || $priceId === 'disabled') {
            throw new \InvalidArgumentException("Price ID not configured for {$planName} {$billingCycle}. Please set up your Stripe price IDs in the .env file or run 'php artisan stripe:setup-products' to create them.");
        }

        return Session::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('dashboard') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('dashboard'),
            'metadata' => [
                'user_id' => $user->id,
                'plan_name' => $planName,
                'billing_cycle' => $billingCycle,
            ],
        ]);
    }

    /**
     * Create a personalized checkout session based on admin-configured amount
     */
    public function createPersonalizedCheckoutSession(User $user, float $amount, string $billingCycle = 'monthly'): Session
    {
        // Validate Stripe configuration once at start
        $this->validateStripeConfiguration();
        
        // Get or create customer (with caching to reduce API calls)
        $customer = $this->createOrGetCustomer($user);
        
        // Convert amount to cents for Stripe
        $amountInCents = (int) ($amount * 100);
        
        // Determine Stripe interval and plan name
        $stripeInterval = $billingCycle === 'yearly' ? 'year' : 'month';
        $planDisplayName = $billingCycle === 'yearly' ? 'Annual Plan' : 'Monthly Plan';
        $intervalText = $billingCycle === 'yearly' ? 'year' : 'month';
        
        // Determine success and cancel URLs based on user type
        if ($user->isHospitalAdmin()) {
            $successUrl = route('hospital-admin.subscription.success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('hospital-admin.subscription.manage');
        } else {
            $successUrl = route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('subscription.manage');
        }

        return Session::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => "Medical AI Assistant - {$planDisplayName}",
                        'description' => "Personalized medical diagnosis assistance for {$user->name} - billed {$intervalText}ly",
                    ],
                    'unit_amount' => $amountInCents,
                    'recurring' => [
                        'interval' => $stripeInterval,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => $user->id,
                'plan_name' => 'personal',
                'billing_cycle' => $billingCycle,
                'billing_amount' => $amount,
                'custom_pricing' => 'true',
            ],
        ]);
    }

    /**
     * Validate Stripe configuration
     */
    private function validateStripeConfiguration(): void
    {
        $stripeSecret = config('stripe.secret');
        $stripeKey = config('stripe.key');
        
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            throw new \InvalidArgumentException('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
        }
        
        if (!$stripeKey || $stripeKey === 'pk_test_your_publishable_key_here') {
            throw new \InvalidArgumentException('Stripe publishable key is not configured. Please set STRIPE_KEY in your .env file.');
        }
    }

    /**
     * Handle successful subscription creation
     */
    public function handleSubscriptionCreated(array $subscriptionData): void
    {
        $stripeSubscription = StripeSubscription::retrieve($subscriptionData['id'], [
            'expand' => ['items.data.price']
        ]);
        $customer = Customer::retrieve($stripeSubscription->customer);
        
        $user = User::where('stripe_customer_id', $customer->id)->first();
        
        if (!$user) {
            Log::error("User not found for Stripe customer: {$customer->id}");
            return;
        }

        // Get plan info from metadata or line items
        $planName = $this->extractPlanFromSubscription($stripeSubscription);
        $billingCycle = $stripeSubscription->items->data[0]->price->recurring->interval === 'year' ? 'yearly' : 'monthly';
        
        // Create subscription record
        Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription->id],
            [
                'user_id' => $user->id,
                'stripe_customer_id' => $customer->id,
                'plan_name' => $planName,
                'billing_cycle' => $billingCycle,
                'status' => $stripeSubscription->status,
                'amount' => $stripeSubscription->items->data[0]->price->unit_amount / 100,
                'current_period_start' => $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_start'),
                'current_period_end' => $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_end'),
                'metadata' => [
                    'stripe_data' => $subscriptionData,
                ],
            ]
        );

        // Update user subscription status via MonthlyInvoiceSetting
        if ($user->monthlyInvoiceSetting) {
            $subscriptionPeriodMonths = $billingCycle === 'yearly' ? 12 : 1;
            
            // If user is in trial, implement trial-to-subscription transition with proration
            if ($user->isInTrialPeriod() && $user->trial_ends_at) {
                $remainingTrialDays = $user->getTrialDaysRemaining();
                $totalTrialDays = SystemSetting::get('trial_days', 14);
                
                // Calculate subscription start and end dates with trial proration
                $subscriptionStartDate = $user->trial_ends_at; // Start when trial ends
                $subscriptionEndDate = $user->trial_ends_at->copy()->addMonths($subscriptionPeriodMonths);
                
                // Log the transition details
                Log::info("User {$user->id} subscribed during trial. Implementing trial proration.", [
                    'trial_ends_at' => $user->trial_ends_at,
                    'remaining_trial_days' => $remainingTrialDays,
                    'total_trial_days' => $totalTrialDays,
                    'subscription_starts_at' => $subscriptionStartDate,
                    'subscription_ends_at' => $subscriptionEndDate,
                    'billing_cycle' => $billingCycle,
                    'subscription_period_months' => $subscriptionPeriodMonths
                ]);
                
                // Update user's trial status to indicate transition
                $user->update([
                    'trial_ends_at' => null, // Clear trial end date as subscription is starting
                    'trial_used' => true,
                ]);
                
                // Log the successful transition
                Log::info("Trial-to-subscription transition completed for user {$user->id}", [
                    'remaining_trial_days_credited' => $remainingTrialDays,
                    'subscription_period_extended_by_days' => $remainingTrialDays,
                    'new_subscription_ends_at' => $subscriptionEndDate
                ]);
            } else {
                // User not in trial, use Stripe's dates
                $subscriptionStartDate = $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_start');
                $subscriptionEndDate = $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_end');
                
                Log::info("User {$user->id} subscribed without active trial period.", [
                    'subscription_starts_at' => $subscriptionStartDate,
                    'subscription_ends_at' => $subscriptionEndDate,
                    'billing_cycle' => $billingCycle
                ]);
            }
            
            $user->monthlyInvoiceSetting->update([
                'subscription_starts_at' => $subscriptionStartDate,
                'subscription_ends_at' => $subscriptionEndDate,
                'subscription_period_months' => $subscriptionPeriodMonths,
                'billing_amount' => $stripeSubscription->items->data[0]->price->unit_amount / 100,
                'is_active' => true,
            ]);
        }

        // Send confirmation email
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        if ($subscription) {
            try {
                Mail::to($user->email)->send(new SubscriptionConfirmation($subscription));
                Log::info("Subscription confirmation email sent to {$user->email}");
            } catch (\Exception $e) {
                Log::error("Failed to send subscription confirmation email: " . $e->getMessage());
            }
        }

        Log::info("Subscription created for user {$user->id}: {$stripeSubscription->id}");
    }

    /**
     * Handle subscription updates
     */
    public function handleSubscriptionUpdated(array $subscriptionData): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $subscriptionData['id'])->first();
        
        if (!$subscription) {
            Log::error("Subscription not found: {$subscriptionData['id']}");
            return;
        }

        $stripeSubscription = StripeSubscription::retrieve($subscriptionData['id']);
        
        $subscription->update([
            'status' => $stripeSubscription->status,
            'current_period_start' => $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_start'),
            'current_period_end' => $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_end'),
            'canceled_at' => $stripeSubscription->canceled_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->canceled_at) : null,
        ]);

        // Update user status via MonthlyInvoiceSetting
        if ($subscription->user->monthlyInvoiceSetting) {
            $subscription->user->monthlyInvoiceSetting->update([
                'is_active' => $stripeSubscription->status === 'active',
                'subscription_ends_at' => $this->getTimestampFromSubscription($stripeSubscription, $subscriptionData, 'current_period_end'),
            ]);
        }

        Log::info("Subscription updated: {$subscriptionData['id']}");
    }

    /**
     * Handle subscription deletion/cancellation
     */
    public function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $subscriptionData['id'])->first();
        
        if (!$subscription) {
            Log::error("Subscription not found for deletion: {$subscriptionData['id']}");
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        // Update user subscription status via MonthlyInvoiceSetting
        if ($subscription->user->monthlyInvoiceSetting) {
            $subscription->user->monthlyInvoiceSetting->update([
                'is_active' => false,
                'subscription_ends_at' => now(),
            ]);
        }

        Log::info("Subscription canceled: {$subscriptionData['id']}");
    }

    /**
     * Extract plan name from Stripe subscription
     */
    private function extractPlanFromSubscription(StripeSubscription $subscription): string
    {
        // Try to get from metadata first
        if (isset($subscription->metadata['plan_name'])) {
            return $subscription->metadata['plan_name'];
        }

        // Try to match price ID to plan
        $priceId = $subscription->items->data[0]->price->id;
        $plans = config('stripe.plans');
        
        foreach ($plans as $planName => $planConfig) {
            if ($planConfig['stripe_price_id_monthly'] === $priceId || 
                $planConfig['stripe_price_id_yearly'] === $priceId) {
                return $planName;
            }
        }

        // Default fallback
        return 'basic';
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription): void
    {
        $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
        $stripeSubscription->cancel();
        
        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        // Update user subscription status via MonthlyInvoiceSetting
        if ($subscription->user->monthlyInvoiceSetting) {
            $subscription->user->monthlyInvoiceSetting->update([
                'is_active' => false,
            ]);
        }
    }

    /**
     * Handle invoice creation
     */
    public function handleInvoiceCreated(array $invoiceData): void
    {
        $stripeInvoice = \Stripe\Invoice::retrieve($invoiceData['id']);
        $customer = \Stripe\Customer::retrieve($stripeInvoice->customer);
        
        $user = User::where('stripe_customer_id', $customer->id)->first();
        
        if (!$user) {
            Log::error("User not found for Stripe customer: {$customer->id}");
            return;
        }

        // Create invoice record
        StripeInvoice::updateOrCreate(
            ['stripe_invoice_id' => $stripeInvoice->id],
            [
                'user_id' => $user->id,
                'amount_due' => $stripeInvoice->amount_due / 100,
                'amount_paid' => $stripeInvoice->amount_paid / 100,
                'status' => $stripeInvoice->status,
                'due_date' => $stripeInvoice->due_date ? \Carbon\Carbon::createFromTimestamp($stripeInvoice->due_date) : null,
                'paid_at' => $stripeInvoice->status_transitions->paid_at ? \Carbon\Carbon::createFromTimestamp($stripeInvoice->status_transitions->paid_at) : null,
                'invoice_url' => $stripeInvoice->hosted_invoice_url,
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
                'currency' => $stripeInvoice->currency,
                'description' => $stripeInvoice->description ?? 'Subscription payment',
                'line_items' => $this->extractLineItems($stripeInvoice),
                'metadata' => [
                    'stripe_data' => $invoiceData,
                ],
            ]
        );

        Log::info("Invoice created for user {$user->id}: {$stripeInvoice->id}");
    }

    /**
     * Handle successful invoice payment
     */
    public function handleInvoicePaymentSucceeded(array $invoiceData): void
    {
        $invoice = StripeInvoice::where('stripe_invoice_id', $invoiceData['id'])->first();
        
        if (!$invoice) {
            // Create the invoice if it doesn't exist
            $this->handleInvoiceCreated($invoiceData);
            $invoice = StripeInvoice::where('stripe_invoice_id', $invoiceData['id'])->first();
        }

        if ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'amount_paid' => $invoiceData['amount_paid'] / 100,
                'paid_at' => now(),
            ]);

            // Start subscription if this is the first payment and user has monthly invoice settings
            $user = $invoice->user;
            if ($user && $user->monthlyInvoiceSetting && !$user->monthlyInvoiceSetting->subscription_starts_at) {
                $user->monthlyInvoiceSetting->startSubscription();
                Log::info("Started subscription for user {$user->id} after first payment");
            }

            Log::info("Invoice payment succeeded: {$invoiceData['id']}");
        }
    }

    /**
     * Handle failed invoice payment
     */
    public function handleInvoicePaymentFailed(array $invoiceData): void
    {
        $invoice = StripeInvoice::where('stripe_invoice_id', $invoiceData['id'])->first();
        
        if (!$invoice) {
            // Create the invoice if it doesn't exist
            $this->handleInvoiceCreated($invoiceData);
            $invoice = StripeInvoice::where('stripe_invoice_id', $invoiceData['id'])->first();
        }

        if ($invoice) {
            $invoice->update([
                'status' => 'open',
            ]);

            Log::warning("Invoice payment failed: {$invoiceData['id']}");
        }
    }

    /**
     * Extract line items from Stripe invoice
     */
    private function extractLineItems(\Stripe\Invoice $invoice): array
    {
        $lineItems = [];
        
        foreach ($invoice->lines->data as $line) {
            $lineItems[] = [
                'description' => $line->description,
                'amount' => $line->amount / 100,
                'quantity' => $line->quantity,
                'period_start' => $line->period ? \Carbon\Carbon::createFromTimestamp($line->period->start)->toDateString() : null,
                'period_end' => $line->period ? \Carbon\Carbon::createFromTimestamp($line->period->end)->toDateString() : null,
            ];
        }
        
        return $lineItems;
    }

    /**
     * Get subscription usage and billing info
     */
    public function getSubscriptionUsage(User $user): array
    {
        $monthlyUsage = $user->getMonthlyTokenUsage();
        $monthlyCost = $user->getMonthlyCost();
        $setting = $user->monthlyInvoiceSetting;
        
        return [
            'monthly_usage' => $monthlyUsage,
            'monthly_cost' => $monthlyCost,
            'cost_limit' => $user->monthly_cost_limit ?? 0,
            'remaining_cost_allowance' => $user->getRemainingCostAllowance(),
            'cost_usage_percentage' => $user->getCostUsagePercentage(),
            'subscription_status' => $setting ? $setting->getSubscriptionStatus() : 'setup_pending',
            'subscription_active' => $user->hasActiveSubscription(),
            'subscription_ends_at' => $user->getSubscriptionEndDate(),
            'monthly_price' => $setting->monthly_price ?? 0,
            'yearly_price' => $setting->yearly_price ?? 0,
            'billing_amount' => $setting->billing_amount ?? 0,
        ];
    }

    /**
     * Create customer portal session
     */
    public function createCustomerPortalSession(User $user): string
    {
        try {
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => route('subscription.manage'),
            ]);

            return $session->url;
        } catch (\Exception $e) {
            // If portal is not configured, create a basic configuration
            if (str_contains($e->getMessage(), 'No configuration provided')) {
                $this->createDefaultPortalConfiguration();
                
                // Try again
                $session = \Stripe\BillingPortal\Session::create([
                    'customer' => $user->stripe_customer_id,
                    'return_url' => route('subscription.manage'),
                ]);

                return $session->url;
            }
            
            throw $e;
        }
    }

    /**
     * Create default portal configuration
     */
    private function createDefaultPortalConfiguration(): void
    {
        \Stripe\BillingPortal\Configuration::create([
            'business_profile' => [
                'headline' => 'MedCura AI - Manage your subscription',
            ],
            'features' => [
                'customer_update' => [
                    'allowed_updates' => ['email', 'address'],
                    'enabled' => true,
                ],
                'invoice_history' => ['enabled' => true],
                'payment_method_update' => ['enabled' => true],
                'subscription_cancel' => [
                    'enabled' => true,
                    'mode' => 'at_period_end',
                ],
                'subscription_update' => [
                    'enabled' => false, // Disable subscription updates to avoid product requirements
                ],
            ],
        ]);
    }

    /**
     * Get timestamp from subscription object or data array
     */
    private function getTimestampFromSubscription($stripeSubscription, array $subscriptionData, string $field): ?\Carbon\Carbon
    {
        // Try to get from the Stripe object first
        if (isset($stripeSubscription->$field) && $stripeSubscription->$field) {
            return \Carbon\Carbon::createFromTimestamp($stripeSubscription->$field);
        }
        
        // Fallback to the data array
        if (isset($subscriptionData[$field]) && $subscriptionData[$field]) {
            return \Carbon\Carbon::createFromTimestamp($subscriptionData[$field]);
        }
        
        return null;
    }
}