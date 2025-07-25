@extends('layouts.admin')

@section('title', 'Invoice Details')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .invoice-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Invoice #{{ $invoice->id }}</h1>
                    <p class="text-muted mb-0">{{ $invoice->stripe_invoice_id }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Invoices
                    </a>
                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    @if(!$invoice->isPaid() && $invoice->status !== 'void')
                        <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" 
                                    onclick="return confirm('Mark this invoice as paid? This action cannot be undone.')">
                                <i class="fas fa-check"></i> Mark as Paid
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('Void this invoice? This action cannot be undone.')">
                                <i class="fas fa-times"></i> Void
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="row">
                <!-- Invoice Details -->
                <div class="col-lg-8">
                    <div class="invoice-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Bill To:</h6>
                                    <p class="mb-1"><strong>{{ $invoice->user->name }}</strong></p>
                                    <p class="mb-1">{{ $invoice->user->email }}</p>
                                    @if($invoice->user->stripe_customer_id)
                                        <p class="mb-1">
                                            <small class="text-muted">Stripe Customer: {{ $invoice->user->stripe_customer_id }}</small>
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h6>Invoice Information:</h6>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->id }}</p>
                                    <p class="mb-1"><strong>Stripe ID:</strong> {{ $invoice->stripe_invoice_id }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                                    @if($invoice->due_date)
                                        <p class="mb-1"><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
                                    @endif
                                    <p class="mb-1">
                                        <strong>Status:</strong> 
                                        <span class="{{ $invoice->getStatusBadgeClass() }}">
                                            {{ $invoice->getHumanStatus() }}
                                        </span>
                                    </p>
                                    @if($invoice->paid_at)
                                        <p class="mb-1"><strong>Paid At:</strong> {{ $invoice->paid_at->format('M d, Y g:i A') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6>Description:</h6>
                                <p>{{ $invoice->description }}</p>
                            </div>

                            <!-- Line Items -->
                            @if($invoice->line_items && count($invoice->line_items) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-center">Quantity</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoice->line_items as $item)
                                                <tr>
                                                    <td>{{ $item['description'] }}</td>
                                                    <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                                                    <td class="text-end">${{ number_format(($item['unit_amount'] ?? $item['amount']) / 100, 2) }}</td>
                                                    <td class="text-end">${{ number_format($item['amount'] / 100, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th class="text-end">{{ $invoice->getFormattedAmountDue() }}</th>
                                            </tr>
                                            @if($invoice->amount_paid > 0)
                                                <tr>
                                                    <th colspan="3" class="text-end">Paid:</th>
                                                    <th class="text-end text-success">-{{ $invoice->getFormattedAmountPaid() }}</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="3" class="text-end">Outstanding:</th>
                                                    <th class="text-end">{{ $invoice->getFormattedOutstandingAmount() }}</th>
                                                </tr>
                                            @endif
                                        </tfoot>
                                    </table>
                                </div>
                            @endif

                            <!-- Payment Information -->
                            @if($invoice->isPaid())
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Payment Received!</strong>
                                    This invoice was paid on {{ $invoice->paid_at->format('M d, Y \a\t g:i A') }}.
                                </div>
                            @elseif($invoice->status === 'void')
                                <div class="alert alert-secondary">
                                    <i class="fas fa-ban"></i>
                                    <strong>Invoice Voided</strong>
                                    This invoice has been voided and is no longer payable.
                                </div>
                            @elseif($invoice->isOverdue())
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Overdue!</strong>
                                    This invoice was due on {{ $invoice->due_date->format('M d, Y') }} 
                                    ({{ $invoice->due_date->diffForHumans() }}).
                                </div>
                            @elseif($invoice->isDueSoon())
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock"></i>
                                    <strong>Due Soon!</strong>
                                    This invoice is due {{ $invoice->due_date->diffForHumans() }}.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Payment Summary -->
                    <div class="invoice-card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Payment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Amount Due:</span>
                                <strong>{{ $invoice->getFormattedAmountDue() }}</strong>
                            </div>
                            @if($invoice->amount_paid > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Amount Paid:</span>
                                    <span class="text-success">{{ $invoice->getFormattedAmountPaid() }}</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Outstanding:</strong>
                                    <strong>{{ $invoice->getFormattedOutstandingAmount() }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Admin Actions -->
                    <div class="invoice-card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Admin Actions</h6>
                        </div>
                        <div class="card-body">
                            @if(!$invoice->isPaid() && $invoice->status !== 'void')
                                <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 mb-2" 
                                            onclick="return confirm('Mark this invoice as paid? This action cannot be undone.')">
                                        <i class="fas fa-check"></i> Mark as Paid
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100 mb-2" 
                                            onclick="return confirm('Void this invoice? This action cannot be undone.')">
                                        <i class="fas fa-times"></i> Void Invoice
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                            @php
                                $invoiceUrl = $invoice->invoice_url;
                                if (is_array($invoiceUrl)) {
                                    $invoiceUrl = isset($invoiceUrl[0]) ? $invoiceUrl[0] : null;
                                }
                            @endphp
                            @if($invoiceUrl && is_string($invoiceUrl) && filter_var($invoiceUrl, FILTER_VALIDATE_URL))
                                <a href="{{ $invoiceUrl }}" target="_blank" class="btn btn-outline-info w-100">
                                    <i class="fas fa-external-link-alt"></i> View on Stripe
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Doctor Information -->
                    <div class="invoice-card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Doctor Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Name:</span>
                                <span>{{ $invoice->user->name }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Email:</span>
                                <span>{{ $invoice->user->email }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Current Plan:</span>
                                <span>{{ $invoice->user->current_plan ?? 'None' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Invoices:</span>
                                <span>{{ $invoice->user->stripeInvoices()->count() }}</span>
                            </div>
                            <hr>
                            <a href="{{ route('admin.users.show', $invoice->user) }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user"></i> View Doctor Profile
                            </a>
                        </div>
                    </div>

                    <!-- Invoice Metadata -->
                    @if($invoice->metadata && count($invoice->metadata) > 0)
                        <div class="invoice-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Additional Information</h6>
                            </div>
                            <div class="card-body">
                                @foreach($invoice->metadata as $key => $value)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                        <span>{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection