<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class SubscriptionController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Show pricing plans
     */
    public function pricing()
    {
        $user = Auth::user();
        
        // Get fresh monthly invoice setting (no caching)
        $setting = $user->getFreshMonthlyInvoiceSetting();
        if (!$setting) {
            $setting = $user->getOrCreateMonthlyInvoiceSetting();
        }
        
        // Get user-specific plans
        $plans = $setting->getUserPlans();
        
        return view('subscription.pricing', compact('plans', 'setting'));
    }

    /**
     * Create checkout session based on selected subscription plan
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_type' => ['required', 'in:monthly,yearly']
        ]);

        try {
            $user = Auth::user();
            
            // Check if user has monthly invoice settings configured by admin
            $monthlySettings = $user->getFreshMonthlyInvoiceSetting();
            
            if (!$monthlySettings) {
                return response()->json([
                    'error' => 'Your account has not been configured yet. Please contact support to set up your subscription.'
                ], 400);
            }

            // Get the price for the selected plan type
            $planType = $request->plan_type;
            $price = $monthlySettings->getPriceForCycle($planType);
            
            if ($price <= 0) {
                return response()->json([
                    'error' => 'Pricing not configured for this plan. Please contact support.'
                ], 400);
            }

            // Update the user's billing amount for the selected plan type
            $monthlySettings->update([
                'billing_amount' => $price, // Set current billing amount for Stripe
            ]);

            // Create checkout session with selected plan pricing
            $session = $this->stripeService->createPersonalizedCheckoutSession(
                $user,
                $price
            );

            return response()->json([
                'checkout_url' => $session->url
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::error('Stripe configuration error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Stripe checkout error: ' . $e->getMessage());
            
            // Provide more specific error messages based on the error
            $errorMessage = 'Unable to create checkout session. Please try again.';
            
            if (strpos($e->getMessage(), 'No API key') !== false) {
                $errorMessage = 'Stripe is not properly configured. Please contact the administrator.';
            } elseif (strpos($e->getMessage(), 'No such price') !== false) {
                $errorMessage = 'Your pricing configuration is not available. Please contact support.';
            } elseif (strpos($e->getMessage(), 'No such customer') !== false) {
                $errorMessage = 'Customer account error. Please try again or contact support.';
            }
            
            return response()->json([
                'error' => $errorMessage
            ], 500);
        }
    }

    /**
     * Handle successful checkout
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            try {
                // Retrieve the checkout session to get subscription details
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                
                if ($session->subscription) {
                    // Get the subscription details
                    $subscription = \Stripe\Subscription::retrieve($session->subscription);
                    
                    // Manually trigger subscription creation if webhook hasn't fired yet
                    $this->stripeService->handleSubscriptionCreated($subscription->toArray());
                    
                    // Also handle the invoice if it exists
                    if ($subscription->latest_invoice) {
                        $invoice = \Stripe\Invoice::retrieve($subscription->latest_invoice);
                        $this->stripeService->handleInvoiceCreated($invoice->toArray());
                        
                        if ($invoice->status === 'paid') {
                            $this->stripeService->handleInvoicePaymentSucceeded($invoice->toArray());
                        }
                    }
                }
                
                return redirect()->route('dashboard')->with('success', 'Subscription activated successfully! Welcome to your new plan.');
            } catch (\Exception $e) {
                Log::error('Error processing successful checkout: ' . $e->getMessage());
                return redirect()->route('dashboard')->with('success', 'Payment processed successfully! Your subscription will be activated shortly.');
            }
        }

        return redirect()->route('dashboard');
    }

    /**
     * Show subscription management page
     */
    public function manage()
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;
        $invoices = $user->stripeInvoices()->orderBy('created_at', 'desc')->limit(10)->get();
        $setting = $user->monthlyInvoiceSetting;
        $status = $setting ? $setting->getSubscriptionStatus() : 'setup_pending';
        $monthlyCost = $user->getMonthlyCostEstimate();
        $costLimit = $user->monthly_cost_limit;
        $costUsagePercentage = $user->getCostUsagePercentage();
        $excessCost = $user->getExcessCost();
        $remainingCost = $user->getRemainingCostAllowance();
        $isExpired = $setting && $setting->subscription_ends_at ? $setting->subscription_ends_at->isPast() : false;
        
        // Get user-specific subscription plans for selection
        $userPlans = $setting ? $setting->getUserPlans() : [];
        
        // Get cost warning message
        $billingService = new \App\Services\ExcessCostBillingService();
        $costWarning = $billingService->getWarningMessage($user);
        
        // Additional data for the view
        $unpaidInvoices = $user->stripeInvoices()->where('status', '!=', 'paid')->get();
        $totalUnpaid = $unpaidInvoices->sum('amount_due');
        $lastInvoice = $user->getLastPaidInvoice();
        
        // Check if user has an active paid subscription
        $hasActivePaidSubscription = $setting && 
                                   $setting->subscription_starts_at && 
                                   !$setting->isSubscriptionExpired() && 
                                   !$user->isRestricted();
        
        return view('subscription.manage', compact(
            'user', 'subscription', 'invoices', 'setting', 'status', 'monthlyCost', 'costLimit',
            'costUsagePercentage', 'excessCost', 'remainingCost', 'costWarning', 'isExpired', 
            'unpaidInvoices', 'totalUnpaid', 'lastInvoice', 'userPlans', 'hasActivePaidSubscription'
        ));
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return redirect()->back()->with('error', 'No active subscription found.');
        }

        try {
            $this->stripeService->cancelSubscription($subscription);
            return redirect()->back()->with('success', 'Subscription canceled successfully.');
        } catch (\Exception $e) {
            Log::error('Subscription cancellation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to cancel subscription. Please contact support.');
        }
    }

    /**
     * Create customer portal session
     */
    public function customerPortal(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user->stripe_customer_id) {
                return redirect()->back()->with('error', 'No billing account found. Please contact support.');
            }

            $portalUrl = $this->stripeService->createCustomerPortalSession($user);
            
            return redirect($portalUrl);
        } catch (\Exception $e) {
            Log::error('Customer portal creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to access billing portal. Please try again.');
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload in Stripe webhook');
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid signature in Stripe webhook');
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'customer.subscription.created':
                $this->stripeService->handleSubscriptionCreated($event->data->object->toArray());
                break;
            
            case 'customer.subscription.updated':
                $this->stripeService->handleSubscriptionUpdated($event->data->object->toArray());
                break;
            
            case 'customer.subscription.deleted':
                $this->stripeService->handleSubscriptionDeleted($event->data->object->toArray());
                break;
            
            case 'invoice.payment_succeeded':
                $this->stripeService->handleInvoicePaymentSucceeded($event->data->object->toArray());
                break;
            
            case 'invoice.payment_failed':
                $this->stripeService->handleInvoicePaymentFailed($event->data->object->toArray());
                break;
            
            case 'invoice.created':
                $this->stripeService->handleInvoiceCreated($event->data->object->toArray());
                break;
            
            default:
                Log::info('Unhandled Stripe webhook event: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }
}
