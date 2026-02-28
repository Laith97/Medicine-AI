<?php

namespace App\Services;

use App\Models\User;
use App\Models\StripeInvoice;
use App\Models\MonthlyInvoiceSetting;
use App\Notifications\InvoiceCreated;
use App\Notifications\InvoiceOverdue;
use App\Notifications\InvoiceDueSoon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MonthlyInvoiceService
{
    public function __construct(
        private StripeInvoiceService $stripeInvoiceService
    ) {}

    /**
     * Generate invoices for all active users based on their billing cycle
     */
    public function generateMonthlyInvoices(Carbon $date = null): array
    {
        $date = $date ?: now();
        $month = $date->month;
        $year = $date->year;
        
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Get all users with active subscription settings
        $users = User::whereHas('monthlyInvoiceSetting', function ($query) {
            $query->where('is_active', true)
                  ->where(function ($q) {
                      $q->where('monthly_price', '>', 0)
                        ->orWhere('yearly_price', '>', 0);
                  });
        })->with('monthlyInvoiceSetting')->get();

        foreach ($users as $user) {
            try {
                $setting = $user->monthlyInvoiceSetting;
                
                // Determine if user should get an invoice this period
                $shouldCreateInvoice = $this->shouldCreateInvoiceForUser($user, $date);
                
                if (!$shouldCreateInvoice) {
                    $results['skipped']++;
                    continue;
                }

                // Check if invoice already exists for this period
                $existingInvoice = $user->getMonthlyInvoices($month, $year)->first();
                
                if ($existingInvoice) {
                    $results['skipped']++;
                    continue;
                }

                $this->createInvoiceForUser($user, $month, $year);
                $results['created']++;
                
            } catch (\Exception $e) {
                $results['errors'][] = "User {$user->id}: " . $e->getMessage();
                Log::error('Failed to create invoice', [
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Determine if a user should get an invoice for the given period
     */
    private function shouldCreateInvoiceForUser(User $user, Carbon $date): bool
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if (!$setting || !$setting->is_active) {
            return false;
        }

        // For now, we'll create invoices based on the billing_amount field
        // This represents the current active billing cycle the user chose
        // TODO: In the future, we could add a 'billing_cycle' field to track monthly vs yearly
        
        return $setting->billing_amount > 0;
    }

    /**
     * Create an invoice for a user based on their current billing cycle
     */
    private function createInvoiceForUser(User $user, int $month, int $year): StripeInvoice
    {
        $setting = $user->monthlyInvoiceSetting;
        
        // Determine billing cycle based on billing_amount
        // If billing_amount matches monthly_price, it's monthly
        // If billing_amount matches yearly_price, it's yearly
        $isYearly = $setting->billing_amount == $setting->yearly_price;
        $billingCycle = $isYearly ? 'yearly' : 'monthly';
        
        return $this->createMonthlyInvoice($user, $month, $year, $billingCycle);
    }

    /**
     * Create an invoice for a specific user
     */
    public function createMonthlyInvoice(User $user, int $month, int $year, string $billingCycle = 'monthly'): StripeInvoice
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if (!$setting || !$setting->is_active || $setting->billing_amount <= 0) {
            throw new \Exception('User does not have active subscription configured');
        }

        // Check if invoice already exists
        $existingInvoice = $user->getMonthlyInvoices($month, $year)->first();
        if ($existingInvoice) {
            throw new \Exception('Invoice already exists for this period');
        }

        // Calculate due date based on billing cycle
        if ($billingCycle === 'yearly') {
            $dueDate = Carbon::createFromDate($year, $month, 1)->addYear()->startOfMonth();
        } else {
            $dueDate = Carbon::createFromDate($year, $month, 1)->addMonth()->startOfMonth();
        }
        
        $gracePeriodEndsAt = $dueDate->copy()->addDays($setting->grace_period_days);

        // Generate appropriate invoice ID and description
        $invoicePrefix = $billingCycle === 'yearly' ? 'yearly' : 'monthly';
        $periodDescription = $billingCycle === 'yearly' 
            ? 'Annual service fee for ' . Carbon::createFromDate($year, $month, 1)->format('Y')
            : 'Monthly service fee for ' . Carbon::createFromDate($year, $month, 1)->format('F Y');

        $invoice = StripeInvoice::create([
            'user_id' => $user->id,
            'stripe_invoice_id' => $invoicePrefix . '_' . $user->id . '_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . Str::random(8),
            'invoice_type' => $billingCycle,
            'invoice_month' => $month,
            'invoice_year' => $year,
            'amount_due' => $setting->billing_amount,
            'amount_paid' => 0,
            'status' => 'open',
            'due_date' => $dueDate,
            'grace_period_ends_at' => $gracePeriodEndsAt,
            'currency' => 'usd',
            'description' => $periodDescription,
            'auto_generated' => true,
            'line_items' => [
                [
                    'description' => $billingCycle === 'yearly' ? 'Annual service fee' : 'Monthly service fee',
                    'amount' => $setting->billing_amount,
                    'quantity' => 1,
                ]
            ],
            'metadata' => [
                'type' => 'monthly_invoice',
                'period' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
            ],
        ]);

        // Send notification
        $this->sendInvoiceCreatedNotification($user, $invoice);

        return $invoice;
    }

    /**
     * Process overdue invoices and apply restrictions
     */
    public function processOverdueInvoices(): array
    {
        $results = [
            'restricted' => 0,
            'reminders_sent' => 0,
            'errors' => [],
        ];

        // Get all monthly invoices that are past grace period
        $overdueInvoices = StripeInvoice::monthly()
            ->pastGracePeriod()
            ->with(['user', 'user.monthlyInvoiceSetting'])
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                $user = $invoice->user;
                $setting = $user->monthlyInvoiceSetting;

                if (!$setting) {
                    continue;
                }

                // Restrict user if not already restricted
                if (!$setting->is_restricted) {
                    $setting->restrictAccess();
                    $results['restricted']++;
                    
                    Log::info('User restricted due to overdue invoice', [
                        'user_id' => $user->id,
                        'invoice_id' => $invoice->id,
                    ]);
                }

                // Send reminder if needed
                if ($invoice->needsReminder()) {
                    $this->sendOverdueReminder($user, $invoice);
                    $invoice->markReminderSent();
                    $results['reminders_sent']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = "Invoice {$invoice->id}: " . $e->getMessage();
                Log::error('Failed to process overdue invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Process paid invoices and remove restrictions
     */
    public function processInvoicePayments(): array
    {
        $results = [
            'unrestricted' => 0,
            'errors' => [],
        ];

        // Get all users who are currently restricted
        $restrictedUsers = User::whereHas('monthlyInvoiceSetting', function ($query) {
            $query->where('is_restricted', true);
        })->with(['monthlyInvoiceSetting', 'stripeInvoices'])->get();

        foreach ($restrictedUsers as $user) {
            try {
                // Check if user has any unpaid monthly invoices
                if (!$user->hasUnpaidMonthlyInvoices()) {
                    $setting = $user->monthlyInvoiceSetting;
                    $setting->unrestrictAccess();
                    $results['unrestricted']++;
                    
                    Log::info('User unrestricted - all invoices paid', [
                        'user_id' => $user->id,
                    ]);
                }
            } catch (\Exception $e) {
                $results['errors'][] = "User {$user->id}: " . $e->getMessage();
                Log::error('Failed to process user restrictions', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Send invoice created notification
     */
    private function sendInvoiceCreatedNotification(User $user, StripeInvoice $invoice): void
    {
        try {
            $user->notify(new InvoiceCreated($invoice));
        } catch (\Exception $e) {
            Log::error('Failed to send invoice created notification', [
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send overdue reminder notification
     */
    private function sendOverdueReminder(User $user, StripeInvoice $invoice): void
    {
        try {
            $user->notify(new InvoiceOverdue($invoice));
        } catch (\Exception $e) {
            Log::error('Failed to send overdue reminder notification', [
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update monthly invoice setting for a user
     */
    public function updateUserMonthlySettings(User $user, array $data): MonthlyInvoiceSetting
    {
        // Safety check: ensure user exists and is not deleted
        if (!$user || !$user->exists) {
            throw new \Exception('User not found or has been deleted');
        }

        // Safety check: validate restricted pages if provided
        if (isset($data['restricted_pages']) && $data['restricted_pages']) {
            $availablePages = array_keys(MonthlyInvoiceSetting::getAvailablePages());
            $invalidPages = array_diff($data['restricted_pages'], $availablePages);
            if (!empty($invalidPages)) {
                throw new \Exception('Invalid pages selected: ' . implode(', ', $invalidPages));
            }
        }

        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        
        $updateData = [
            'billing_amount' => $data['billing_amount'] ?? $setting->billing_amount,
            'grace_period_days' => $data['grace_period_days'] ?? $setting->grace_period_days,
            'reminder_frequency_days' => $data['reminder_frequency_days'] ?? $setting->reminder_frequency_days,
            'restricted_pages' => $data['restricted_pages'] ?? $setting->restricted_pages,
            'restriction_message' => $data['restriction_message'] ?? $setting->restriction_message,
            'is_active' => $data['is_active'] ?? $setting->is_active,
        ];

        // If restricted_pages are being set, also set is_restricted to true
        if (isset($data['restricted_pages']) && !empty($data['restricted_pages'])) {
            $updateData['is_restricted'] = true;
        }

        $setting->update($updateData);

        Log::info("Monthly invoice settings updated for user {$user->id} ({$user->name})", [
            'updated_data' => $updateData,
            'admin_user' => auth()->id(),
        ]);

        return $setting;
    }

    /**
     * Manually restrict a user
     */
    public function restrictUser(User $user, array $pages = null, string $message = null): void
    {
        // Safety check: ensure user exists and is not deleted
        if (!$user || !$user->exists) {
            throw new \Exception('User not found or has been deleted');
        }

        // Safety check: prevent restricting admin users
        if ($user->role === 'admin') {
            throw new \Exception('Cannot restrict admin users');
        }

        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        
        // Validate pages if provided
        if ($pages) {
            $availablePages = array_keys(MonthlyInvoiceSetting::getAvailablePages());
            $invalidPages = array_diff($pages, $availablePages);
            if (!empty($invalidPages)) {
                throw new \Exception('Invalid pages selected: ' . implode(', ', $invalidPages));
            }
        }
        
        $setting->update([
            'is_restricted' => true,
            'restricted_pages' => $pages ?: MonthlyInvoiceSetting::getDefaultRestrictedPages(),
            'restriction_message' => $message,
        ]);

        Log::info("User {$user->id} ({$user->name}) has been restricted", [
            'restricted_pages' => $pages ?: MonthlyInvoiceSetting::getDefaultRestrictedPages(),
            'message' => $message,
            'admin_user' => auth()->id(),
        ]);
    }

    /**
     * Manually unrestrict a user
     */
    public function unrestrictUser(User $user): void
    {
        // Safety check: ensure user exists and is not deleted
        if (!$user || !$user->exists) {
            throw new \Exception('User not found or has been deleted');
        }

        $setting = $user->monthlyInvoiceSetting;
        
        if ($setting) {
            $setting->unrestrictAccess();
            
            Log::info("User {$user->id} ({$user->name}) has been unrestricted", [
                'admin_user' => auth()->id(),
            ]);
        } else {
            Log::warning("Attempted to unrestrict user {$user->id} ({$user->name}) but no monthly invoice setting found");
        }
    }
}