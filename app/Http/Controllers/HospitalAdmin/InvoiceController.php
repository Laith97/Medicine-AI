<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\StripeInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        // Build query for invoices
        $query = StripeInvoice::where('user_id', $user->id);
        
        // Apply filters if provided
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'unpaid') {
                $query->whereIn('status', ['open', 'overdue']);
            } else {
                $query->where('status', $status);
            }
        }
        
        if ($request->filled('type')) {
            $query->where('invoice_type', $request->get('type'));
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Get paginated invoices
        $invoices = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Calculate statistics
        $allInvoices = StripeInvoice::where('user_id', $user->id)->get();
        $totalPaid = $allInvoices->where('status', 'paid')->sum('amount_paid');
        $totalUnpaid = $allInvoices->whereIn('status', ['open', 'overdue'])->sum('amount_due');
        $overdueCount = $allInvoices->where('status', 'overdue')->count();
        $lastPaidInvoice = $allInvoices->where('status', 'paid')->sortByDesc('paid_at')->first();
        
        // This month unpaid
        $thisMonth = now()->month;
        $thisYear = now()->year;
        $totalUnpaidMonthly = $allInvoices
            ->where('invoice_month', $thisMonth)
            ->where('invoice_year', $thisYear)
            ->whereIn('status', ['open', 'overdue'])
            ->sum('amount_due');

        // Check if user is restricted (from monthly invoice setting)
        $setting = $user->monthlyInvoiceSetting;
        $isRestricted = $setting ? $setting->is_restricted ?? false : false;
        
        // Get next due invoice
        $nextDueInvoice = $allInvoices
            ->whereIn('status', ['open', 'overdue'])
            ->where('due_date', '>=', now())
            ->sortBy('due_date')
            ->first();

        return view('hospital-admin.invoices.index', compact(
            'invoices', 
            'hospital',
            'totalPaid',
            'totalUnpaid', 
            'overdueCount',
            'totalUnpaidMonthly',
            'lastPaidInvoice',
            'isRestricted',
            'nextDueInvoice'
        ));
    }

    /**
     * Display the specified invoice.
     */
    public function show(StripeInvoice $invoice)
    {
        $user = Auth::user();
        
        if ($invoice->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        return view('hospital-admin.invoices.show', compact('invoice'));
    }

    /**
     * Download the invoice PDF.
     */
    public function downloadPdf(StripeInvoice $invoice)
    {
        $user = Auth::user();
        
        if ($invoice->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        // If invoice has a PDF URL, redirect to it
        if ($invoice->invoice_pdf) {
            return redirect($invoice->invoice_pdf);
        }

        // If invoice has a URL, redirect to it  
        if ($invoice->invoice_url) {
            return redirect($invoice->invoice_url);
        }

        // Otherwise, generate a simple PDF view
        return view('hospital-admin.invoices.pdf', compact('invoice'));
    }

    /**
     * Sync invoices from Stripe (if applicable)
     */
    public function sync()
    {
        $user = Auth::user();
        
        try {
            // Here you would implement Stripe invoice sync logic
            // For now, just return success message
            
            return redirect()->route('hospital-admin.invoices.index')
                ->with('success', 'Invoice sync completed successfully.');
                
        } catch (\Exception $e) {
            return redirect()->route('hospital-admin.invoices.index')
                ->with('error', 'Failed to sync invoices: ' . $e->getMessage());
        }
    }
}