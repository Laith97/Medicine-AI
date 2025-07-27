<?php

namespace App\Http\Controllers;

use App\Models\StripeInvoice;
use App\Services\MonthlyInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    public function __construct(
        private MonthlyInvoiceService $monthlyInvoiceService
    ) {}

    /**
     * Handle Stripe webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpoint_secret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload in webhook: ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid signature in webhook: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
            default:
                Log::info('Unhandled webhook event type: ' . $event->type);
        }

        return response('Success', 200);
    }

    /**
     * Handle successful checkout session
     */
    private function handleCheckoutSessionCompleted($session)
    {
        try {
            // Find the invoice by session ID
            $invoice = StripeInvoice::where('stripe_session_id', $session->id)->first();
            
            if (!$invoice) {
                Log::warning('No invoice found for session: ' . $session->id);
                return;
            }

            // Mark invoice as paid
            $invoice->update([
                'status' => 'paid',
                'amount_paid' => $invoice->amount_due,
                'paid_at' => now(),
            ]);

            // If this was a monthly invoice and user is restricted, check if they should be unrestricted
            if ($invoice->isMonthlyInvoice()) {
                $user = $invoice->user;
                if ($user->isRestricted() && !$user->hasUnpaidMonthlyInvoices()) {
                    $this->monthlyInvoiceService->unrestrictUser($user);
                }
            }

            Log::info('Invoice payment completed via checkout session', [
                'invoice_id' => $invoice->id,
                'session_id' => $session->id,
                'amount' => $invoice->amount_due,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling checkout session completed: ' . $e->getMessage(), [
                'session_id' => $session->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful invoice payment
     */
    private function handleInvoicePaymentSucceeded($stripeInvoice)
    {
        try {
            $invoice = StripeInvoice::where('stripe_invoice_id', $stripeInvoice->id)->first();
            
            if (!$invoice) {
                Log::warning('No local invoice found for Stripe invoice: ' . $stripeInvoice->id);
                return;
            }

            // Update invoice status
            $invoice->update([
                'status' => 'paid',
                'amount_paid' => $stripeInvoice->amount_paid / 100,
                'paid_at' => now(),
            ]);

            Log::info('Invoice payment succeeded', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'amount' => $stripeInvoice->amount_paid / 100,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling invoice payment succeeded: ' . $e->getMessage(), [
                'stripe_invoice_id' => $stripeInvoice->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed invoice payment
     */
    private function handleInvoicePaymentFailed($stripeInvoice)
    {
        try {
            $invoice = StripeInvoice::where('stripe_invoice_id', $stripeInvoice->id)->first();
            
            if (!$invoice) {
                Log::warning('No local invoice found for failed Stripe invoice: ' . $stripeInvoice->id);
                return;
            }

            // Update invoice status
            $invoice->update([
                'status' => 'payment_failed',
            ]);

            Log::info('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoice->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling invoice payment failed: ' . $e->getMessage(), [
                'stripe_invoice_id' => $stripeInvoice->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
}