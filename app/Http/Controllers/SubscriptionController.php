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
        $plans = config('stripe.plans');
        return view('subscription.pricing', compact('plans'));
    }

    /**
     * Create checkout session
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:basic,pro,enterprise',
            'billing_cycle' => 'required|string|in:monthly,yearly',
        ]);

        try {
            $user = Auth::user();
            $session = $this->stripeService->createCheckoutSession(
                $user,
                $request->plan,
                $request->billing_cycle
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
                $errorMessage = 'The selected plan is not available. Please contact support.';
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
     * Show subscription management
     */
    public function manage()
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;
        $usage = $this->stripeService->getSubscriptionUsage($user);
        
        // Get user's invoices
        $invoices = $user->stripeInvoices()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('subscription.manage', compact('subscription', 'usage', 'invoices'));
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
