<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Allow access if admin is impersonating
            if (session()->has('impersonating_admin_id')) {
                return $next($request);
            }
            
            // Allow super admin direct access
            if ($user->role === 'admin') {
                return $next($request);
            }
            
            if (!$user->isHospitalAdmin()) {
                abort(403, 'Access denied. Hospital admin role required.');
            }
            
            if (!$user->hospital) {
                abort(403, 'Access denied. Hospital association required.');
            }
            
            return $next($request);
        });
    }

    /**
     * Display subscription management page
     */
    public function manage()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        // Get current subscription setting
        $setting = $user->monthlyInvoiceSetting;
        
        // Get subscription status
        $status = $user->getSubscriptionStatus();
        
        // Get subscription data (checking if it exists)
        $subscription = null; // This would come from Stripe if integrated
        
        // Get recent invoices (last 5)
        $invoices = \App\Models\StripeInvoice::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get unpaid invoices
        $unpaidInvoices = \App\Models\StripeInvoice::where('user_id', $user->id)
            ->whereIn('status', ['open', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();
        
        // Get subscription statistics
        $statistics = $this->getSubscriptionStatistics($user);
        $monthlyCost = $statistics['monthly_cost'] ?? 0;
        $costLimit = $setting ? $setting->cost_limit ?? 0 : 0;
        $costUsagePercentage = $costLimit > 0 ? ($monthlyCost / $costLimit * 100) : 0;
        
        // Calculate total unpaid amount
        $totalUnpaid = $unpaidInvoices->sum('amount_due');
        
        // Generate cost warning if needed
        $costWarning = null;
        if ($costLimit > 0 && $costUsagePercentage > 90) {
            $costWarning = "You have exceeded 90% of your monthly cost limit ($" . number_format($costLimit, 2) . "). Current usage: $" . number_format($monthlyCost, 2) . ".";
        }
        
        // Get available subscription plans
        $subscriptionPlans = SubscriptionPlan::active()->get();
        
        return view('hospital-admin.subscription.manage', compact(
            'user',
            'hospital',
            'setting',
            'status', 
            'subscription',
            'invoices',
            'unpaidInvoices',
            'statistics',
            'monthlyCost',
            'costLimit',
            'costUsagePercentage',
            'totalUnpaid',
            'costWarning',
            'subscriptionPlans'
        ));
    }

    /**
     * Update subscription plan
     */
    public function updatePlan(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'billing_period' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
        
        // Get or create monthly invoice setting
        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        
        // Calculate pricing based on billing period and number of doctors
        $doctorCount = $user->hospital->doctors()->count();
        $basePrice = $request->billing_period === 'yearly' ? $plan->yearly_price : $plan->monthly_price;
        $totalPrice = $basePrice * max(1, $doctorCount); // Minimum 1 doctor pricing
        
        // Update subscription setting
        $setting->update([
            'subscription_plan_id' => $plan->id,
            'monthly_price' => $request->billing_period === 'monthly' ? $totalPrice : 0,
            'yearly_price' => $request->billing_period === 'yearly' ? $totalPrice : 0,
            'subscription_period_months' => $request->billing_period === 'yearly' ? 12 : 1,
            'billing_amount' => $totalPrice,
            'is_active' => true,
            'subscription_starts_at' => now(),
            'subscription_ends_at' => $request->billing_period === 'yearly' 
                ? now()->addYear() 
                : now()->addMonth(),
        ]);

        return back()->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Show pricing calculator
     */
    public function pricing()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        $doctorCount = $hospital->doctors()->count();
        
        $subscriptionPlans = SubscriptionPlan::active()->get();
        
        return view('hospital-admin.subscription.pricing', compact(
            'hospital',
            'doctorCount',
            'subscriptionPlans'
        ));
    }

    /**
     * Get subscription statistics
     */
    private function getSubscriptionStatistics($user)
    {
        $setting = $user->monthlyInvoiceSetting;
        $hospital = $user->hospital;
        
        if (!$setting) {
            return [
                'status' => 'not_configured',
                'doctors_count' => $hospital->doctors()->count(),
                'monthly_cost' => 0,
                'usage_this_month' => 0,
            ];
        }
        
        // Calculate total usage for all doctors in the hospital
        $totalUsage = 0;
        $totalCost = 0;
        
        $doctors = $hospital->doctors()->get();
        foreach ($doctors as $doctor) {
            $totalUsage += $doctor->getMonthlyTokenUsage();
            $totalCost += $doctor->getMonthlyCost();
        }
        
        return [
            'status' => $user->getSubscriptionStatus(),
            'doctors_count' => $doctors->count(),
            'active_doctors_count' => $hospital->activeDoctors()->count(),
            'monthly_cost' => $totalCost,
            'usage_this_month' => $totalUsage,
            'subscription_ends_at' => $user->getSubscriptionEndDate(),
            'days_remaining' => $user->getDaysRemainingInCurrentPeriod(),
            'current_plan' => $setting->subscriptionPlan ?? null,
        ];
    }
}