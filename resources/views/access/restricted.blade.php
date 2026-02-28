@extends('master')

@section('title', 'Access Restricted')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        Access Restricted
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-ban fa-4x text-warning mb-3"></i>
                        <h5>Your account access has been temporarily restricted</h5>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i>
                        {{ $restrictionMessage }}
                    </div>

                    @if($unpaidInvoices->count() > 0)
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb"></i> How to Restore Access:</h6>
                            <ol class="mb-0">
                                <li><strong>Pay Outstanding Invoices:</strong> Click "Pay Now" on any unpaid invoice below</li>
                                <li><strong>Update Payment Method:</strong> Visit "Manage Subscription" to update your payment details</li>
                                <li><strong>Automatic Restoration:</strong> Your access will be restored within minutes after payment</li>
                            </ol>
                        </div>
                    @endif

                    @if(auth()->user()->monthlyInvoiceSetting && auth()->user()->monthlyInvoiceSetting->restricted_pages)
                        <div class="alert alert-secondary">
                            <h6><i class="fas fa-ban"></i> Restricted Pages:</h6>
                            <p class="mb-0">The following pages are currently restricted:</p>
                            <ul class="mb-0 mt-2">
                                @foreach(auth()->user()->monthlyInvoiceSetting->restricted_pages as $page)
                                    @php
                                        $availablePages = \App\Models\MonthlyInvoiceSetting::getAvailablePages();
                                    @endphp
                                    <li>{{ $availablePages[$page] ?? ucfirst(str_replace(['-', '_'], ' ', $page)) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($unpaidInvoices->count() > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Outstanding Invoices</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Invoice</th>
                                                <th>Period</th>
                                                <th>Amount</th>
                                                <th>Due Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($unpaidInvoices as $invoice)
                                                <tr>
                                                    <td>
                                                        <span class="{{ $invoice->getTypeBadgeClass() }}">
                                                            {{ $invoice->getHumanType() }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($invoice->isMonthlyInvoice())
                                                            {{ $invoice->getFormattedPeriod() }}
                                                        @else
                                                            {{ $invoice->created_at->format('M Y') }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong class="text-danger">
                                                            {{ $invoice->getFormattedAmountDue() }}
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        {{ $invoice->due_date->format('M d, Y') }}
                                                        @if($invoice->due_date->isPast())
                                                            <br>
                                                            <small class="text-danger">
                                                                ({{ $invoice->due_date->diffForHumans() }})
                                                            </small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('invoices.show', $invoice) }}" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-credit-card"></i> Pay Now
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h5 class="text-danger mb-0">
                                                    ${{ number_format($totalUnpaidAmount, 2) }}
                                                </h5>
                                                <small class="text-muted">Total Outstanding</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="{{ route('invoices.index') }}" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-credit-card"></i>
                                            Pay All Outstanding Invoices
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-invoice fa-2x text-primary mb-3"></i>
                                    <h6>View All Invoices</h6>
                                    <p class="text-muted small">See your complete invoice history and payment status.</p>
                                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-list"></i> View Invoices
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-cog fa-2x text-info mb-3"></i>
                                    <h6>Manage Subscription</h6>
                                    <p class="text-muted small">Update payment methods and manage your subscription.</p>
                                    <a href="{{ route('subscription.manage') }}" class="btn btn-outline-info">
                                        <i class="fas fa-credit-card"></i> Manage Subscription
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-2x text-success mb-3"></i>
                                    <h6>Contact Support</h6>
                                    <p class="text-muted small">Need help? Contact our support team for assistance.</p>
                                    <a href="{{ route('contact') }}" class="btn btn-outline-success">
                                        <i class="fas fa-envelope"></i> Contact Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <p class="text-muted">
                            <i class="fas fa-question-circle"></i>
                            Need help? <a href="{{ route('contact') }}">Contact our support team</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-refresh to check payment status -->
<script>
// Check payment status every 30 seconds
setInterval(function() {
    fetch('{{ route("access.check-status") }}')
        .then(response => response.json())
        .then(data => {
            if (!data.restricted) {
                // User is no longer restricted, redirect to dashboard
                window.location.href = '{{ route("dashboard") }}';
            }
        })
        .catch(error => {
            console.log('Status check failed:', error);
        });
}, 30000);
</script>
@endsection