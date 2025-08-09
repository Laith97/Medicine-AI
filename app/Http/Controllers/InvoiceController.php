<?php

namespace App\Http\Controllers;

use App\Models\StripeInvoice;
use App\Services\StripeInvoiceService;
use App\Jobs\SyncStripeInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HandlesEffectiveDoctor;

class InvoiceController extends Controller
{
    use HandlesEffectiveDoctor;
    public function __construct(
        private StripeInvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of the user's invoices
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        
        // For hospital admins, use the hospital admin user directly
        // For doctors, use the effective doctor user (handles sub-users)
        if ($currentUser->isHospitalAdmin()) {
            $user = $currentUser;
        } else {
            $user = $this->getEffectiveDoctorUser();
        }
        
        $query = $user->stripeInvoices()->with('user');

        // Filter by invoice type
        if ($request->filled('type')) {
            $query->where('invoice_type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'unpaid') {
                $query->unpaid();
            } elseif ($request->status === 'paid') {
                $query->paid();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

        // Calculate summary statistics
        $totalUnpaid = $user->getTotalUnpaidAmount();
        $totalPaid = $user->getTotalPaidAmount();
        $totalUnpaidMonthly = $user->getTotalUnpaidMonthlyAmount();
        $lastPaidInvoice = $user->getLastPaidInvoice();
        $nextDueInvoice = $user->getNextDueInvoice();
        $overdueCount = $user->getOverdueInvoicesCount();
        $isRestricted = $user->isRestricted();

        // Use different views for hospital admins vs doctors
        if ($currentUser->isHospitalAdmin()) {
            return view('hospital-admin.invoices.index', compact(
                'invoices',
                'totalUnpaid',
                'totalPaid',
                'totalUnpaidMonthly',
                'lastPaidInvoice',
                'nextDueInvoice',
                'overdueCount',
                'isRestricted'
            ));
        } else {
            return view('invoices.index', compact(
                'invoices',
                'totalUnpaid',
                'totalPaid',
                'totalUnpaidMonthly',
                'lastPaidInvoice',
                'nextDueInvoice',
                'overdueCount',
                'isRestricted'
            ));
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(StripeInvoice $invoice)
    {
        // Ensure user can only view their own invoices
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        // Sync invoice status before showing
        SyncStripeInvoices::dispatch($invoice);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Redirect to Stripe payment page
     */
    public function pay(StripeInvoice $invoice)
    {
        // Ensure user can only pay their own invoices
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        if ($invoice->isPaid()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'This invoice has already been paid.');
        }

        try {
            $paymentUrl = $this->invoiceService->getPaymentUrl($invoice);

            \Log::info('Payment URL generation attempted', [
                'invoice_id' => $invoice->id,
                'payment_url' => $paymentUrl,
                'url_length' => $paymentUrl ? strlen($paymentUrl) : 0,
                'is_stripe_url' => $paymentUrl ? (strpos($paymentUrl, 'stripe.com') !== false) : false
            ]);

            if (!$paymentUrl) {
                \Log::error('Payment URL generation failed', [
                    'invoice_id' => $invoice->id,
                    'invoice_type' => $invoice->invoice_type,
                    'invoice_url' => $invoice->invoice_url,
                    'is_monthly' => $invoice->isMonthlyInvoice(),
                    'user_id' => $invoice->user_id,
                    'stripe_customer_id' => $invoice->user->stripe_customer_id ?? 'null'
                ]);
                
                return redirect()->route('invoices.show', $invoice)
                    ->with('error', 'Payment URL is not available for this invoice. Please contact support.');
            }

            // For debugging: Check if this is a Stripe URL
            if (strpos($paymentUrl, 'stripe.com') !== false) {
                \Log::info('Redirecting to Stripe checkout', [
                    'invoice_id' => $invoice->id,
                    'redirect_url' => $paymentUrl
                ]);
                
                // Check if direct redirect is requested
                if (request()->has('direct')) {
                    return redirect()->away($paymentUrl);
                }
                
                // Use intermediate redirect page for better compatibility
                return view('invoices.redirect-to-payment', compact('paymentUrl', 'invoice'));
            } else {
                \Log::info('Redirecting to internal payment page', [
                    'invoice_id' => $invoice->id,
                    'redirect_url' => $paymentUrl
                ]);
                
                return redirect($paymentUrl);
            }
            
        } catch (\Exception $e) {
            \Log::error('Payment URL error: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Unable to process payment. Please try again or contact support.');
        }
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(StripeInvoice $invoice)
    {
        // Ensure user can only download their own invoices
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        
        $filename = "invoice-{$invoice->id}-" . now()->format('Y-m-d') . ".pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Show manual payment page for invoices when Stripe isn't available
     */
    public function manualPayment(StripeInvoice $invoice)
    {
        // Ensure user can only access their own invoices
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        if ($invoice->isPaid()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'This invoice has already been paid.');
        }

        return view('invoices.manual-payment', compact('invoice'));
    }

    /**
     * Sync invoice status with Stripe
     */
    public function sync(StripeInvoice $invoice)
    {
        // Ensure user can only sync their own invoices
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $this->invoiceService->syncInvoiceStatus($invoice);
            
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice status has been synchronized.');
        } catch (\Exception $e) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Failed to synchronize invoice status: ' . $e->getMessage());
        }
    }
}
