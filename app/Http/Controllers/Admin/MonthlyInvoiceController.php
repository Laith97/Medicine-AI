<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Services\MonthlyInvoiceService;
use App\Jobs\CreateMonthlyInvoices;
use App\Jobs\ProcessOverdueInvoices;
use App\Jobs\ProcessInvoicePayments;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonthlyInvoiceController extends Controller
{
    public function __construct(
        private MonthlyInvoiceService $monthlyInvoiceService
    ) {
        $this->middleware('admin');
    }

    /**
     * Display monthly invoice settings for all users
     */
    public function index(Request $request)
    {
        $query = User::with('monthlyInvoiceSetting');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereHas('monthlyInvoiceSetting', function ($q) {
                    $q->where('is_active', true);
                });
            } elseif ($request->status === 'restricted') {
                $query->whereHas('monthlyInvoiceSetting', function ($q) {
                    $q->where('is_restricted', true);
                });
            } elseif ($request->status === 'inactive') {
                $query->whereDoesntHave('monthlyInvoiceSetting')
                      ->orWhereHas('monthlyInvoiceSetting', function ($q) {
                          $q->where('is_active', false);
                      });
            }
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        // Calculate summary statistics
        $totalActiveUsers = User::whereHas('monthlyInvoiceSetting', function ($q) {
            $q->where('is_active', true);
        })->count();

        $totalRestrictedUsers = User::whereHas('monthlyInvoiceSetting', function ($q) {
            $q->where('is_restricted', true);
        })->count();

        $totalMonthlyRevenue = MonthlyInvoiceSetting::where('is_active', true)
            ->sum('billing_amount');

        return view('admin.monthly-invoices.index', compact(
            'users',
            'totalActiveUsers',
            'totalRestrictedUsers',
            'totalMonthlyRevenue'
        ));
    }

    /**
     * Show the form for editing a user's monthly invoice settings
     */
    public function edit(User $user)
    {
        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        $availablePages = MonthlyInvoiceSetting::getAvailablePages();
        
        return view('admin.monthly-invoices.edit', compact('user', 'setting', 'availablePages'));
    }

    /**
     * Update a user's monthly invoice settings
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'billing_amount' => 'required|numeric|min:0',
            'grace_period_days' => 'required|integer|min:1|max:30',
            'reminder_frequency_days' => 'required|integer|min:1|max:14',
            'restricted_pages' => 'nullable|array',
            'restricted_pages.*' => 'string',
            'restriction_message' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $this->monthlyInvoiceService->updateUserMonthlySettings($user, [
                'billing_amount' => $request->billing_amount,
                'grace_period_days' => $request->grace_period_days,
                'reminder_frequency_days' => $request->reminder_frequency_days,
                'restricted_pages' => $request->restricted_pages,
                'restriction_message' => $request->restriction_message,
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route('admin.monthly-invoices.index')
                ->with('success', 'Monthly invoice settings updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Manually restrict a user
     */
    public function restrict(Request $request, User $user)
    {
        $request->validate([
            'restricted_pages' => 'nullable|array',
            'restricted_pages.*' => 'string',
            'restriction_message' => 'nullable|string|max:500',
        ]);

        try {
            $this->monthlyInvoiceService->restrictUser(
                $user,
                $request->restricted_pages,
                $request->restriction_message
            );

            return redirect()->route('admin.monthly-invoices.index')
                ->with('success', 'User has been restricted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to restrict user: ' . $e->getMessage());
        }
    }

    /**
     * Manually unrestrict a user
     */
    public function unrestrict(User $user)
    {
        try {
            $this->monthlyInvoiceService->unrestrictUser($user);

            return redirect()->route('admin.monthly-invoices.index')
                ->with('success', 'User has been unrestricted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unrestrict user: ' . $e->getMessage());
        }
    }

    /**
     * Generate monthly invoices manually
     */
    public function generateInvoices(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        try {
            $date = Carbon::createFromFormat('Y-m', $request->month);
            CreateMonthlyInvoices::dispatch($date);

            return redirect()->route('admin.monthly-invoices.index')
                ->with('success', 'Monthly invoice generation job has been queued.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to queue invoice generation: ' . $e->getMessage());
        }
    }

    /**
     * Process overdue invoices manually
     */
    public function processOverdue(Request $request)
    {
        try {
            ProcessOverdueInvoices::dispatch();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overdue invoice processing job has been queued.'
                ]);
            }

            return redirect()->route('admin.monthly-invoices.index')
                ->with('success', 'Overdue invoice processing job has been queued.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to queue overdue processing: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to queue overdue processing: ' . $e->getMessage());
        }
    }

    /**
     * Process invoice payments manually
     */
    public function processPayments(Request $request)
    {
        try {
            ProcessInvoicePayments::dispatch();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice payment processing job has been queued.'
                ]);
            }

            return redirect()->route('admin.monthly-invoices.index')
                ->with('success', 'Invoice payment processing job has been queued.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to queue payment processing: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to queue payment processing: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,deactivate,restrict,unrestrict',
            'billing_amount' => 'nullable|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:1|max:30',
            'reminder_frequency_days' => 'nullable|integer|min:1|max:14',
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $updated = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                switch ($request->action) {
                    case 'activate':
                        $data = ['is_active' => true];
                        if ($request->filled('billing_amount')) {
                            $data['billing_amount'] = $request->billing_amount;
                        }
                        if ($request->filled('grace_period_days')) {
                            $data['grace_period_days'] = $request->grace_period_days;
                        }
                        if ($request->filled('reminder_frequency_days')) {
                            $data['reminder_frequency_days'] = $request->reminder_frequency_days;
                        }
                        $this->monthlyInvoiceService->updateUserMonthlySettings($user, $data);
                        break;

                    case 'deactivate':
                        $this->monthlyInvoiceService->updateUserMonthlySettings($user, ['is_active' => false]);
                        break;

                    case 'restrict':
                        $this->monthlyInvoiceService->restrictUser($user);
                        break;

                    case 'unrestrict':
                        $this->monthlyInvoiceService->unrestrictUser($user);
                        break;
                }
                $updated++;
            } catch (\Exception $e) {
                $errors[] = "User {$user->name}: " . $e->getMessage();
            }
        }

        $message = "Updated {$updated} users successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()->route('admin.monthly-invoices.index')
            ->with($updated > 0 ? 'success' : 'error', $message);
    }
}