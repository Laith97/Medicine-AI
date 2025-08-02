<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->get();
        return view('admin.subscription-plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create()
    {
        return view('admin.subscription-plans.create');
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:subscription_plans'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $billingPeriodMonths = $request->billing_cycle === 'yearly' ? 12 : 1;

        SubscriptionPlan::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'price' => $request->price,
            'billing_cycle' => $request->billing_cycle,
            'billing_period_months' => $billingPeriodMonths,
            'features' => array_filter($request->features ?? []),
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.subscription-plans.index')
                        ->with('success', 'Subscription plan created successfully.');
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load('monthlyInvoiceSettings.user');
        return view('admin.subscription-plans.show', compact('subscriptionPlan'));
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.edit', compact('subscriptionPlan'));
    }

    /**
     * Update the specified subscription plan in storage.
     */
    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('subscription_plans')->ignore($subscriptionPlan->id)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $billingPeriodMonths = $request->billing_cycle === 'yearly' ? 12 : 1;

        $subscriptionPlan->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'price' => $request->price,
            'billing_cycle' => $request->billing_cycle,
            'billing_period_months' => $billingPeriodMonths,
            'features' => array_filter($request->features ?? []),
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.subscription-plans.index')
                        ->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Remove the specified subscription plan from storage.
     */
    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        // Check if plan is being used by any users
        if ($subscriptionPlan->monthlyInvoiceSettings()->exists()) {
            return redirect()->route('admin.subscription-plans.index')
                            ->with('error', 'Cannot delete subscription plan that is currently being used by users.');
        }

        $subscriptionPlan->delete();

        return redirect()->route('admin.subscription-plans.index')
                        ->with('success', 'Subscription plan deleted successfully.');
    }

    /**
     * Toggle the active status of a subscription plan.
     */
    public function toggleActive(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->update([
            'is_active' => !$subscriptionPlan->is_active
        ]);

        $status = $subscriptionPlan->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
                        ->with('success', "Subscription plan {$status} successfully.");
    }
}