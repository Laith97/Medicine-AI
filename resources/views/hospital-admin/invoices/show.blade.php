@extends('layouts.app')

@section('page-title', 'Invoice Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hospital-admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hospital-admin.invoices.index') }}">Invoices</a></li>
                            <li class="breadcrumb-item active">Invoice #{{ $invoice->stripe_invoice_id ?? $invoice->id }}</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">Invoice Details</h1>
                    <p class="text-muted">Complete invoice information and payment history</p>
                </div>
                <div>
                    <a href="{{ route('hospital-admin.invoices.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>Back to Invoices
                    </a>
                    @if($invoice->status !== 'paid' && $invoice->hosted_invoice_url)
                        <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" class="btn btn-success">
                            <i class="fas fa-credit-card me-1"></i>Pay Now
                        </a>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <!-- Invoice Overview -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-invoice me-2"></i>
                                Invoice #{{ $invoice->stripe_invoice_id ?? $invoice->id }}
                            </h5>
                            <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }} fs-6">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                        <div class="card-body">
                            <!-- Invoice Summary -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Invoice Information</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">Invoice ID:</td>
                                            <td>#{{ $invoice->stripe_invoice_id ?? $invoice->id }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Type:</td>
                                            <td>
                                                <span class="badge bg-{{ $invoice->invoice_type === 'subscription' ? 'primary' : ($invoice->invoice_type === 'usage' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($invoice->invoice_type ?? 'subscription') }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Created:</td>
                                            <td>{{ $invoice->created_at->format('F d, Y \a\t g:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Due Date:</td>
                                            <td>
                                                @if($invoice->due_date)
                                                    {{ $invoice->due_date->format('F d, Y') }}
                                                    @if($invoice->status !== 'paid')
                                                        @php
                                                            $daysUntilDue = now()->diffInDays($invoice->due_date, false);
                                                        @endphp
                                                        @if($daysUntilDue < 0)
                                                            <br><small class="text-danger">{{ abs($daysUntilDue) }} days overdue</small>
                                                        @elseif($daysUntilDue <= 3)
                                                            <br><small class="text-warning">Due in {{ $daysUntilDue }} days</small>
                                                        @else
                                                            <br><small class="text-muted">Due in {{ $daysUntilDue }} days</small>
                                                        @endif
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($invoice->invoice_month && $invoice->invoice_year)
                                            <tr>
                                                <td class="fw-bold">Billing Period:</td>
                                                <td>{{ date('F Y', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Amount Details</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-bold">Amount Due:</td>
                                            <td class="fs-5 fw-bold text-primary">${{ number_format($invoice->amount_due, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Amount Paid:</td>
                                            <td class="fs-5 fw-bold text-success">${{ number_format($invoice->amount_paid, 2) }}</td>
                                        </tr>
                                        @if($invoice->amount_due != $invoice->amount_paid)
                                            <tr>
                                                <td class="fw-bold">Outstanding:</td>
                                                <td class="fs-5 fw-bold text-danger">${{ number_format($invoice->amount_due - $invoice->amount_paid, 2) }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td class="fw-bold">Currency:</td>
                                            <td>{{ strtoupper($invoice->currency ?? 'USD') }}</td>
                                        </tr>
                                        @if($invoice->paid_at)
                                            <tr>
                                                <td class="fw-bold">Paid On:</td>
                                                <td class="text-success">{{ $invoice->paid_at->format('F d, Y \a\t g:i A') }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <!-- Description -->
                            @if($invoice->description)
                                <div class="mb-4">
                                    <h6 class="text-primary mb-2">Description</h6>
                                    <div class="bg-light p-3 rounded">
                                        {{ $invoice->description }}
                                    </div>
                                </div>
                            @endif

                            <!-- Line Items -->
                            @if($invoice->line_items && count($invoice->line_items) > 0)
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">Invoice Line Items</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($invoice->line_items as $item)
                                                    <tr>
                                                        <td>{{ $item['description'] ?? 'N/A' }}</td>
                                                        <td>{{ $item['quantity'] ?? 1 }}</td>
                                                        <td>${{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                                                        <td>${{ number_format($item['amount'] ?? 0, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions & Quick Info -->
                <div class="col-lg-4">
                    <!-- Status Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Status</h5>
                        </div>
                        <div class="card-body text-center">
                            @if($invoice->status === 'paid')
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                <h4 class="text-success mt-2">Fully Paid</h4>
                                @if($invoice->paid_at)
                                    <p class="text-muted">Paid on {{ $invoice->paid_at->format('M d, Y') }}</p>
                                @endif
                            @elseif($invoice->status === 'overdue')
                                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                                <h4 class="text-danger mt-2">Overdue</h4>
                                <p class="text-muted">Please pay immediately to avoid service interruption</p>
                            @elseif($invoice->status === 'open')
                                <i class="fas fa-clock text-warning" style="font-size: 3rem;"></i>
                                <h4 class="text-warning mt-2">Pending Payment</h4>
                                @if($invoice->due_date)
                                    <p class="text-muted">Due {{ $invoice->due_date->format('M d, Y') }}</p>
                                @endif
                            @else
                                <i class="fas fa-file-invoice text-secondary" style="font-size: 3rem;"></i>
                                <h4 class="text-secondary mt-2">{{ ucfirst($invoice->status) }}</h4>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($invoice->status !== 'paid' && $invoice->hosted_invoice_url)
                                    <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" class="btn btn-success">
                                        <i class="fas fa-credit-card me-1"></i>Pay Online
                                    </a>
                                @endif

                                @if($invoice->invoice_pdf || $invoice->invoice_url)
                                    <a href="{{ route('hospital-admin.invoices.pdf', $invoice) }}" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-download me-1"></i>Download PDF
                                    </a>
                                @endif

                                <a href="{{ route('hospital-admin.invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-1"></i>All Invoices
                                </a>

                                <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-cog me-1"></i>Subscription Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reminders Info -->
                    @if($invoice->reminder_count > 0)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Payment Reminders</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Reminders Sent:</strong> {{ $invoice->reminder_count }}</p>
                                @if($invoice->last_reminder_sent_at)
                                    <p><strong>Last Reminder:</strong> {{ $invoice->last_reminder_sent_at->format('M d, Y') }}</p>
                                @endif
                                @if($invoice->grace_period_ends_at)
                                    <p><strong>Grace Period Ends:</strong> {{ $invoice->grace_period_ends_at->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection