<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeInvoice;
use App\Models\User;
use App\Services\StripeInvoiceService;
use App\Jobs\CreateMonthlyInvoices;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AdminInvoiceController extends Controller
{
    public function __construct(
        private StripeInvoiceService $invoiceService
    ) {
        $this->middleware('admin');
    }

    /**
     * Display a listing of all invoices
     */
    public function index(Request $request)
    {
        $query = StripeInvoice::with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
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

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get users for filter dropdown
        $users = User::where('is_admin', false)->orderBy('name')->get();

        // Calculate summary statistics
        $totalUnpaid = StripeInvoice::unpaid()->sum('amount_due') - StripeInvoice::unpaid()->sum('amount_paid');
        $totalPaid = StripeInvoice::paid()->sum('amount_paid');
        $overdueCount = StripeInvoice::overdue()->count();
        $totalInvoices = StripeInvoice::count();

        return view('admin.invoices.index', compact(
            'invoices',
            'users',
            'totalUnpaid',
            'totalPaid',
            'overdueCount',
            'totalInvoices'
        ));
    }

    /**
     * Display the specified invoice
     */
    public function show(StripeInvoice $invoice)
    {
        $invoice->load('user');
        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Show the form for creating a new manual invoice
     */
    public function create()
    {
        $users = User::where('is_admin', false)->orderBy('name')->get();
        return view('admin.invoices.create', compact('users'));
    }

    /**
     * Store a newly created manual invoice
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            
            $items = collect($request->items)->map(function ($item) {
                return [
                    'description' => $item['description'],
                    'amount' => (float) $item['amount'],
                    'quantity' => $item['quantity'] ?? 1,
                ];
            })->toArray();

            $invoice = $this->invoiceService->createManualInvoice(
                $user,
                $items,
                $request->description
            );

            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Manual invoice created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Mark an invoice as paid manually
     */
    public function markAsPaid(StripeInvoice $invoice)
    {
        try {
            $this->invoiceService->markInvoiceAsPaid($invoice);
            
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Invoice marked as paid successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', 'Failed to mark invoice as paid: ' . $e->getMessage());
        }
    }

    /**
     * Void an invoice
     */
    public function void(StripeInvoice $invoice)
    {
        try {
            $this->invoiceService->voidInvoice($invoice);
            
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Invoice voided successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', 'Failed to void invoice: ' . $e->getMessage());
        }
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(StripeInvoice $invoice)
    {
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        
        $filename = "invoice-{$invoice->id}-" . now()->format('Y-m-d') . ".pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Generate monthly invoices for all users
     */
    public function generateMonthlyInvoices(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        try {
            $date = Carbon::createFromFormat('Y-m', $request->month);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();

            CreateMonthlyInvoices::dispatch($startDate, $endDate);

            return redirect()->route('admin.invoices.index')
                ->with('success', 'Monthly invoice generation job has been queued.');
        } catch (\Exception $e) {
            return redirect()->route('admin.invoices.index')
                ->with('error', 'Failed to queue invoice generation: ' . $e->getMessage());
        }
    }

    /**
     * Export invoices to CSV
     */
    public function export(Request $request)
    {
        $query = StripeInvoice::with('user');

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
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
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        $filename = 'invoices-export-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($invoices) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Invoice ID',
                'Stripe Invoice ID',
                'User Name',
                'User Email',
                'Amount Due',
                'Amount Paid',
                'Status',
                'Due Date',
                'Paid At',
                'Created At',
                'Description'
            ]);

            // CSV data
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->id,
                    $invoice->stripe_invoice_id,
                    $invoice->user->name,
                    $invoice->user->email,
                    $invoice->amount_due,
                    $invoice->amount_paid,
                    $invoice->status,
                    $invoice->due_date?->format('Y-m-d H:i:s'),
                    $invoice->paid_at?->format('Y-m-d H:i:s'),
                    $invoice->created_at->format('Y-m-d H:i:s'),
                    $invoice->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
