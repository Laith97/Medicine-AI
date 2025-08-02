<?php

namespace App\Services;

use App\Models\User;
use App\Models\StripeInvoice;
use App\Models\OpenAIUsage;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StripeInvoiceService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create an invoice for a user based on their token usage
     */
    public function createTokenUsageInvoice(User $user, Carbon $startDate = null, Carbon $endDate = null): ?StripeInvoice
    {
        try {
            // Default to current month if no dates provided
            $startDate = $startDate ?? now()->startOfMonth();
            $endDate = $endDate ?? now()->endOfMonth();

            // Get token usage for the period
            $tokenUsages = $user->openaiUsages()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            if ($tokenUsages->isEmpty()) {
                return null;
            }

            // Ensure user has a Stripe customer ID
            if (!$user->stripe_customer_id) {
                $this->createStripeCustomer($user);
            }

            // Calculate totals
            $totalCost = $tokenUsages->sum('cost_estimate');
            $totalTokens = $tokenUsages->sum('total_tokens');
            $totalRequests = $tokenUsages->count();

            // Group by request type for line items
            $lineItems = [];
            $groupedUsages = $tokenUsages->groupBy('request_type');

            foreach ($groupedUsages as $requestType => $usages) {
                $typeTokens = $usages->sum('total_tokens');
                $typeCost = $usages->sum('cost_estimate');
                $typeRequests = $usages->count();

                $lineItems[] = [
                    'description' => ucfirst($requestType) . " requests ({$typeRequests} requests, {$typeTokens} tokens)",
                    'quantity' => $typeRequests,
                    'unit_amount' => round(($typeCost / $typeRequests) * 100), // Convert to cents
                    'amount' => round($typeCost * 100),
                ];
            }

            // Create invoice items in Stripe
            foreach ($lineItems as $item) {
                InvoiceItem::create([
                    'customer' => $user->stripe_customer_id,
                    'amount' => $item['amount'],
                    'currency' => 'usd',
                    'description' => $item['description'],
                ]);
            }

            // Create the invoice in Stripe
            $stripeInvoice = Invoice::create([
                'customer' => $user->stripe_customer_id,
                'description' => "OpenAI Token Usage - {$startDate->format('M Y')}",
                'collection_method' => 'send_invoice',
                'days_until_due' => 30,
                'metadata' => [
                    'period_start' => $startDate->toDateString(),
                    'period_end' => $endDate->toDateString(),
                    'total_tokens' => $totalTokens,
                    'total_requests' => $totalRequests,
                ],
            ]);

            // Finalize the invoice to make it payable
            $stripeInvoice->finalizeInvoice();

            // Store in our database
            $localInvoice = StripeInvoice::create([
                'user_id' => $user->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'amount_due' => $stripeInvoice->amount_due / 100, // Convert from cents
                'amount_paid' => $stripeInvoice->amount_paid / 100,
                'status' => $stripeInvoice->status,
                'due_date' => Carbon::createFromTimestamp($stripeInvoice->due_date),
                'invoice_url' => $stripeInvoice->hosted_invoice_url,
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
                'currency' => $stripeInvoice->currency,
                'description' => $stripeInvoice->description,
                'line_items' => $lineItems,
                'metadata' => $stripeInvoice->metadata->toArray(),
            ]);

            Log::info("Created invoice for user {$user->id}: {$stripeInvoice->id}");

            return $localInvoice;

        } catch (\Exception $e) {
            Log::error("Failed to create invoice for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a manual invoice for a user
     */
    public function createManualInvoice(User $user, array $items, string $description = null): StripeInvoice
    {
        try {
            // Ensure user has a Stripe customer ID
            if (!$user->stripe_customer_id) {
                $this->createStripeCustomer($user);
            }

            // Create invoice items in Stripe
            $totalAmount = 0;
            $lineItems = [];

            foreach ($items as $item) {
                $amount = round($item['amount'] * 100); // Convert to cents
                $totalAmount += $amount;

                InvoiceItem::create([
                    'customer' => $user->stripe_customer_id,
                    'amount' => $amount,
                    'currency' => 'usd',
                    'description' => $item['description'],
                ]);

                $lineItems[] = [
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_amount' => $amount,
                    'amount' => $amount,
                ];
            }

            // Create the invoice in Stripe
            $stripeInvoice = Invoice::create([
                'customer' => $user->stripe_customer_id,
                'description' => $description ?? 'Manual Invoice',
                'collection_method' => 'send_invoice',
                'days_until_due' => 30,
                'auto_advance' => false, // Prevent auto-finalization
            ]);

            // Finalize the invoice to make it payable
            $stripeInvoice->finalizeInvoice();

            // Store in our database - force status to 'open' for manual invoices
            $localInvoice = StripeInvoice::create([
                'user_id' => $user->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'amount_due' => max($stripeInvoice->amount_due / 100, $totalAmount / 100), // Ensure amount is correct
                'amount_paid' => 0, // Manual invoices start unpaid
                'status' => $stripeInvoice->status === 'paid' ? 'open' : $stripeInvoice->status, // Force open if Stripe says paid
                'due_date' => Carbon::createFromTimestamp($stripeInvoice->due_date),
                'invoice_url' => $stripeInvoice->hosted_invoice_url,
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
                'currency' => $stripeInvoice->currency,
                'description' => $stripeInvoice->description,
                'line_items' => $lineItems,
                'invoice_type' => 'manual', // Mark as manual invoice
            ]);

            return $localInvoice;

        } catch (\Exception $e) {
            Log::error("Failed to create manual invoice for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync invoice status from Stripe
     */
    public function syncInvoiceStatus(StripeInvoice $invoice): StripeInvoice
    {
        try {
            $stripeInvoice = Invoice::retrieve($invoice->stripe_invoice_id);

            $invoice->update([
                'amount_due' => $stripeInvoice->amount_due / 100,
                'amount_paid' => $stripeInvoice->amount_paid / 100,
                'status' => $stripeInvoice->status,
                'paid_at' => $stripeInvoice->status_transitions->paid_at ? 
                    Carbon::createFromTimestamp($stripeInvoice->status_transitions->paid_at) : null,
                'invoice_url' => $stripeInvoice->hosted_invoice_url,
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
            ]);

            return $invoice->fresh();

        } catch (\Exception $e) {
            Log::error("Failed to sync invoice {$invoice->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark invoice as paid manually (for admin use)
     */
    public function markInvoiceAsPaid(StripeInvoice $invoice): StripeInvoice
    {
        try {
            // Mark as paid in Stripe
            $stripeInvoice = Invoice::retrieve($invoice->stripe_invoice_id);
            $stripeInvoice->markAsPaid();

            // Update local record
            $invoice->update([
                'status' => 'paid',
                'amount_paid' => $invoice->amount_due,
                'paid_at' => now(),
            ]);

            // Unrestrict user when their invoice is marked as paid
            $user = $invoice->user;
            if ($user && $user->monthlyInvoiceSetting) {
                $setting = $user->monthlyInvoiceSetting;
                
                // If user was restricted, unrestrict them
                if ($setting->is_restricted) {
                    $setting->update([
                        'is_restricted' => false,
                        'subscription_starts_at' => $setting->subscription_starts_at ?: now(),
                        'subscription_ends_at' => now()->addMonth(), // Extend subscription for one month
                    ]);
                    
                    Log::info('User unrestricted after manual payment marking', [
                        'user_id' => $user->id,
                        'invoice_id' => $invoice->id,
                        'subscription_ends_at' => now()->addMonth()->toDateString(),
                    ]);
                }
            }

            return $invoice;

        } catch (\Exception $e) {
            Log::error("Failed to mark invoice {$invoice->id} as paid: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create Stripe customer for user
     */
    private function createStripeCustomer(User $user): void
    {
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            $user->update(['stripe_customer_id' => $customer->id]);

        } catch (\Exception $e) {
            Log::error("Failed to create Stripe customer for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get invoice payment URL
     */
    public function getPaymentUrl(StripeInvoice $invoice): ?string
    {
        try {
            // First, try to get the existing invoice URL
            $url = $invoice->invoice_url;
            
            // Handle case where URL might be an array
            if (is_array($url)) {
                $url = isset($url[0]) ? $url[0] : '';
            }
            
            // If we have a valid URL, return it
            if ($url && is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                Log::info('Using existing payment URL', [
                    'invoice_id' => $invoice->id,
                    'url' => $url
                ]);
                return $url;
            }
            
            // If no URL, try to sync from Stripe first
            Log::info('No payment URL found, syncing from Stripe', ['invoice_id' => $invoice->id]);
            $syncedInvoice = $this->syncInvoiceStatus($invoice);
            
            // Check if sync provided a URL
            $syncedUrl = $syncedInvoice->invoice_url;
            if (is_array($syncedUrl)) {
                $syncedUrl = isset($syncedUrl[0]) ? $syncedUrl[0] : '';
            }
            
            if ($syncedUrl && is_string($syncedUrl) && filter_var($syncedUrl, FILTER_VALIDATE_URL)) {
                Log::info('Got payment URL from sync', [
                    'invoice_id' => $invoice->id,
                    'url' => $syncedUrl
                ]);
                return $syncedUrl;
            }
            
            // For monthly invoices, create a Stripe checkout session
            if ($invoice->isMonthlyInvoice()) {
                Log::info('Creating payment session for monthly invoice', ['invoice_id' => $invoice->id]);
                
                $stripeUrl = $this->createPaymentSessionForMonthlyInvoice($invoice);
                if ($stripeUrl) {
                    return $stripeUrl;
                }
            }
            
            // Last resort: return fallback URL
            Log::warning('Using fallback payment method', [
                'invoice_id' => $invoice->id
            ]);
            
            return route('invoices.manual-payment', $invoice);
            
        } catch (\Exception $e) {
            Log::error('Error getting payment URL: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            // Return fallback URL in case of error
            return route('invoices.manual-payment', $invoice);
        }
    }
    
    /**
     * Create a Stripe checkout session for monthly invoice
     */
    private function createPaymentSessionForMonthlyInvoice(StripeInvoice $invoice): ?string
    {
        try {
            $user = $invoice->user;
            
            Log::info('Creating payment session', [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount_due,
                'user_id' => $user->id
            ]);
            
            // Check if Stripe is properly configured
            if (!config('stripe.secret')) {
                Log::error('Stripe secret key not configured');
                return null;
            }
            
            // Ensure user has a Stripe customer ID
            if (!$user->stripe_customer_id) {
                Log::info('Creating Stripe customer for user', ['user_id' => $user->id]);
                $this->createStripeCustomer($user);
                $user->refresh(); // Reload to get the stripe_customer_id
            }

            if (!$user->stripe_customer_id) {
                Log::error('Failed to create or get Stripe customer ID', ['user_id' => $user->id]);
                return null;
            }

            // Create a checkout session
            $session = \Stripe\Checkout\Session::create([
                'customer' => $user->stripe_customer_id,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $invoice->description ?: 'Monthly Service Fee',
                                'description' => "Invoice #{$invoice->id}",
                            ],
                            'unit_amount' => round($invoice->amount_due * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('invoices.show', $invoice) . '?payment=success',
                'cancel_url' => route('invoices.show', $invoice) . '?payment=cancelled',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'type' => 'monthly_invoice',
                ],
            ]);
            
            Log::info('Stripe session created successfully', [
                'invoice_id' => $invoice->id,
                'session_id' => $session->id,
                'session_url' => $session->url
            ]);
            
            // Update the invoice with the payment URL
            $invoice->update([
                'invoice_url' => $session->url,
                'stripe_session_id' => $session->id,
            ]);
            
            return $session->url;
            
        } catch (\Exception $e) {
            Log::error("Failed to create payment session for monthly invoice {$invoice->id}: " . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Void an invoice
     */
    public function voidInvoice(StripeInvoice $invoice): StripeInvoice
    {
        try {
            $stripeInvoice = Invoice::retrieve($invoice->stripe_invoice_id);
            $stripeInvoice->voidInvoice();

            $invoice->update(['status' => 'void']);

            return $invoice;

        } catch (\Exception $e) {
            Log::error("Failed to void invoice {$invoice->id}: " . $e->getMessage());
            throw $e;
        }
    }
}