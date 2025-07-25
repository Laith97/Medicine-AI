<?php

namespace App\Http\Controllers;

use App\Models\StripeInvoice;
use App\Services\StripeInvoiceService;
use App\Jobs\SyncStripeInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function __construct(
        private StripeInvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of the user's invoices
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->stripeInvoices()->with('user');

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
        $lastPaidInvoice = $user->getLastPaidInvoice();
        $nextDueInvoice = $user->getNextDueInvoice();
        $overdueCount = $user->getOverdueInvoicesCount();

        return view('invoices.index', compact(
            'invoices',
            'totalUnpaid',
            'totalPaid',
            'lastPaidInvoice',
            'nextDueInvoice',
            'overdueCount'
        ));
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

        $paymentUrl = $this->invoiceService->getPaymentUrl($invoice);

        if (!$paymentUrl) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Payment URL is not available for this invoice.');
        }

        return redirect($paymentUrl);
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
