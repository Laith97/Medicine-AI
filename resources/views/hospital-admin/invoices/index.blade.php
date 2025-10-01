@extends('layouts.app')

@section('page-title', 'Invoices')

@section('content')
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">Hospital Invoices</h2>
    <p>View hospital invoices</p>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Hospital Invoices</h1>
                    <p class="text-muted">Manage your hospital's billing and invoices</p>
                </div>
                <div>
                    
                    <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-primary">
                        <i class="fas fa-credit-card me-1"></i>Subscription
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Enhanced Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-exclamation-circle text-danger fa-2x me-2"></i>
                                <h3 class="text-danger mb-0">${{ number_format($totalUnpaid, 2) }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Total Unpaid</p>
                            @if($totalUnpaid > 0)
                                <small class="text-muted">Requires immediate attention</small>
                            @else
                                <small class="text-success">All invoices paid!</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-check-circle text-success fa-2x me-2"></i>
                                <h3 class="text-success mb-0">${{ number_format($totalPaid, 2) }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Total Paid</p>
                            <small class="text-muted">Lifetime payments</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-clock text-warning fa-2x me-2"></i>
                                <h3 class="text-warning mb-0">{{ $overdueCount }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Overdue Invoices</p>
                            @if($overdueCount > 0)
                                <small class="text-danger">Action required</small>
                            @else
                                <small class="text-success">No overdue invoices</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-calendar-alt text-info fa-2x me-2"></i>
                                <h3 class="text-info mb-0">${{ number_format($totalUnpaidMonthly, 2) }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">This Month Unpaid</p>
                            @if($totalUnpaidMonthly > 0)
                                <small class="text-warning">Current month balance</small>
                            @else
                                <small class="text-success">Month fully paid</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Row -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <i class="fas fa-file-invoice text-primary me-2"></i>
                                Total Invoices
                            </h5>
                            <h4 class="text-primary">{{ $invoices->total() ?? $invoices->count() }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line text-info me-2"></i>
                                Average Invoice
                            </h5>
                            <h4 class="text-info">
                                @if($invoices->count() > 0)
                                    ${{ number_format(($totalPaid + $totalUnpaid) / $invoices->count(), 2) }}
                                @else
                                    $0.00
                                @endif
                            </h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-check text-success me-2"></i>
                                Last Payment
                            </h5>
                            <h4 class="text-success">
                                @if($lastPaidInvoice)
                                    {{ $lastPaidInvoice->paid_at ? $lastPaidInvoice->paid_at->format('M d') : 'N/A' }}
                                @else
                                    No payments
                                @endif
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('hospital-admin.invoices.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Type</label>
                                <select name="type" id="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="subscription" {{ request('type') === 'subscription' ? 'selected' : '' }}>Subscription</option>
                                    <option value="usage" {{ request('type') === 'usage' ? 'selected' : '' }}>Usage</option>
                                    <option value="excess" {{ request('type') === 'excess' ? 'selected' : '' }}>Excess Usage</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Apply Filters
                                </button>
                                <a href="{{ route('hospital-admin.invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoice History</h5>
                </div>
                <div class="card-body">
                    @if($invoices && $invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Billing Period</th>
                                        <th>Type & Description</th>
                                        <th>Amount Details</th>
                                        <th>Due Date & Status</th>
                                        <th>Payment Info</th>
                                        <th>Next Billing</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr class="{{ $invoice->status === 'overdue' ? 'table-danger' : ($invoice->status === 'open' ? 'table-warning' : ($invoice->status === 'paid' ? 'table-success' : '')) }}">
                                          
                                            <td>
                                                @if($invoice->invoice_month && $invoice->invoice_year)
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ date('F Y', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }}</span>
                                                        <small class="text-muted">{{ date('M d', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }} - {{ date('M d', mktime(0, 0, 0, $invoice->invoice_month + 1, 0, $invoice->invoice_year)) }}</small>
                                                    </div>
                                                @else
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ $invoice->created_at->format('F Y') }}</span>
                                                        <small class="text-muted">{{ $invoice->created_at->format('M d') }} - {{ $invoice->created_at->copy()->addMonth()->format('M d') }}</small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-{{ $invoice->invoice_type === 'subscription' ? 'primary' : ($invoice->invoice_type === 'usage' ? 'info' : ($invoice->invoice_type === 'excess' ? 'warning' : 'secondary')) }} mb-1">
                                                        {{ ucfirst($invoice->invoice_type ?? 'subscription') }}
                                                    </span>
                                                    @if($invoice->description)
                                                        <small class="text-muted">{{ Str::limit($invoice->description, 50) }}</small>
                                                    @endif
                                                    @if($invoice->line_items && is_array($invoice->line_items))
                                                        <small class="text-info">{{ count($invoice->line_items) }} item(s)</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold fs-6">${{ number_format($invoice->amount_due, 2) }}</span>
                                                    @if($invoice->amount_paid > 0)
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            Paid: ${{ number_format($invoice->amount_paid, 2) }}
                                                        </small>
                                                    @endif
                                                    @if($invoice->amount_due != $invoice->amount_paid && $invoice->amount_paid > 0)
                                                        <small class="text-warning">
                                                            Balance: ${{ number_format($invoice->amount_due - $invoice->amount_paid, 2) }}
                                                        </small>
                                                    @endif
                                                    @if($invoice->currency && $invoice->currency !== 'usd')
                                                        <small class="text-muted">{{ strtoupper($invoice->currency) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    @if($invoice->due_date)
                                                        <span class="fw-bold">{{ $invoice->due_date->format('M d, Y') }}</span>
                                                        @if($invoice->status !== 'paid')
                                                            @php
                                                                $daysUntilDue = now()->diffInDays($invoice->due_date, false);
                                                            @endphp
                                                            @if($daysUntilDue < 0)
                                                                <small class="text-danger">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                                    {{ abs($daysUntilDue) }} days overdue
                                                                </small>
                                                            @elseif($daysUntilDue <= 3)
                                                                <small class="text-warning">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    Due in {{ $daysUntilDue }} days
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Due in {{ $daysUntilDue }} days</small>
                                                            @endif
                                                        @endif
                                                    @else
                                                        @php
                                                            // Calculate estimated due date based on invoice creation + 30 days
                                                            $calculatedDueDate = $invoice->created_at->copy()->addDays(30);
                                                            $isPastDue = $calculatedDueDate->isPast() && $invoice->status !== 'paid';
                                                        @endphp
                                                        <span class="fw-bold">{{ $calculatedDueDate->format('M d, Y') }}</span>
                                                        <small class="text-muted {{ $isPastDue ? 'text-danger' : '' }}">
                                                            {{ $isPastDue ? 'Est. (Past Due)' : 'Estimated' }}
                                                        </small>
                                                    @endif
                                                    
                                                    <!-- Status Badge -->
                                                    <div class="mt-1">
                                                        @if($invoice->status === 'paid')
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Paid
                                                            </span>
                                                        @elseif($invoice->status === 'open')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-clock me-1"></i>Pending
                                                            </span>
                                                        @elseif($invoice->status === 'overdue')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                                            </span>
                                                        @elseif($invoice->status === 'draft')
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-edit me-1"></i>Draft
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    @if($invoice->paid_at)
                                                        <small class="text-success">
                                                            <i class="fas fa-calendar-check me-1"></i>
                                                            Paid: {{ $invoice->paid_at->format('M d, Y') }}
                                                        </small>
                                                    @endif
                                                    @if($invoice->reminder_count > 0)
                                                        <small class="text-info">
                                                            <i class="fas fa-bell me-1"></i>
                                                            {{ $invoice->reminder_count }} reminder(s) sent
                                                        </small>
                                                    @endif
                                                    @if($invoice->last_reminder_sent_at)
                                                        <small class="text-muted">
                                                            Last: {{ $invoice->last_reminder_sent_at->format('M d') }}
                                                        </small>
                                                    @endif
                                                    @if($invoice->grace_period_ends_at && $invoice->status !== 'paid')
                                                        <small class="text-warning">
                                                            <i class="fas fa-hourglass-half me-1"></i>
                                                            Grace ends: {{ $invoice->grace_period_ends_at->format('M d') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    // Get user's subscription setting for next billing calculation
                                                    $userSetting = $invoice->user->monthlyInvoiceSetting ?? null;
                                                @endphp
                                                @if($userSetting && $userSetting->isActive())
                                                    @php
                                                        // Calculate next billing date based on subscription settings
                                                        $nextBilling = $userSetting->subscription_ends_at ?? $invoice->created_at->copy()->addMonth();
                                                        $daysUntilNext = now()->diffInDays($nextBilling, false);
                                                    @endphp
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-primary">{{ $nextBilling->format('M d, Y') }}</span>
                                                        @if($daysUntilNext >= 0)
                                                            <small class="text-muted">In {{ $daysUntilNext }} days</small>
                                                        @else
                                                            <small class="text-warning">{{ abs($daysUntilNext) }} days overdue</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="text-center">
                                                        <small class="text-muted">No active subscription</small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="{{ route('hospital-admin.invoices.show', $invoice) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
                                                    @if($invoice->invoice_pdf || $invoice->invoice_url)
                                                        <a href="{{ route('hospital-admin.invoices.pdf', $invoice) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-download me-1"></i>Download PDF
                                                        </a>
                                                    @endif
                                                    @if($invoice->status !== 'paid' && $invoice->hosted_invoice_url)
                                                        <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-credit-card me-1"></i>Pay Now
                                                        </a>
                                                    @elseif($invoice->status !== 'paid' && $invoice->invoice_url)
                                                        <a href="{{ $invoice->invoice_url }}" target="_blank" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-external-link-alt me-1"></i>Pay Online
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($invoices->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $invoices->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Invoices Found</h5>
                            <p class="text-muted">No invoices match your current filters.</p>
                            @if(request()->hasAny(['status', 'type', 'date_from', 'date_to']))
                                <a href="{{ route('hospital-admin.invoices.index') }}" class="btn btn-primary">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            @else
                                <a href="{{ route('hospital-admin.subscription.pricing') }}" class="btn btn-primary">
                                    <i class="fas fa-credit-card me-1"></i>Start Subscription
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Account Restrictions Warning -->
            @if($isRestricted)
                <div class="alert alert-danger mt-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Account Restricted
                    </h6>
                    <p class="mb-2">Your account has been restricted due to unpaid invoices. Please pay outstanding invoices to restore full access.</p>
                    @if($nextDueInvoice)
                        <a href="{{ route('hospital-admin.invoices.show', $nextDueInvoice) }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-credit-card me-1"></i>Pay Next Due Invoice
                        </a>
                    @endif
                </div>
            @endif

            <!-- Next Due Invoice -->
            @if($nextDueInvoice && !$isRestricted)
                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>Upcoming Payment
                    </h6>
                    <p class="mb-2">
                        Your next invoice (#{{ $nextDueInvoice->id }}) for ${{ number_format($nextDueInvoice->amount_due, 2) }} 
                        is due on {{ $nextDueInvoice->due_date->format('M d, Y') }}.
                    </p>
                    <a href="{{ route('hospital-admin.invoices.show', $nextDueInvoice) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye me-1"></i>View Invoice
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection