<?php

namespace App\Services;

use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Models\StripeInvoice;
use App\Notifications\SubscriptionExpired;
use App\Notifications\GracePeriodReminder;
use App\Notifications\FinalWarning;
use App\Notifications\AccountRestricted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionLifecycleService
{
    public function __construct(
        private MonthlyInvoiceService $monthlyInvoiceService
    ) {}

    /**
     * Process all subscription lifecycle stages
     */
    public function processSubscriptionLifecycle(): array
    {
        $results = [
            'expired_processed' => 0,
            'grace_reminders_sent' => 0,
            'warning_reminders_sent' => 0,
            'accounts_restricted' => 0,
            'renewal_invoices_created' => 0,
            'errors' => [],
        ];

        // Get all active subscription settings
        $settings = MonthlyInvoiceSetting::where('is_active', true)
            ->whereNotNull('subscription_starts_at')
            ->where('subscription_period_months', '!=', -1) // Exclude unlimited
            ->with('user')
            ->get();

        foreach ($settings as $setting) {
            try {
                $status = $setting->getSubscriptionStatus();
                
                switch ($status) {
                    case 'grace_period':
                        $this->processGracePeriod($setting);
                        $results['grace_reminders_sent']++;
                        break;
                        
                    case 'warning_period':
                        $this->processWarningPeriod($setting);
                        $results['warning_reminders_sent']++;
                        break;
                        
                    case 'should_be_restricted':
                        $this->processAccountRestriction($setting);
                        $results['accounts_restricted']++;
                        break;
                }

                // Check if we need to create renewal invoice
                if (in_array($status, ['grace_period', 'warning_period']) && 
                    !$this->hasRenewalInvoice($setting)) {
                    $this->createRenewalInvoice($setting);
                    $results['renewal_invoices_created']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = "Setting {$setting->id}: " . $e->getMessage();
                Log::error('Failed to process subscription lifecycle', [
                    'setting_id' => $setting->id,
                    'user_id' => $setting->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Process grace period - send reminders
     */
    private function processGracePeriod(MonthlyInvoiceSetting $setting): void
    {
        $user = $setting->user;
        
        // Check if we should send a reminder
        if ($this->shouldSendGraceReminder($setting)) {
            $user->notify(new GracePeriodReminder($setting));
            
            $setting->update([
                'last_reminder_sent_at' => now()
            ]);

            Log::info('Grace period reminder sent', [
                'user_id' => $user->id,
                'days_remaining' => $setting->getDaysRemainingInCurrentPeriod()
            ]);
        }
    }

    /**
     * Process warning period - send urgent reminders
     */
    private function processWarningPeriod(MonthlyInvoiceSetting $setting): void
    {
        $user = $setting->user;
        
        // Send daily reminders during warning period
        if ($this->shouldSendWarningReminder($setting)) {
            $user->notify(new FinalWarning($setting));
            
            $setting->update([
                'last_reminder_sent_at' => now()
            ]);

            Log::info('Final warning sent', [
                'user_id' => $user->id,
                'days_remaining' => $setting->getDaysRemainingInCurrentPeriod()
            ]);
        }
    }

    /**
     * Process account restriction
     */
    private function processAccountRestriction(MonthlyInvoiceSetting $setting): void
    {
        $user = $setting->user;
        
        if (!$setting->is_restricted) {
            // Restrict the account
            $setting->restrictAccess();
            
            // Send restriction notification
            $user->notify(new AccountRestricted($setting));
            
            Log::info('Account restricted due to expired subscription', [
                'user_id' => $user->id,
                'subscription_ended' => $setting->subscription_ends_at,
                'grace_period_ended' => $setting->getGracePeriodEndDate(),
                'warning_period_ended' => $setting->getWarningPeriodEndDate()
            ]);
        }
    }

    /**
     * Check if we should send grace period reminder
     */
    private function shouldSendGraceReminder(MonthlyInvoiceSetting $setting): bool
    {
        // Send reminder based on reminder_frequency_days
        if (!$setting->last_reminder_sent_at) {
            return true; // First reminder
        }

        return $setting->last_reminder_sent_at
            ->addDays($setting->reminder_frequency_days)
            ->isPast();
    }

    /**
     * Check if we should send warning reminder
     */
    private function shouldSendWarningReminder(MonthlyInvoiceSetting $setting): bool
    {
        // Send daily reminders during warning period
        if (!$setting->last_reminder_sent_at) {
            return true; // First warning
        }

        return $setting->last_reminder_sent_at
            ->addDay()
            ->isPast();
    }

    /**
     * Check if user has a renewal invoice for current period
     */
    private function hasRenewalInvoice(MonthlyInvoiceSetting $setting): bool
    {
        $user = $setting->user;
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return $user->stripeInvoices()
            ->where('invoice_type', 'renewal')
            ->where('invoice_month', $currentMonth)
            ->where('invoice_year', $currentYear)
            ->exists();
    }

    /**
     * Create renewal invoice for expired subscription
     */
    private function createRenewalInvoice(MonthlyInvoiceSetting $setting): StripeInvoice
    {
        $user = $setting->user;
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Calculate new subscription period
        $newStartDate = now();
        $newEndDate = $newStartDate->copy()->addMonths($setting->subscription_period_months);

        $invoice = StripeInvoice::create([
            'user_id' => $user->id,
            'stripe_invoice_id' => 'renewal_' . $user->id . '_' . $currentYear . '_' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . '_' . \Str::random(8),
            'invoice_type' => 'renewal',
            'invoice_month' => $currentMonth,
            'invoice_year' => $currentYear,
            'amount_due' => $setting->billing_amount,
            'amount_paid' => 0,
            'status' => 'open',
            'due_date' => now()->addDays(3), // Due in 3 days
            'grace_period_ends_at' => now()->addDays(3 + $setting->grace_period_days),
            'currency' => 'usd',
            'description' => 'Subscription renewal for ' . $setting->getSubscriptionPeriodText(),
            'auto_generated' => true,
            'line_items' => [
                [
                    'description' => 'Subscription renewal - ' . $setting->getSubscriptionPeriodText(),
                    'amount' => $setting->billing_amount,
                    'quantity' => 1,
                ]
            ],
            'metadata' => [
                'type' => 'renewal_invoice',
                'new_start_date' => $newStartDate->toDateString(),
                'new_end_date' => $newEndDate->toDateString(),
                'subscription_period_months' => $setting->subscription_period_months,
            ],
        ]);

        Log::info('Renewal invoice created', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'amount' => $setting->billing_amount,
            'new_period' => $setting->getSubscriptionPeriodText()
        ]);

        return $invoice;
    }

    /**
     * Process renewal payment and extend subscription
     */
    public function processRenewalPayment(StripeInvoice $invoice): void
    {
        if ($invoice->invoice_type !== 'renewal' || $invoice->status !== 'paid') {
            return;
        }

        $user = $invoice->user;
        $setting = $user->monthlyInvoiceSetting;

        if (!$setting) {
            return;
        }

        // Get new dates from invoice metadata
        $metadata = $invoice->metadata;
        $newStartDate = Carbon::parse($metadata['new_start_date']);
        $newEndDate = Carbon::parse($metadata['new_end_date']);

        // Update subscription dates
        $setting->update([
            'subscription_starts_at' => $newStartDate,
            'subscription_ends_at' => $newEndDate,
            'is_restricted' => false, // Remove restriction if any
            'last_reminder_sent_at' => null, // Reset reminders
        ]);

        Log::info('Subscription renewed via payment', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'new_start_date' => $newStartDate,
            'new_end_date' => $newEndDate,
        ]);
    }

    /**
     * Get subscription lifecycle summary for admin
     */
    public function getLifecycleSummary(): array
    {
        $settings = MonthlyInvoiceSetting::where('is_active', true)
            ->whereNotNull('subscription_starts_at')
            ->where('subscription_period_months', '!=', -1)
            ->get();

        $summary = [
            'total_subscriptions' => $settings->count(),
            'active' => 0,
            'grace_period' => 0,
            'warning_period' => 0,
            'restricted' => 0,
            'unlimited' => 0,
        ];

        foreach ($settings as $setting) {
            $status = $setting->getSubscriptionStatus();
            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        // Add unlimited subscriptions
        $summary['unlimited'] = MonthlyInvoiceSetting::where('is_active', true)
            ->where('subscription_period_months', -1)
            ->count();

        return $summary;
    }
}