<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Traits\HandlesEffectiveDoctor;

class SubscriptionController extends Controller
{
    use HandlesEffectiveDoctor;
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
        $user = auth()->user();
        
        // For hospital admins, use the hospital admin user directly
        // For doctors, use the effective doctor user (handles sub-users)
        if ($user->isHospitalAdmin()) {
            $billingUser = $user;
        } else {
            $billingUser = $this->getEffectiveDoctorUser();
        }
        
        // Get or create monthly invoice setting
        $setting = $billingUser->getOrCreateMonthlyInvoiceSetting();
        
        // For hospital admins, calculate pricing based on number of doctors
        $doctorCount = 1; // Default for individual doctors
        if ($user->isHospitalAdmin() && $user->hospital) {
            $doctorCount = max(1, $user->hospital->doctors()->count());
        }
        
        // For hospital admins, set default pricing if not configured
        if ($user->isHospitalAdmin() && ($setting->monthly_price <= 0 || $setting->yearly_price <= 0)) {
            // Get pricing from system settings
            $defaultMonthly = \App\Models\SystemSetting::get('saas_professional_monthly', 30);
            $defaultYearly = \App\Models\SystemSetting::get('saas_professional_yearly', 300);
            
            $setting->update([
                'monthly_price' => $defaultMonthly * $doctorCount,
                'yearly_price' => $defaultYearly * $doctorCount,
                'is_active' => true,
            ]);
            
            $setting->refresh();
        }
        
        // For individual doctors, set default pricing if not configured
        if (!$user->isHospitalAdmin() && ($setting->monthly_price <= 0 || $setting->yearly_price <= 0)) {
            // Get pricing from system settings
            $defaultMonthly = \App\Models\SystemSetting::get('saas_professional_monthly', 30);
            $defaultYearly = \App\Models\SystemSetting::get('saas_professional_yearly', 300);
            
            $setting->update([
                'monthly_price' => $defaultMonthly,
                'yearly_price' => $defaultYearly,
                'is_active' => true,
            ]);
            
            $setting->refresh();
        }
        
        // Get user-specific plans
        $plans = $setting->getUserPlans();
        
        // Add trial information for better UX
        $trialInfo = [
            'is_in_trial' => $user->isInTrialPeriod(),
            'trial_days_remaining' => $user->getTrialDaysRemaining(),
            'trial_status' => $user->getTrialStatus(),
            'has_used_trial' => $user->hasUsedTrial(),
            'has_future_subscription' => $setting && 
                                        $setting->subscription_starts_at &&
                                        $setting->subscription_starts_at->isFuture(),
        ];
        
        // Use different views for hospital admins vs doctors
        if ($user->isHospitalAdmin()) {
            return view('hospital-admin.subscription.pricing', compact('plans', 'setting', 'doctorCount', 'trialInfo'));
        } else {
            return view('subscription.pricing', compact('plans', 'setting', 'doctorCount', 'trialInfo'));
        }
    }

    /**
     * Create checkout session based on selected subscription plan
     */
    public function checkout(Request $request)
    {
        // Ensure we always return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            try {
                Log::info('Checkout request received', [
                    'user_id' => auth()->id(),
                    'plan_type' => $request->plan_type,
                    'user_role' => auth()->user()->role ?? 'unknown'
                ]);

                $request->validate([
                    'plan_type' => ['required', 'in:monthly,yearly']
                ]);

                $currentUser = auth()->user();
                
                // For hospital admins, use the hospital admin user directly
                // For doctors, use the effective doctor user (handles sub-users)
                if ($currentUser->isHospitalAdmin()) {
                    $user = $currentUser;
                } else {
                    $user = $this->getEffectiveDoctorUser();
                }
                
                if (!$user) {
                    return response()->json([
                        'error' => 'Authentication required. Please log in and try again.'
                    ], 401);
                }
                
                // Get or create monthly invoice settings
                $monthlySettings = $user->getOrCreateMonthlyInvoiceSetting();
                
                // For hospital admins, set default pricing if not configured
                if ($currentUser->isHospitalAdmin() && ($monthlySettings->monthly_price <= 0 || $monthlySettings->yearly_price <= 0)) {
                    $doctorCount = $user->hospital ? $user->hospital->doctors()->count() : 1;
                    $doctorCount = max(1, $doctorCount); // Minimum 1 doctor
                    
                    // Get pricing from system settings
                    $defaultMonthly = \App\Models\SystemSetting::get('saas_professional_monthly', 30);
                    $defaultYearly = \App\Models\SystemSetting::get('saas_professional_yearly', 300);
                    
                    $monthlySettings->update([
                        'monthly_price' => $defaultMonthly * $doctorCount,
                        'yearly_price' => $defaultYearly * $doctorCount,
                        'is_active' => true,
                    ]);
                    
                    $monthlySettings->refresh();
                }
                
                // For individual doctors, set default pricing if not configured
                if (!$currentUser->isHospitalAdmin() && ($monthlySettings->monthly_price <= 0 || $monthlySettings->yearly_price <= 0)) {
                    // Get pricing from system settings
                    $defaultMonthly = \App\Models\SystemSetting::get('saas_professional_monthly', 30);
                    $defaultYearly = \App\Models\SystemSetting::get('saas_professional_yearly', 300);
                    
                    $monthlySettings->update([
                        'monthly_price' => $defaultMonthly,
                        'yearly_price' => $defaultYearly,
                        'is_active' => true,
                    ]);
                    
                    $monthlySettings->refresh();
                }

                // Get the price for the selected plan type
                $planType = $request->plan_type;
                $price = $monthlySettings->getPriceForCycle($planType);
                
                if ($price <= 0) {
                    return response()->json([
                        'error' => 'Pricing not configured for this plan. Please contact support.'
                    ], 400);
                }

                // Update the user's billing settings for the selected plan type
                $subscriptionPeriodMonths = $planType === 'yearly' ? 12 : 1;
                $monthlySettings->update([
                    'billing_amount' => $price,
                    'subscription_period_months' => $subscriptionPeriodMonths,
                ]);

                // Create checkout session with selected plan pricing
                $session = $this->stripeService->createPersonalizedCheckoutSession(
                    $user,
                    $price,
                    $planType
                );

                Log::info('Checkout session created successfully', [
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                    'checkout_url' => $session->url,
                    'price' => $price,
                    'plan_type' => $planType
                ]);

                return response()->json([
                    'success' => true,
                    'checkout_url' => $session->url
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'error' => 'Invalid plan type selected.',
                    'validation_errors' => $e->errors()
                ], 422);
            } catch (\InvalidArgumentException $e) {
                Log::error('Stripe configuration error: ' . $e->getMessage());
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('Stripe API error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Payment system error. Please try again or contact support.'
                ], 500);
            } catch (\Exception $e) {
                Log::error('Stripe checkout error: ' . $e->getMessage(), [
                    'user_id' => Auth::id(),
                    'plan_type' => $request->plan_type ?? 'unknown',
                    'trace' => $e->getTraceAsString()
                ]);
                
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
        
        // For non-AJAX requests, redirect back with error
        return redirect()->back()->with('error', 'This endpoint only accepts AJAX requests.');
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
                
                // Redirect to appropriate dashboard based on user type
                $user = auth()->user();
                if ($user->isHospitalAdmin()) {
                    return redirect()->route('hospital-admin.subscription.manage')->with('success', 'Subscription activated successfully! Welcome to your new plan.');
                } else {
                    return redirect()->route('dashboard')->with('success', 'Subscription activated successfully! Welcome to your new plan.');
                }
            } catch (\Exception $e) {
                Log::error('Error processing successful checkout: ' . $e->getMessage());
                $user = auth()->user();
                if ($user->isHospitalAdmin()) {
                    return redirect()->route('hospital-admin.subscription.manage')->with('success', 'Payment processed successfully! Your subscription will be activated shortly.');
                } else {
                    return redirect()->route('dashboard')->with('success', 'Payment processed successfully! Your subscription will be activated shortly.');
                }
            }
        }

        // Redirect to appropriate dashboard based on user type
        $user = auth()->user();
        if ($user->isHospitalAdmin()) {
            return redirect()->route('hospital-admin.subscription.manage');
        } else {
            return redirect()->route('dashboard');
        }
    }

    /**
     * Show subscription management page
     */
    public function manage()
    {
        $currentUser = auth()->user();
        
        // For hospital admins, use the hospital admin user directly
        // For doctors, use the effective doctor user (handles sub-users)
        if ($currentUser->isHospitalAdmin()) {
            $user = $currentUser;
        } else {
            $user = $this->getEffectiveDoctorUser();
        }
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
        if (!$setting) {
            $setting = $user->getOrCreateMonthlyInvoiceSetting();
        }
        $userPlans = $setting->getUserPlans();
        
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
        
        // Add trial information for better UX
        $trialInfo = [
            'is_in_trial' => $user->isInTrialPeriod(),
            'trial_days_remaining' => $user->getTrialDaysRemaining(),
            'trial_status' => $user->getTrialStatus(),
            'has_used_trial' => $user->hasUsedTrial(),
            'has_future_subscription' => $setting && 
                                        $setting->subscription_starts_at &&
                                        $setting->subscription_starts_at->isFuture(),
        ];
        
        // Plans are now properly configured for all users
        
        // Use different views for hospital admins vs doctors
        if ($currentUser->isHospitalAdmin()) {
            return view('hospital-admin.subscription.manage', compact(
                'user', 'subscription', 'invoices', 'setting', 'status', 'monthlyCost', 'costLimit',
                'costUsagePercentage', 'excessCost', 'remainingCost', 'costWarning', 'isExpired', 
                'unpaidInvoices', 'totalUnpaid', 'lastInvoice', 'userPlans', 'hasActivePaidSubscription', 'trialInfo'
            ));
        } else {
            return view('subscription.manage', compact(
                'user', 'subscription', 'invoices', 'setting', 'status', 'monthlyCost', 'costLimit',
                'costUsagePercentage', 'excessCost', 'remainingCost', 'costWarning', 'isExpired', 
                'unpaidInvoices', 'totalUnpaid', 'lastInvoice', 'userPlans', 'hasActivePaidSubscription', 'trialInfo'
            ));
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $currentUser = auth()->user();
        
        // For hospital admins, use the hospital admin user directly
        // For doctors, use the effective doctor user (handles sub-users)
        if ($currentUser->isHospitalAdmin()) {
            $user = $currentUser;
        } else {
            $user = $this->getEffectiveDoctorUser();
        }
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
            $currentUser = auth()->user();
            
            // For hospital admins, use the hospital admin user directly
            // For doctors, use the effective doctor user (handles sub-users)
            if ($currentUser->isHospitalAdmin()) {
                $user = $currentUser;
            } else {
                $user = $this->getEffectiveDoctorUser();
            }
            
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
