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
                'due_date' => now()->addDays(30)->timestamp,
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
                'due_date' => now()->addDays(30)->timestamp,
            ]);

            // Finalize the invoice
            $stripeInvoice->finalizeInvoice();

            // Store in our database
            $localInvoice = StripeInvoice::create([
                'user_id' => $user->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'amount_due' => $stripeInvoice->amount_due / 100,
                'amount_paid' => $stripeInvoice->amount_paid / 100,
                'status' => $stripeInvoice->status,
                'due_date' => Carbon::createFromTimestamp($stripeInvoice->due_date),
                'invoice_url' => $stripeInvoice->hosted_invoice_url,
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
                'currency' => $stripeInvoice->currency,
                'description' => $stripeInvoice->description,
                'line_items' => $lineItems,
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
    public function getPaymentUrl(StripeInvoice $invoice): string
    {
        $url = $invoice->invoice_url;
        
        // Handle case where URL might be an array
        if (is_array($url)) {
            $url = isset($url[0]) ? $url[0] : '';
        }
        
        return $url && is_string($url) ? $url : '';
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