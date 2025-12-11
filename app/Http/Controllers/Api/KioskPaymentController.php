<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\KioskPayment;
use App\Models\KioskSession;
use App\Services\StripeService;
use App\Services\AuditLoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class KioskPaymentController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    /**
     * Create payment intent for kiosk payment
     */
    public function createPaymentIntent(Request $request, Appointment $appointment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kiosk_session_id' => 'required|string|exists:kiosk_sessions,session_id',
            'amount' => 'required|integer|min:1', // Amount in cents
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if appointment already has a payment
            $existingPayment = $appointment->kioskPayments()->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment already has a payment record',
                    'data' => $existingPayment,
                ], 409);
            }

            // Verify kiosk session is active
            $session = KioskSession::where('session_id', $request->kiosk_session_id)
                                  ->where('status', 'active')
                                  ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive kiosk session',
                ], 422);
            }

            // Initialize Stripe
            Stripe::setApiKey(config('stripe.secret'));

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'usd',
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'kiosk_session_id' => $request->kiosk_session_id,
                    'appointment_number' => $appointment->appointment_number,
                    'patient_name' => $appointment->patient_name,
                ],
                'description' => "Payment for appointment {$appointment->appointment_number}",
            ]);

            // Create payment record
            $payment = KioskPayment::create([
                'appointment_id' => $appointment->id,
                'kiosk_session_id' => $request->kiosk_session_id,
                'stripe_payment_intent' => $paymentIntent->id,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'usd',
                'status' => 'pending',
                'payment_metadata' => [
                    'client_secret' => $paymentIntent->client_secret,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment intent created successfully',
                'data' => [
                    'payment' => $payment,
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                ],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment intent creation failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent',
            ], 500);
        }
    }

    /**
     * Confirm payment completion
     */
    public function confirmPayment(Request $request, KioskPayment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Initialize Stripe
            Stripe::setApiKey(config('stripe.secret'));

            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            // Update payment record based on Stripe status
            $status = match($paymentIntent->status) {
                'succeeded' => 'succeeded',
                'processing' => 'processing',
                'requires_payment_method' => 'failed',
                'canceled' => 'cancelled',
                default => 'failed',
            };

            $payment->update([
                'status' => $status,
                'processed_at' => $status === 'succeeded' ? now() : null,
                'payment_metadata' => array_merge(
                    $payment->payment_metadata ?? [],
                    [
                        'stripe_status' => $paymentIntent->status,
                        'last_updated' => now()->toISOString(),
                    ]
                ),
            ]);

            // Update appointment payment status if payment succeeded
            if ($status === 'succeeded') {
                $payment->appointment->update([
                    'payment_status' => 'paid',
                    'payment_intent_id' => $paymentIntent->id,
                ]);
            }

            // Log payment confirmation
            AuditLoggingService::logKioskPayment(
                $payment->appointment_id,
                $payment->kiosk_session_id,
                $payment->amount,
                $status,
                [
                    'stripe_payment_intent' => $paymentIntent->id,
                    'appointment_number' => $payment->appointment->appointment_number,
                    'patient_name' => $payment->appointment->patient_name,
                    'stripe_status' => $paymentIntent->status,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'appointment' => $payment->appointment->fresh(),
                ],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment',
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(KioskPayment $payment): JsonResponse
    {
        try {
            // Initialize Stripe
            Stripe::setApiKey(config('stripe.secret'));

            // Retrieve latest status from Stripe
            $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_intent);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment' => $payment,
                    'stripe_status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                ],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment status check failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment status: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment status',
            ], 500);
        }
    }

    /**
     * Process refund for kiosk payment
     */
    public function refundPayment(Request $request, KioskPayment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|integer|min:1|max:' . $payment->amount,
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if payment can be refunded
            if (!in_array($payment->status, ['succeeded'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be refunded',
                ], 422);
            }

            // Initialize Stripe
            Stripe::setApiKey(config('stripe.secret'));

            // Create refund
            $refundParams = [
                'payment_intent' => $payment->stripe_payment_intent,
            ];

            if ($request->amount) {
                $refundParams['amount'] = $request->amount;
            }

            if ($request->reason) {
                $refundParams['metadata'] = ['reason' => $request->reason];
            }

            $refund = \Stripe\Refund::create($refundParams);

            // Update payment status
            $payment->update([
                'status' => 'refunded',
                'payment_metadata' => array_merge(
                    $payment->payment_metadata ?? [],
                    [
                        'refund_id' => $refund->id,
                        'refund_amount' => $refund->amount,
                        'refund_reason' => $request->reason,
                        'refunded_at' => now()->toISOString(),
                    ]
                ),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'refund' => $refund,
                ],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund',
            ], 500);
        }
    }

    /**
     * Get payments for a kiosk session
     */
    public function getSessionPayments(KioskSession $session): JsonResponse
    {
        try {
            $payments = $session->payments()
                               ->with(['appointment.doctor', 'appointment.patient'])
                               ->orderBy('created_at', 'desc')
                               ->get();

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
            Log::error('Get session payments failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get session payments',
            ], 500);
        }
    }
}
