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
     * Generate monthly invoices for all active users
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

        // Get all users with active monthly invoice settings
        $users = User::whereHas('monthlyInvoiceSetting', function ($query) {
            $query->where('is_active', true)
                  ->where('billing_amount', '>', 0);
        })->with('monthlyInvoiceSetting')->get();

        foreach ($users as $user) {
            try {
                // Check if invoice already exists for this month
                $existingInvoice = $user->getMonthlyInvoices($month, $year)->first();
                
                if ($existingInvoice) {
                    $results['skipped']++;
                    continue;
                }

                $this->createMonthlyInvoice($user, $month, $year);
                $results['created']++;
                
            } catch (\Exception $e) {
                $results['errors'][] = "User {$user->id}: " . $e->getMessage();
                Log::error('Failed to create monthly invoice', [
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
     * Create a monthly invoice for a specific user
     */
    public function createMonthlyInvoice(User $user, int $month, int $year): StripeInvoice
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if (!$setting || !$setting->is_active || $setting->billing_amount <= 0) {
            throw new \Exception('User does not have active monthly invoicing configured');
        }

        // Check if invoice already exists
        $existingInvoice = $user->getMonthlyInvoices($month, $year)->first();
        if ($existingInvoice) {
            throw new \Exception('Monthly invoice already exists for this period');
        }

        $dueDate = Carbon::createFromDate($year, $month, 1)->addMonth()->startOfMonth();
        $gracePeriodEndsAt = $dueDate->copy()->addDays($setting->grace_period_days);

        $invoice = StripeInvoice::create([
            'user_id' => $user->id,
            'stripe_invoice_id' => 'monthly_' . $user->id . '_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . Str::random(8),
            'invoice_type' => 'monthly',
            'invoice_month' => $month,
            'invoice_year' => $year,
            'amount_due' => $setting->billing_amount,
            'amount_paid' => 0,
            'status' => 'open',
            'due_date' => $dueDate,
            'grace_period_ends_at' => $gracePeriodEndsAt,
            'currency' => 'usd',
            'description' => 'Monthly service fee for ' . Carbon::createFromDate($year, $month, 1)->format('F Y'),
            'auto_generated' => true,
            'line_items' => [
                [
                    'description' => 'Monthly service fee',
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
        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        
        $setting->update([
            'billing_amount' => $data['billing_amount'] ?? $setting->billing_amount,
            'grace_period_days' => $data['grace_period_days'] ?? $setting->grace_period_days,
            'reminder_frequency_days' => $data['reminder_frequency_days'] ?? $setting->reminder_frequency_days,
            'restricted_pages' => $data['restricted_pages'] ?? $setting->restricted_pages,
            'restriction_message' => $data['restriction_message'] ?? $setting->restriction_message,
            'is_active' => $data['is_active'] ?? $setting->is_active,
        ]);

        return $setting;
    }

    /**
     * Manually restrict a user
     */
    public function restrictUser(User $user, array $pages = null, string $message = null): void
    {
        $setting = $user->getOrCreateMonthlyInvoiceSetting();
        
        $setting->update([
            'is_restricted' => true,
            'restricted_pages' => $pages ?: MonthlyInvoiceSetting::getDefaultRestrictedPages(),
            'restriction_message' => $message,
        ]);
    }

    /**
     * Manually unrestrict a user
     */
    public function unrestrictUser(User $user): void
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if ($setting) {
            $setting->unrestrictAccess();
        }
    }
}