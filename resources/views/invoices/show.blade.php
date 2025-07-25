@extends('master')

@section('title', 'Invoice Details')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .subscription-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .subscription-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }

    /* Page Header */
    .page-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .page-header h1 {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    /* Legacy class mapping for backward compatibility */
    .invoice-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .invoice-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-file-invoice me-2"></i>Invoice #{{ $invoice->id }}</h1>
                            <p class="text-muted mb-0">{{ $invoice->stripe_invoice_id }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ $invoice->getHumanStatus() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="subscription-card">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle me-2"></i>Invoice Information</h5>
                            <div class="mt-3">
                                <p><strong>Created:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                                @if($invoice->due_date)
                                    <span class="opacity-75">
                                        <i class="fas fa-clock me-1"></i>
                                        Due: {{ $invoice->due_date->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="h2 mb-1">{{ $invoice->getFormattedAmountDue() }}</div>
                            @if($invoice->amount_paid > 0)
                                <div class="text-success">
                                    <small>Paid: {{ $invoice->getFormattedAmountPaid() }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="{{ route('invoices.index') }}" class="btn-custom-danger" style="background: #DE6262">
                        <i class="fas fa-arrow-left"></i> Back to Invoices
                    </a>
                    @if(!$invoice->isPaid())
                        <a href="{{ route('invoices.pay', $invoice) }}" class="btn-custom-success">
                            <i class="fas fa-credit-card"></i> Pay Now
                        </a>
                    @endif
                    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-custom-primary">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    <form method="POST" action="{{ route('invoices.sync', $invoice) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-custom-info">
                            <i class="fas fa-sync"></i> Sync Status
                        </button>
                    </form>
                </div>
                <br>

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
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h6>Invoice Information:</h6>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->id }}</p>
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
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6>Description:</h6>
                                <p>{{ $invoice->description }}</p>
                            </div>

                            <!-- Line Items -->
                            @if($invoice->line_items && count($invoice->line_items) > 0)
                                <div class="table-responsive">
                                    <table class="table table-custom">
                                        <thead>
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
                                        <tfoot>
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
                            @elseif($invoice->isOverdue())
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Overdue!</strong>
                                    This invoice was due on {{ $invoice->due_date->format('M d, Y') }} 
                                    ({{ $invoice->due_date->diffForHumans() }}).
                                    Please pay immediately to avoid service interruption.
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

                    <!-- Quick Actions -->
                    <div class="invoice-card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            @if(!$invoice->isPaid())
                                <a href="{{ route('invoices.pay', $invoice) }}" class="btn-custom-success w-100 mb-2 justify-content-center">
                                    <i class="fas fa-credit-card"></i> Pay This Invoice
                                </a>
                            @endif
                            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-custom-primary w-100 mb-2 justify-content-center">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                            @php
                                $invoiceUrl = $invoice->invoice_url;
                                if (is_array($invoiceUrl)) {
                                    $invoiceUrl = isset($invoiceUrl[0]) ? $invoiceUrl[0] : null;
                                }
                            @endphp
                            @if($invoiceUrl && is_string($invoiceUrl) && filter_var($invoiceUrl, FILTER_VALIDATE_URL))
                                <a href="{{ $invoiceUrl }}" target="_blank" class="btn-custom-info w-100 mb-2 justify-content-center">
                                    <i class="fas fa-external-link-alt"></i> View on Stripe
                                </a>
                            @endif
                            <form method="POST" action="{{ route('invoices.sync', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn-custom-secondary w-100 justify-content-center">
                                    <i class="fas fa-sync"></i> Refresh Status
                                </button>
                            </form>
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
                                    @if($key !== 'stripe_data')
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                            <span>
                                                @if(is_array($value))
                                                    {{ implode(', ', $value) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                                
                                @if(isset($invoice->metadata['stripe_data']) && is_array($invoice->metadata['stripe_data']))
                                    @php
                                        $stripeData = $invoice->metadata['stripe_data'];
                                    @endphp
                                    
                                    @if(isset($stripeData['customer_name']))
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Customer Name:</span>
                                            <span>{{ $stripeData['customer_name'] }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($stripeData['customer_email']))
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Customer Email:</span>
                                            <span>{{ $stripeData['customer_email'] }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($stripeData['billing_reason']))
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Billing Reason:</span>
                                            <span>{{ ucwords(str_replace('_', ' ', $stripeData['billing_reason'])) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($stripeData['collection_method']))
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Collection Method:</span>
                                            <span>{{ ucwords(str_replace('_', ' ', $stripeData['collection_method'])) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($stripeData['number']))
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Invoice Number:</span>
                                            <span>{{ $stripeData['number'] }}</span>
                                        </div>
                                    @endif
                                @endif
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