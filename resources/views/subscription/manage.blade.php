@extends('master')

@section('title', 'Manage Subscription')

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

    .plan-badge {
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .plan-free { 
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%); 
        color: white; 
    }
    .plan-basic { 
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); 
        color: white; 
    }
    .plan-pro { 
        background: linear-gradient(135deg, #DE6262 0%, #c44d4d 100%); 
        color: white; 
    }
    .plan-enterprise { 
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); 
        color: white; 
    }

    .usage-progress {
        background-color: #e9ecef;
        border-radius: 15px;
        height: 16px;
        overflow: hidden;
        margin: 1.5rem 0;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .usage-fill {
        height: 100%;
        border-radius: 15px;
        transition: width 0.5s ease;
        position: relative;
    }
    
    .usage-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .usage-fill.low { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
    .usage-fill.medium { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
    .usage-fill.high { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); }

    .stat-item {
        text-align: center;
        padding: 1.5rem;
        border-radius: 15px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        margin-bottom: 1rem;
        border: 1px solid rgba(222, 98, 98, 0.1);
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(222, 98, 98, 0.15);
    }

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

    /* Consistent Button Styles */
    .btn-custom-primary {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-custom-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-custom-secondary {
        background: white;
        border: 2px solid #DE6262;
        color: #DE6262;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-custom-secondary:hover {
        background: #DE6262;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.3);
        text-decoration: none;
    }
    
    .btn-custom-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-custom-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-custom-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-custom-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-custom-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-custom-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
        color: white;
        text-decoration: none;
    }

    /* Small button variants for table actions */
    .btn-sm-custom-primary {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
    }
    
    .btn-sm-custom-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-sm-custom-secondary {
        background: white;
        border: 2px solid #DE6262;
        color: #DE6262;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
    }
    
    .btn-sm-custom-secondary:hover {
        background: #DE6262;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        text-decoration: none;
    }
    
    .btn-sm-custom-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
    }
    
    .btn-sm-custom-info:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        color: white;
        text-decoration: none;
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

    /* Status Badges */
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }
    
    .status-inactive {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }
    
    .status-paid {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }
    
    .status-unpaid {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
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

    /* Modal Enhancements */
    .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .modal-header {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        color: white;
        border-radius: 20px 20px 0 0;
        border: none;
    }
    
    .modal-header .btn-close {
        filter: invert(1);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .subscription-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .stats-card {
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
        }
        
        .btn-custom-primary,
        .btn-custom-secondary,
        .btn-custom-success,
        .btn-custom-danger,
        .btn-custom-info {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Page Header -->
                <div class="page-header text-center text-md-start">
                    <h1><i class="fas fa-credit-card me-2"></i>Manage Subscription</h1>
                    <p class="text-muted mb-0">View and manage your subscription plan and usage</p>
                </div>

            <div class="row">
                <!-- Current Plan -->
                <div class="col-md-8">
                    <div class="subscription-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4>Current Plan</h4>
                                <span class="plan-badge plan-{{ auth()->user()->current_plan }}">
                                    {{ ucfirst(auth()->user()->current_plan) }} Plan
                                </span>
                            </div>
                            @if(auth()->user()->subscription_active)
                                <span class="status-badge status-active">Active</span>
                            @else
                                <span class="status-badge status-inactive">Inactive</span>
                            @endif
                        </div>

                        @if($subscription)
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <strong>Billing Cycle:</strong> {{ ucfirst($subscription->billing_cycle) }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Amount:</strong> ${{ number_format($subscription->amount, 2) }}/{{ $subscription->billing_cycle === 'yearly' ? 'year' : 'month' }}
                                </div>
                                <div class="col-md-6 mt-2">
                                    <strong>Next Billing:</strong> {{ $subscription->current_period_end ? $subscription->current_period_end->format('M j, Y') : 'N/A' }}
                                </div>
                                <div class="col-md-6 mt-2">
                                    <strong>Status:</strong> 
                                    <span class="status-badge {{ $subscription->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <!-- Usage Statistics -->
                        @php
                            $user = auth()->user();
                            $planConfig = $user->getPlanConfig();
                            $monthlyUsage = $user->getMonthlyTokenUsage();
                            $tokenLimit = $planConfig['token_limit'] ?? 0;
                            $usagePercentage = $tokenLimit > 0 ? ($monthlyUsage / $tokenLimit) * 100 : 0;
                            $usageClass = $usagePercentage >= 90 ? 'high' : ($usagePercentage >= 70 ? 'medium' : 'low');
                        @endphp

                        <h5 class="mb-3">Usage This Month</h5>
                        <div class="usage-progress">
                            <div class="usage-fill {{ $usageClass }}" style="width: {{ min($usagePercentage, 100) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>{{ number_format($monthlyUsage) }} tokens used</span>
                            <span>{{ $tokenLimit === -1 ? 'Unlimited' : number_format($tokenLimit) }} limit</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            @if(!auth()->user()->subscription_active)
                                <a href="/#pricing" class="btn-custom-primary">
                                    <i class="fas fa-rocket"></i>Upgrade Plan
                                </a>
                            @else
                                <a href="{{ route('subscription.portal') }}" class="btn-custom-secondary">
                                    <i class="fas fa-external-link-alt"></i>Manage Billing
                                </a>
                                <button type="button" style="background: #DE6262" class="btn-custom-danger" onclick="confirmCancellation()">
                                    <i class="fas fa-times"></i>Cancel Subscription
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="stats-card">
                                <h6 class="text-muted mb-2">Requests This Month</h6>
                                <div class="stat-number">{{ number_format($user->getMonthlyRequestCount()) }}</div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="stats-card">
                                <h6 class="text-muted mb-2">Tokens This Month</h6>
                                <div class="stat-number">{{ number_format($monthlyUsage) }}</div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="stats-card">
                                <h6 class="text-muted mb-2">Estimated Cost</h6>
                                <div class="stat-number">${{ number_format($user->getMonthlyCostEstimate(), 4) }}</div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="stats-card">
                                <h6 class="text-muted mb-2">Total Requests</h6>
                                <div class="stat-number">{{ number_format($user->openaiUsages()->count()) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Features -->
                    @if(isset($planConfig['features']))
                        <div class="subscription-card">
                            <h5 class="mb-3">Plan Features</h5>
                            <ul class="list-unstyled">
                                @foreach($planConfig['features'] as $feature)
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>{{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Invoices Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="subscription-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><i class="fas fa-file-invoice-dollar me-2"></i>Recent Invoices</h4>
                            @if($invoices->count() > 0)
                                <small class="text-muted">Showing last {{ $invoices->count() }} invoices</small>
                            @endif
                        </div>

                        @if($invoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <code>{{ substr($invoice->stripe_invoice_id, -8) }}</code>
                                                </td>
                                                <td>
                                                    {{ $invoice->created_at->format('M j, Y') }}
                                                </td>
                                                <td>
                                                    {{ $invoice->description }}
                                                    @if($invoice->line_items && count($invoice->line_items) > 0)
                                                        <br>
                                                        <small class="text-muted">
                                                            @if(isset($invoice->line_items[0]['period_start']) && isset($invoice->line_items[0]['period_end']))
                                                                {{ \Carbon\Carbon::parse($invoice->line_items[0]['period_start'])->format('M j') }} - 
                                                                {{ \Carbon\Carbon::parse($invoice->line_items[0]['period_end'])->format('M j, Y') }}
                                                            @endif
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>${{ number_format($invoice->amount_due, 2) }}</strong>
                                                    @if($invoice->amount_paid > 0 && $invoice->amount_paid != $invoice->amount_due)
                                                        <br><small class="text-success">Paid: ${{ number_format($invoice->amount_paid, 2) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="{{ $invoice->getStatusBadgeClass() }}">
                                                        {{ $invoice->getHumanStatus() }}
                                                    </span>
                                                    @if($invoice->paid_at)
                                                        <br><small class="text-muted">{{ $invoice->paid_at->format('M j, Y') }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $invoiceUrl = $invoice->invoice_url;
                                                        $invoicePdf = $invoice->invoice_pdf;
                                                        
                                                        // Ensure URLs are strings
                                                        if (is_array($invoiceUrl)) {
                                                            $invoiceUrl = isset($invoiceUrl[0]) ? $invoiceUrl[0] : null;
                                                        }
                                                        if (is_array($invoicePdf)) {
                                                            $invoicePdf = isset($invoicePdf[0]) ? $invoicePdf[0] : null;
                                                        }
                                                    @endphp
                                                    
                                                    <div class="d-flex gap-1">
                                                        @if($invoiceUrl && is_string($invoiceUrl) && filter_var($invoiceUrl, FILTER_VALIDATE_URL))
                                                            <a href="{{ $invoiceUrl }}" target="_blank" class="btn-sm-custom-primary" title="View Invoice">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        @endif
                                                        @if($invoicePdf && is_string($invoicePdf) && filter_var($invoicePdf, FILTER_VALIDATE_URL))
                                                            <a href="{{ $invoicePdf }}" target="_blank" class="btn-sm-custom-secondary" title="Download PDF">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Invoice Summary -->
                            <div class="row mt-4">
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card bg-success text-white">
                                        <h6 class="mb-2 opacity-75">Total Paid</h6>
                                        <div class="stat-number text-white">${{ number_format(auth()->user()->getTotalPaidAmount(), 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card bg-danger text-white">
                                        <h6 class="mb-2 opacity-75">Outstanding</h6>
                                        <div class="stat-number text-white">${{ number_format(auth()->user()->getTotalUnpaidAmount(), 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card bg-info text-white">
                                        <h6 class="mb-2 opacity-75">Total Invoices</h6>
                                        <div class="stat-number text-white">{{ auth()->user()->stripeInvoices()->count() }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card bg-warning text-dark">
                                        <h6 class="mb-2 opacity-75">Last Payment</h6>
                                        @php $lastInvoice = auth()->user()->getLastPaidInvoice(); @endphp
                                        <div class="stat-number text-dark">{{ $lastInvoice ? $lastInvoice->paid_at->format('M j') : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-file-invoice text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">No Invoices Yet</h5>
                                <p class="text-muted">Your invoices will appear here once you have an active subscription.</p>
                                @if(!auth()->user()->subscription_active)
                                    <a href="/#pricing" class="btn-custom-primary mt-3">
                                        <i class="fas fa-rocket"></i>Choose a Plan
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancellation Confirmation Modal -->
<div class="modal fade" id="cancellationModal" tabindex="-1" aria-labelledby="cancellationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancellationModalLabel" style="background: #DE6262">Cancel Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel your subscription?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Your subscription will remain active until {{ $subscription?->current_period_end?->format('M j, Y') ?? 'the end of the current billing period' }}, after which you'll be moved to the free plan.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-custom-secondary" data-bs-dismiss="modal">Keep Subscription</button>
                <button type="button" class="btn-custom-danger" onclick="cancelSubscription()">
                    <i class="fas fa-times"></i>Cancel Subscription
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmCancellation() {
    const modal = new bootstrap.Modal(document.getElementById('cancellationModal'));
    modal.show();
}

function cancelSubscription() {
    const button = document.querySelector('#cancellationModal .btn-custom-danger');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Cancelling...';
    button.disabled = true;

    fetch('{{ route("subscription.cancel") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to cancel subscription');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the subscription');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
@endpush