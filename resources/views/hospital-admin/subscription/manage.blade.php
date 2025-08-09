@extends('layouts.app')

@section('page-title', 'Subscription Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Subscription Management</h1>
                    <p class="text-muted">Manage your hospital's subscription and billing</p>
                </div>
                <div>
                    @if($setting && $setting->isActive())
                        <a href="{{ route('hospital-admin.subscription.customer-portal') }}" class="btn btn-outline-primary me-2">
                            <i class="fas fa-cog me-1"></i>Billing Portal
                        </a>
                    @endif
                    <a href="{{ route('hospital-admin.subscription.pricing') }}" class="btn btn-primary">
                        <i class="fas fa-credit-card me-1"></i>View Plans
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

            <!-- Current Subscription Status -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Current Subscription</h5>
                        </div>
                        <div class="card-body">
                            @if($setting && $setting->isActive())
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Plan Details</h6>
                                        <p><strong>Plan:</strong> {{ $setting->subscriptionPlan->name ?? 'Custom Plan' }}</p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-{{ $status === 'active' ? 'success' : 'warning' }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </p>
                                        <p><strong>Billing Period:</strong> 
                                            {{ $setting->subscription_period_months == 12 ? 'Yearly' : 'Monthly' }}
                                        </p>
                                        @if($user->hospital)
                                            <p><strong>Doctors Covered:</strong> {{ $user->hospital->doctors()->count() }}</p>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Billing Information</h6>
                                        <p><strong>Monthly Price:</strong> ${{ number_format($setting->monthly_price, 2) }}</p>
                                        <p><strong>Yearly Price:</strong> ${{ number_format($setting->yearly_price, 2) }}</p>
                                        <p><strong>Next Billing:</strong> 
                                            {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->format('M d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                
                                @if($subscription)
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Subscription ID:</strong> {{ $subscription->stripe_id }}
                                        </div>
                                        <div>
                                            <form method="POST" action="{{ route('hospital-admin.subscription.cancel') }}" 
                                                  onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-times me-1"></i>Cancel Subscription
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3">No Active Subscription</h5>
                                    <p class="text-muted">You don't have an active subscription. Choose a plan to get started.</p>
                                    <a href="{{ route('hospital-admin.subscription.pricing') }}" class="btn btn-primary">
                                        <i class="fas fa-credit-card me-1"></i>Choose a Plan
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Usage Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Monthly Cost</span>
                                <span class="fw-bold text-primary">${{ number_format($monthlyCost, 2) }}</span>
                            </div>
                            @if($costLimit > 0)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Cost Limit</span>
                                    <span class="fw-bold">${{ number_format($costLimit, 2) }}</span>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Usage</span>
                                        <span>{{ number_format($costUsagePercentage, 1) }}%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar {{ $costUsagePercentage > 90 ? 'bg-danger' : ($costUsagePercentage > 75 ? 'bg-warning' : 'bg-success') }}" 
                                             style="width: {{ min(100, $costUsagePercentage) }}%"></div>
                                    </div>
                                </div>
                            @endif
                            @if($user->hospital)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Active Doctors</span>
                                    <span class="fw-bold">{{ $user->hospital->doctors()->whereHas('doctor', function($q) { $q->where('is_active', true); })->count() }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total Doctors</span>
                                    <span class="fw-bold">{{ $user->hospital->doctors()->count() }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            @if($invoices->count() > 0)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Invoices</h5>
                        <a href="{{ route('hospital-admin.invoices.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-invoice me-1"></i>View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Period</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Next Billing</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr class="{{ $invoice->status === 'overdue' ? 'table-danger' : ($invoice->status === 'open' ? 'table-warning' : '') }}">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold">#{{ $invoice->stripe_invoice_id ?? $invoice->id }}</span>
                                                    <small class="text-muted">{{ $invoice->created_at->format('M d, Y') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($invoice->invoice_month && $invoice->invoice_year)
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ date('M Y', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }}</span>
                                                        <small class="text-muted">{{ date('M d', mktime(0, 0, 0, $invoice->invoice_month, 1, $invoice->invoice_year)) }} - {{ date('M d', mktime(0, 0, 0, $invoice->invoice_month + 1, 0, $invoice->invoice_year)) }}</small>
                                                    </div>
                                                @else
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ $invoice->created_at->format('M Y') }}</span>
                                                        <small class="text-muted">Billing period</small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $invoice->invoice_type === 'subscription' ? 'primary' : ($invoice->invoice_type === 'usage' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($invoice->invoice_type ?? 'subscription') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold">${{ number_format($invoice->amount_due, 2) }}</span>
                                                    @if($invoice->amount_paid > 0 && $invoice->amount_paid != $invoice->amount_due)
                                                        <small class="text-success">Paid: ${{ number_format($invoice->amount_paid, 2) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($invoice->due_date)
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ $invoice->due_date->format('M d, Y') }}</span>
                                                        @if($invoice->status !== 'paid')
                                                            @php
                                                                $daysUntilDue = now()->diffInDays($invoice->due_date, false);
                                                            @endphp
                                                            @if($daysUntilDue < 0)
                                                                <small class="text-danger">{{ abs($daysUntilDue) }} days overdue</small>
                                                            @elseif($daysUntilDue <= 3)
                                                                <small class="text-warning">Due in {{ $daysUntilDue }} days</small>
                                                            @else
                                                                <small class="text-muted">Due in {{ $daysUntilDue }} days</small>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    @php
                                                        // Calculate due date based on invoice creation + 30 days (standard billing cycle)
                                                        $calculatedDueDate = $invoice->created_at->copy()->addDays(30);
                                                        $isPastDue = $calculatedDueDate->isPast() && $invoice->status !== 'paid';
                                                    @endphp
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ $calculatedDueDate->format('M d, Y') }}</span>
                                                        <small class="text-muted {{ $isPastDue ? 'text-danger' : '' }}">
                                                            {{ $isPastDue ? 'Estimated (Past Due)' : 'Estimated' }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($invoice->status === 'paid')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Paid
                                                        @if($invoice->paid_at)
                                                            <br><small>{{ $invoice->paid_at->format('M d') }}</small>
                                                        @endif
                                                    </span>
                                                @elseif($invoice->status === 'open')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                @elseif($invoice->status === 'overdue')
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($setting && $setting->isActive())
                                                    @php
                                                        // Calculate next billing date based on subscription settings
                                                        $nextBilling = $setting->subscription_ends_at ?? $invoice->created_at->copy()->addMonth();
                                                        $daysUntilNext = now()->diffInDays($nextBilling, false);
                                                    @endphp
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-primary">{{ $nextBilling->format('M d, Y') }}</span>
                                                        @if($daysUntilNext >= 0)
                                                            <small class="text-muted">In {{ $daysUntilNext }} days</small>
                                                        @else
                                                            <small class="text-warning">{{ abs($daysUntilNext) }} days ago</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">No active subscription</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="{{ route('hospital-admin.invoices.show', $invoice) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                    @if($invoice->status !== 'paid' && $invoice->hosted_invoice_url)
                                                        <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-credit-card me-1"></i>Pay Now
                                                        </a>
                                                    @endif
                                                    @if($invoice->invoice_pdf || $invoice->invoice_url)
                                                        <a href="{{ route('hospital-admin.invoices.pdf', $invoice) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-download me-1"></i>PDF
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Unpaid Invoices Warning -->
            @if($unpaidInvoices->count() > 0)
                <div class="alert alert-warning mt-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Outstanding Invoices
                    </h6>
                    <p class="mb-2">You have {{ $unpaidInvoices->count() }} unpaid invoice(s) totaling ${{ number_format($totalUnpaid, 2) }}.</p>
                    <a href="{{ route('hospital-admin.invoices.index') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-file-invoice me-1"></i>View Unpaid Invoices
                    </a>
                </div>
            @endif

            <!-- Cost Warning -->
            @if($costWarning)
                <div class="alert alert-danger mt-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cost Alert
                    </h6>
                    <p class="mb-0">{{ $costWarning }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection