@extends('master')

@section('title', 'My Invoices')

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
    
    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        height: 100%;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
    }

    /* Enhanced Table Styling */
    .table-custom {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .table-custom thead th {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table-custom tbody td {
        padding: 1rem;
        border-color: #f1f3f4;
        vertical-align: middle;
    }
    
    .table-custom tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
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

    /* Enhanced stat numbers */
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
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
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">Invoices</h2>
    <p>View your invoices</p>
</div>
<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-file-invoice-dollar me-2"></i>My Invoices</h1>
                            <p class="text-muted mb-0">View and manage your billing history</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn-custom-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="fas fa-filter"></i> Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <div class="stats-card" style="padding: 1rem;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Total Invoiced</div>
                                    <div style="font-size: 1.25rem; font-weight: 600; color: #2c3e50;">${{ number_format($totalUnpaid + $totalPaid, 2) }}</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-invoice-dollar" style="font-size: 1.2rem; color: #6c757d;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <div class="stats-card" style="padding: 1rem;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Outstanding</div>
                                    <div style="font-size: 1.25rem; font-weight: 600; color: #DE6262;">${{ number_format($totalUnpaid, 2) }}</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem; color: #DE6262;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <div class="stats-card" style="padding: 1rem;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Paid</div>
                                    <div style="font-size: 1.25rem; font-weight: 600; color: #28a745;">${{ number_format($totalPaid, 2) }}</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle" style="font-size: 1.2rem; color: #28a745;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <div class="stats-card" style="padding: 1rem;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Billing Rate</div>
                                    <div style="font-size: 1.25rem; font-weight: 600; color: #2c3e50;">
                                        @if(auth()->user()->monthlyInvoiceSetting)
                                            {{ auth()->user()->monthlyInvoiceSetting->getAmountWithPeriod() }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-alt" style="font-size: 1.2rem; color: #6c757d;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <div class="stats-card" style="padding: 1rem;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Next Due</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #2c3e50;">
                                        @if($nextDueInvoice)
                                            {{ $nextDueInvoice->due_date->format('M d, Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock" style="font-size: 1.2rem; color: #6c757d;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                        <div class="stats-card" style="padding: 1rem;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Subscription Expires</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #DE6262;">
                                        @if(auth()->user()->monthlyInvoiceSetting && auth()->user()->monthlyInvoiceSetting->subscription_ends_at)
                                            {{ auth()->user()->monthlyInvoiceSetting->subscription_ends_at->format('M d, Y') }}
                                        @elseif(auth()->user()->monthlyInvoiceSetting && auth()->user()->monthlyInvoiceSetting->isUnlimitedSubscription())
                                            <span style="color: #28a745;">
                                                <i class="fas fa-infinity me-1"></i>Never
                                            </span>
                                        @elseif(auth()->user()->subscription && auth()->user()->subscription->current_period_end)
                                            {{ auth()->user()->subscription->current_period_end->format('M d, Y') }}
                                        @else
                                            Not Started
                                        @endif
                                    </div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-times" style="font-size: 1.2rem; color: #DE6262;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @if($isRestricted)
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-ban"></i>
                    <strong>Account Restricted!</strong> Your access has been limited due to unpaid invoices. 
                    <a href="{{ route('access.restricted') }}" class="alert-link">Click here for details</a>
                </div>
            @elseif($overdueCount > 0)
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Attention!</strong> You have {{ $overdueCount }} overdue invoice(s). Please pay them immediately to avoid service interruption.
                </div>
            @endif

            @if($totalUnpaidMonthly > 0)
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Outstanding Monthly Invoices:</strong> You have ${{ number_format($totalUnpaidMonthly, 2) }} in unpaid monthly service fees.
                    @if($isRestricted)
                        <br><small><strong>Note:</strong> Your account is currently restricted due to unpaid invoices. Pay outstanding invoices to restore access.</small>
                    @endif
                </div>
            @endif

            @if($isRestricted && $totalUnpaidMonthly == 0)
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i>
                    <strong>Account Status:</strong> Your account is currently restricted. If you believe this is an error, please contact support.
                </div>
            @endif

                <!-- Filters -->
                <div class="collapse mb-4" id="filterCollapse">
                    <div class="subscription-card">
                        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Invoices</h5>
                        <form method="GET" action="{{ route('invoices.index') }}">
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="type" class="form-label">Type</label>
                                    <select name="type" id="type" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="subscription" {{ request('type') === 'subscription' ? 'selected' : '' }}>Subscription</option>
                                        <option value="manual" {{ request('type') === 'manual' ? 'selected' : '' }}>Manual</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
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
                                <div class="col-md-2 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn-custom-primary">
                                        <i class="fas fa-search"></i>Apply Filters
                                    </button>
                                    <a href="{{ route('invoices.index') }}" class="btn-custom-secondary">
                                        <i class="fas fa-times"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="subscription-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-table me-2"></i>Invoice History</h4>
                        @if($invoices->count() > 0)
                            <small class="text-muted">{{ $invoices->count() }} invoice(s) found</small>
                        @endif
                    </div>
                    
                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Service Period</th>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                        <th>Payment Date</th>
                                        <th>Subscription Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr class="{{ $invoice->isOverdue() ? 'table-danger' : '' }}">
                                            <td>
                                                <strong style="color: #DE6262;">#{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.75rem;">{{ $invoice->created_at->format('M d, Y') }}</small>
                                            </td>
                                            <td>
                                                @if($invoice->line_items && count($invoice->line_items) > 0 && isset($invoice->line_items[0]['period_start']) && isset($invoice->line_items[0]['period_end']))
                                                    @php
                                                        $periodStart = \Carbon\Carbon::parse($invoice->line_items[0]['period_start']);
                                                        $periodEnd = \Carbon\Carbon::parse($invoice->line_items[0]['period_end']);
                                                    @endphp
                                                    <strong class="text-primary">{{ $periodStart->format('M j') }} - {{ $periodEnd->format('M j, Y') }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $periodStart->diffInDays($periodEnd) }} days of service</small>
                                                @else
                                                    <strong class="text-primary">{{ $invoice->created_at->format('M Y') }}</strong>
                                                    <br>
                                                    <small class="text-muted">One-time payment</small>
                                                @endif
                                                <br>
                                                <span class="badge bg-light text-dark" style="font-size: 0.7rem;">
                                                    {{ $invoice->getHumanType() }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong style="color: #2c3e50; font-size: 1.1rem;">{{ $invoice->getFormattedAmountDue() }}</strong>
                                                @if($invoice->amount_paid > 0)
                                                    <br>
                                                    <small class="text-success" style="font-size: 0.75rem;">Paid: {{ $invoice->getFormattedAmountPaid() }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="{{ $invoice->getStatusBadgeClass() }}" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                    {{ $invoice->getHumanStatus() }}
                                                </span>
                                                @if($invoice->isOverdue())
                                                    <br>
                                                    <small class="text-danger" style="font-size: 0.7rem;">
                                                        <i class="fas fa-exclamation-triangle"></i> Overdue
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($invoice->paid_at)
                                                    <strong class="text-success">{{ $invoice->paid_at->format('M j, Y') }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $invoice->paid_at->format('g:i A') }}</small>
                                                @elseif($invoice->due_date)
                                                    <strong class="text-warning">Due: {{ $invoice->due_date->format('M j, Y') }}</strong>
                                                    @if($invoice->isOverdue())
                                                        <br>
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> Overdue
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-warning">
                                                        <i class="fas fa-clock me-1"></i>Payment Required
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(auth()->user()->monthlyInvoiceSetting)
                                                    @php $setting = auth()->user()->monthlyInvoiceSetting; @endphp
                                                    @if($setting->subscription_ends_at && !$setting->isSubscriptionExpired())
                                                        <strong class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Active
                                                        </strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Until {{ $setting->subscription_ends_at->format('M j, Y') }}
                                                        </small>
                                                    @elseif($setting->isSubscriptionExpired())
                                                        <strong class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Expired
                                                        </strong>
                                                        <br>
                                                        <small class="text-danger">
                                                            {{ $setting->subscription_ends_at->format('M j, Y') }}
                                                        </small>
                                                    @else
                                                        <strong class="text-warning">
                                                            <i class="fas fa-clock me-1"></i>Starting
                                                        </strong>
                                                        <br>
                                                        <small class="text-muted">Setting up...</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">
                                                        <i class="fas fa-question-circle me-1"></i>No Subscription
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn-sm-custom-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!$invoice->isPaid())
                                                        <a href="{{ route('invoices.pay', $invoice) }}" class="btn-sm-custom-success" title="Pay Invoice">
                                                            <i class="fas fa-credit-card"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-sm-custom-secondary" title="Download PDF">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $invoices->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                            <h5>No invoices found</h5>
                            <p class="text-muted">
                                @if(request()->hasAny(['type', 'status', 'date_from', 'date_to']))
                                    No invoices match your current filters. <a href="{{ route('invoices.index') }}">Clear filters</a> to see all invoices.
                                @else
                                    You don't have any invoices yet. Your first invoice will appear here when generated.
                                @endif
                            </p>
                            @if($isRestricted)
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Account Restricted:</strong> If you're expecting invoices but don't see them, please contact support.
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection