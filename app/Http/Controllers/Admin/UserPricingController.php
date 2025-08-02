<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Http\Request;

class UserPricingController extends Controller
{
    /**
     * Display a listing of users with their pricing
     */
    public function index()
    {
        $users = User::with('monthlyInvoiceSetting')
            ->where('role', '!=', 'admin')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.user-pricing.index', compact('users'));
    }

    /**
     * Show the form for editing user pricing
     */
    public function edit(User $user)
    {
        $setting = $user->monthlyInvoiceSetting ?? $user->getOrCreateMonthlyInvoiceSetting();
        return view('admin.user-pricing.edit', compact('user', 'setting'));
    }

    /**
     * Update user pricing
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'yearly_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'warning_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'reminder_frequency_days' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $setting = $user->monthlyInvoiceSetting ?? $user->getOrCreateMonthlyInvoiceSetting();
        
        $setting->update([
            'monthly_price' => $request->monthly_price,
            'yearly_price' => $request->yearly_price,
            'grace_period_days' => $request->grace_period_days,
            'warning_period_days' => $request->warning_period_days,
            'reminder_frequency_days' => $request->reminder_frequency_days,
        ]);

        return redirect()->route('admin.user-pricing.index')
            ->with('success', "Pricing updated successfully for {$user->name}.");
    }

    /**
     * Bulk update pricing for multiple users
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'yearly_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $updatedCount = 0;

        foreach ($users as $user) {
            $setting = $user->monthlyInvoiceSetting ?? $user->getOrCreateMonthlyInvoiceSetting();
            $setting->update([
                'monthly_price' => $request->monthly_price,
                'yearly_price' => $request->yearly_price,
            ]);
            $updatedCount++;
        }

        return redirect()->route('admin.user-pricing.index')
            ->with('success', "Pricing updated for {$updatedCount} users.");
    }
}
