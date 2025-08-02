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

                <!-- Quick Payment Actions -->
                
                @if($unpaidInvoices->count() > 0)
                    <div class="subscription-card mb-4" style="border-left: 4px solid #dc3545;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-danger mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Outstanding Payments Required
                                </h5>
                                <p class="mb-2">
                                    You have <strong>{{ $unpaidInvoices->count() }}</strong> unpaid invoice{{ $unpaidInvoices->count() > 1 ? 's' : '' }} 
                                    totaling <strong class="text-danger">${{ number_format($totalUnpaid, 2) }}</strong>
                                </p>
                                <small class="text-muted">
                                    Please settle outstanding payments to maintain full access to your account.
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('invoices.index') }}" class="btn-custom-danger">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> View All Invoices
                                </a>
                                @if($unpaidInvoices->count() === 1)
                                    <a href="{{ route('invoices.pay', $unpaidInvoices->first()) }}" class="btn-custom-success">
                                        <i class="fas fa-credit-card me-1"></i> Pay Now (${{ number_format($unpaidInvoices->first()->amount_due, 2) }})
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        @if($unpaidInvoices->count() > 1)
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="mb-2">Quick Actions:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($unpaidInvoices->take(3) as $invoice)
                                        <a href="{{ route('invoices.pay', $invoice) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-credit-card me-1"></i>
                                            Pay Invoice #{{ $invoice->id }} (${{ number_format($invoice->amount_due, 2) }})
                                        </a>
                                    @endforeach
                                    @if($unpaidInvoices->count() > 3)
                                        <span class="text-muted align-self-center">
                                            and {{ $unpaidInvoices->count() - 3 }} more...
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="subscription-card mb-4" style="border-left: 4px solid #28a745;">
                        <div class="d-flex align-items-center">
                            <div class="text-success me-3">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-success mb-1">All Payments Up to Date!</h6>
                                <p class="mb-0 text-muted">
                                    No outstanding invoices. 
                                    <a href="{{ route('invoices.index') }}" class="text-decoration-none">View billing history →</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

            {{-- Variables are now passed from the controller --}}

            <div class="row">
                <!-- Main Subscription Status -->
                <div class="col-md-8">
                    <div class="subscription-card">
                        
                        @if($status === 'setup_pending')
                            <!-- Account Setup Pending -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                                    <h4>Account Setup in Progress</h4>
                                    <p class="text-muted">Our team is configuring your personalized plan. You'll receive an email once it's ready.</p>
                                </div>
                                <a href="{{ route('contact') }}" class="btn-custom-primary">
                                    <i class="fas fa-phone me-2"></i>Contact Support
                                </a>
                            </div>

                        @elseif($status === 'ready_to_subscribe')
                            <!-- First Time User - Choose Plan -->
                            <div class="py-4">
                                <div class="text-center mb-4">
                                    <i class="fas fa-rocket fa-3x text-success mb-3"></i>
                                    <h4>Choose Your Subscription Plan</h4>
                                    <p class="text-muted">Select the plan that best fits your needs and start your subscription.</p>
                                </div>

                                @if(count($userPlans) > 0)
                                    <div class="row">
                                        @foreach($userPlans as $planKey => $plan)
                                            <div class="col-md-6 mb-4">
                                                <div class="plan-card h-100 {{ $planKey === 'yearly' ? 'featured-plan' : '' }}" 
                                                     style="border: 2px solid {{ $planKey === 'yearly' ? '#28a745' : '#dee2e6' }}; border-radius: 15px; padding: 2rem; position: relative; background: white;">
                                                    
                                                    @if($planKey === 'yearly')
                                                        <div class="featured-badge" style="position: absolute; top: -10px; right: 20px; background: #28a745; color: white; padding: 5px 15px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                                                            POPULAR
                                                        </div>
                                                    @endif

                                                    <div class="text-center mb-3">
                                                        <h5 class="fw-bold">{{ $plan['name'] }}</h5>
                                                        <div class="price-display mb-2">
                                                            <span class="h3 text-primary">${{ number_format($plan['price'], 0) }}</span>
                                                            <span class="text-muted">{{ $plan['billing_cycle'] === 'monthly' ? '/month' : '/year' }}</span>
                                                        </div>
                                                        @if($plan['billing_cycle'] === 'yearly' && isset($userPlans['monthly']))
                                                            @php
                                                                $monthlyPrice = $userPlans['monthly']['price'];
                                                                $yearlyPrice = $plan['price'];
                                                                $yearlyEquivalent = $monthlyPrice * 12;
                                                                $savings = $yearlyEquivalent > 0 ? round((($yearlyEquivalent - $yearlyPrice) / $yearlyEquivalent) * 100) : 0;
                                                            @endphp
                                                            @if($savings > 0)
                                                                <div class="savings-badge text-success fw-bold">
                                                                    Save {{ $savings }}%
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>

                                                    <div class="features-list mb-4">
                                                        <div class="feature-item d-flex align-items-center mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>
                                                            <span class="text-muted">Unlimited AI consultations</span>
                                                        </div>
                                                        <div class="feature-item d-flex align-items-center mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>
                                                            <span class="text-muted">Patient case management</span>
                                                        </div>
                                                        <div class="feature-item d-flex align-items-center mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>
                                                            <span class="text-muted">Advanced medical analysis</span>
                                                        </div>
                                                        <div class="feature-item d-flex align-items-center mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>
                                                            <span class="text-muted">24/7 platform access</span>
                                                        </div>
                                                    </div>

                                                    <div class="text-center">
                                                        <button type="button" 
                                                                class="btn {{ $planKey === 'yearly' ? 'btn-custom-primary' : 'btn-custom-secondary' }} w-100" 
                                                                onclick="selectPlan('{{ $planKey }}')">
                                                            <i class="fas fa-credit-card me-2"></i>
                                                            Choose {{ $plan['name'] }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <p class="text-muted">No subscription plans are currently available. Please contact support.</p>
                                        <a href="{{ route('contact') }}" class="btn-custom-primary">
                                            <i class="fas fa-phone me-2"></i>Contact Support
                                        </a>
                                    </div>
                                @endif
                            </div>

                        @elseif($status === 'active')
                            <!-- Active Subscriber -->
                            <div class="py-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h4><i class="fas fa-check-circle text-success me-2"></i>Subscription Active</h4>
                                        <p class="text-muted mb-0">You have full access to all features</p>
                                    </div>
                                    <span class="status-badge status-active">Active</span>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="info-card p-3 rounded" style="background: #f8f9fa; border-left: 4px solid #28a745;">
                                            <small class="text-muted d-block">Your Plan</small>
                                            <strong class="text-success">{{ $setting->getAmountWithPeriod() }}</strong>
                                            <div class="mt-1">
                                                <small class="text-muted">{{ $setting->getBillingFrequencyText() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card p-3 rounded" style="background: #f8f9fa; border-left: 4px solid #007bff;">
                                            <small class="text-muted d-block">
                                                @if($setting->isUnlimitedSubscription())
                                                    Access
                                                @else
                                                    Expires On
                                                @endif
                                            </small>
                                            @if($setting->isUnlimitedSubscription())
                                                <strong class="text-success"><i class="fas fa-infinity me-1"></i>Unlimited</strong>
                                            @elseif($setting->subscription_ends_at)
                                                <strong class="text-primary">{{ $setting->subscription_ends_at->format('M d, Y') }}</strong>
                                                @if($setting->subscription_ends_at->isBefore(now()->addDays(30)))
                                                    <div class="mt-1">
                                                        <small class="text-warning">
                                                            <i class="fas fa-clock me-1"></i>{{ $setting->subscription_ends_at->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                @endif
                                            @else
                                                <strong class="text-muted">Not Set</strong>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="usage-summary p-3 rounded mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                    <div class="row text-center mb-3">
                                        <div class="col-md-4">
                                            <h4 class="text-primary mb-1">${{ number_format($monthlyCost, 2) }}</h4>
                                            <small class="text-muted">Cost Used This Month</small>
                                        </div>
                                        <div class="col-md-4">
                                            @if($costLimit > 0)
                                                <h4 class="text-info mb-1">${{ number_format($costLimit, 2) }}</h4>
                                                <small class="text-muted">Monthly Limit</small>
                                            @else
                                                <h4 class="text-success mb-1"><i class="fas fa-infinity"></i></h4>
                                                <small class="text-muted">No Limit Set</small>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            @if($excessCost > 0)
                                                <h4 class="text-danger mb-1">${{ number_format($excessCost, 2) }}</h4>
                                                <small class="text-muted">Excess Cost</small>
                                            @else
                                                @if($remainingCost >= 0)
                                                    <h4 class="text-success mb-1">${{ number_format($remainingCost, 2) }}</h4>
                                                    <small class="text-muted">Remaining</small>
                                                @else
                                                    <h4 class="text-success mb-1"><i class="fas fa-infinity"></i></h4>
                                                    <small class="text-muted">Unlimited</small>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($costLimit > 0)
                                        <!-- Usage Progress Bar -->
                                        <div class="usage-progress">
                                            <div class="usage-fill {{ $costUsagePercentage >= 90 ? 'high' : ($costUsagePercentage >= 70 ? 'medium' : 'low') }}" 
                                                 style="width: {{ min(100, $costUsagePercentage) }}%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">{{ number_format($costUsagePercentage, 1) }}% used</small>
                                            @if($excessCost > 0)
                                                <small class="text-danger"><strong>Over limit by ${{ number_format($excessCost, 2) }}</strong></small>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @if($costWarning)
                                    <div class="alert {{ $excessCost > 0 ? 'alert-danger' : 'alert-warning' }} mb-4">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        {{ $costWarning }}
                                    </div>
                                @endif

                                <div class="d-flex gap-2">
                                    <a href="{{ route('subscription.portal') }}" class="btn-custom-secondary">
                                        <i class="fas fa-cog me-2"></i>Manage Billing
                                    </a>
                                    <a href="{{ route('invoices.index') }}" class="btn-custom-secondary">
                                        <i class="fas fa-file-invoice me-2"></i>View Invoices
                                    </a>
                                </div>
                            </div>

                        @elseif($status === 'grace_period')
                            <!-- Grace Period -->
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                    <h4>Subscription Expired - Grace Period</h4>
                                    <div class="alert alert-warning">
                                        <strong>Your subscription expired on {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->format('M d, Y') : 'Unknown Date' }}</strong>
                                        <br>
                                        <small>You have {{ $setting->getDaysRemainingInCurrentPeriod() }} days remaining in your grace period</small>
                                    </div>
                                    <p class="text-muted mb-4">Your access continues during the grace period. Renew now to avoid any interruption.</p>
                                </div>
                                
                                <div class="plan-highlight p-4 rounded mb-4" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107;">
                                    <div class="row text-center">
                                        <div class="col-md-6">
                                            <h4 class="text-warning mb-1">{{ $setting->getAmountWithPeriod() }}</h4>
                                            <small class="text-muted">Renewal Rate</small>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="text-primary mb-1">{{ $setting->getSubscriptionPeriodText() }}</h5>
                                            <small class="text-muted">Billing Period</small>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn-custom-primary btn-lg" onclick="startPersonalizedCheckout()">
                                    <i class="fas fa-refresh me-2"></i>Renew Subscription
                                </button>
                                <div class="mt-3">
                                    <a href="{{ route('invoices.index') }}" class="btn-custom-secondary">
                                        <i class="fas fa-file-invoice me-2"></i>View Invoices
                                    </a>
                                </div>
                            </div>

                        @elseif($status === 'warning_period')
                            <!-- Warning Period -->
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                    <h4>Final Warning - Account Will Be Restricted</h4>
                                    <div class="alert alert-danger">
                                        <strong>Grace period ended on {{ $setting->getGracePeriodEndDate() ? $setting->getGracePeriodEndDate()->format('M d, Y') : 'Unknown Date' }}</strong>
                                        <br>
                                        <small>Your account will be restricted in {{ $setting->getDaysRemainingInCurrentPeriod() }} days if not renewed</small>
                                    </div>
                                    <p class="text-muted mb-4">This is your final warning. Renew immediately to avoid losing access to all features.</p>
                                </div>
                                
                                <div class="plan-highlight p-4 rounded mb-4" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border: 2px solid #dc3545;">
                                    <div class="row text-center">
                                        <div class="col-md-6">
                                            <h4 class="text-danger mb-1">{{ $setting->getAmountWithPeriod() }}</h4>
                                            <small class="text-muted">Renewal Rate</small>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="text-primary mb-1">{{ $setting->getSubscriptionPeriodText() }}</h5>
                                            <small class="text-muted">Billing Period</small>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn-custom-primary btn-lg" onclick="startPersonalizedCheckout()">
                                    <i class="fas fa-exclamation-circle me-2"></i>Renew Now - Avoid Restriction
                                </button>
                                <div class="mt-3">
                                    <a href="{{ route('invoices.index') }}" class="btn-custom-secondary">
                                        <i class="fas fa-file-invoice me-2"></i>View Invoices
                                    </a>
                                </div>
                            </div>

                        @elseif($status === 'restricted' || $status === 'should_be_restricted')
                            <!-- Restricted Account -->
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-ban fa-3x text-danger mb-3"></i>
                                    <h4>Account Restricted</h4>
                                    <div class="alert alert-danger">
                                        <strong>Your access has been limited due to unpaid invoices.</strong>
                                        <br>
                                        <small>Please resolve payment issues to restore full access.</small>
                                    </div>
                                </div>
                                <a href="{{ route('invoices.index') }}" class="btn-custom-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Pay Outstanding Invoices
                                </a>
                                <div class="mt-3">
                                    <a href="{{ route('contact') }}" class="btn-custom-secondary">
                                        <i class="fas fa-phone me-2"></i>Contact Support
                                    </a>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                <!-- Quick Stats & Info -->
                <div class="col-md-4">
                    <div class="row">
                        @if(in_array($status, ['active', 'ready_to_subscribe', 'grace_period', 'warning_period']))
                            <!-- Usage Stats for Active/Ready Users -->
                            <div class="col-12 mb-3">
                                <div class="stats-card">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-chart-line me-1"></i>Requests This Month
                                    </h6>
                                    <div class="stat-number text-primary">{{ number_format($user->getMonthlyRequestCount()) }}</div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="stats-card">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-dollar-sign me-1"></i>Cost This Month
                                    </h6>
                                    <div class="stat-number text-success">${{ number_format($monthlyCost, 2) }}</div>
                                    @if($costLimit > 0)
                                        <small class="text-muted">Limit: ${{ number_format($costLimit, 2) }}</small>
                                    @else
                                        <small class="text-muted">No limit set</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="stats-card">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-history me-1"></i>Total Sessions
                                    </h6>
                                    <div class="stat-number text-info">{{ number_format($user->openaiUsages()->count()) }}</div>
                                </div>
                            </div>
                        @endif

                        @if($setting && $setting->is_active)
                            <!-- Plan Details -->
                            <div class="col-12 mb-3">
                                <div class="stats-card" style="border-left: 4px solid #DE6262;">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-tag me-1"></i>Your Plan Details
                                    </h6>
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Rate:</span>
                                            <strong>{{ $setting->getAmountWithPeriod() }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Billing:</span>
                                            <strong>{{ $setting->getSubscriptionPeriodText() }}</strong>
                                        </div>
                                        @if($setting->subscription_starts_at)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Started:</span>
                                                <strong>{{ $setting->subscription_starts_at->format('M d, Y') }}</strong>
                                            </div>
                                        @endif
                                        @if($setting->subscription_ends_at && !$setting->isUnlimitedSubscription())
                                            <div class="d-flex justify-content-between">
                                                <span>Expires:</span>
                                                <strong class="{{ $isExpired ? 'text-danger' : 'text-success' }}">
                                                    {{ $setting->subscription_ends_at->format('M d, Y') }}
                                                </strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Quick Actions -->
                        <div class="col-12 mb-3">
                            <div class="stats-card">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-bolt me-1"></i>Quick Actions
                                </h6>
                                <div class="d-grid gap-2">
                                    @if($status === 'active')
                                        <a href="{{ route('ask-ai') }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-robot me-1"></i>Ask AI
                                        </a>
                                        <a href="{{ route('cases') }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-folder me-1"></i>My Cases
                                        </a>
                                    @elseif($status === 'ready_to_subscribe')
                                        <div class="text-muted">
                                            <i class="fas fa-arrow-up me-1"></i>Choose a plan above
                                        </div>
                                    @elseif(in_array($status, ['grace_period', 'warning_period']))
                                        <button class="btn btn-sm btn-warning" onclick="startPersonalizedCheckout()">
                                            <i class="fas fa-refresh me-1"></i>Renew
                                        </button>
                                        <a href="{{ route('ask-ai') }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-robot me-1"></i>Ask AI (Limited Time)
                                        </a>
                                    @endif
                                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-file-invoice me-1"></i>Invoices
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                            <th>Service Period</th>
                                            <th>Amount</th>
                                            <th>Payment Status</th>
                                            <th>Payment Date</th>
                                            <th>Next Renewal</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <code class="text-primary">#{{ substr($invoice->stripe_invoice_id, -8) }}</code>
                                                    <br><small class="text-muted">{{ $invoice->created_at ? $invoice->created_at->format('M j, Y') : 'Unknown' }}</small>
                                                </td>
                                                <td>
                                                    @if($invoice->line_items && count($invoice->line_items) > 0)
                                                        <strong class="text-primary">
                                                            @if(isset($invoice->line_items[0]['period_start']) && isset($invoice->line_items[0]['period_end']))
                                                                {{ \Carbon\Carbon::parse($invoice->line_items[0]['period_start'])->format('M j') }} - 
                                                                {{ \Carbon\Carbon::parse($invoice->line_items[0]['period_end'])->format('M j, Y') }}
                                                            @else
                                                                {{ $invoice->created_at ? $invoice->created_at->format('M Y') : 'Current Period' }}
                                                            @endif
                                                        </strong>
                                                    @else
                                                        <strong class="text-primary">{{ $invoice->created_at ? $invoice->created_at->format('M Y') : 'Current Period' }}</strong>
                                                    @endif
                                                    <br>
                                                    <small class="badge bg-light text-dark">
                                                        {{ $invoice->invoice_type ? ucfirst($invoice->invoice_type) : 'Subscription' }}
                                                    </small>
                                                    @if($invoice->description && $invoice->description !== 'Subscription payment')
                                                        <br><small class="text-muted">{{ $invoice->description }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong class="text-success">${{ number_format($invoice->amount_due, 2) }}</strong>
                                                    @if($invoice->amount_paid > 0 && $invoice->amount_paid != $invoice->amount_due)
                                                        <br><small class="text-warning">Paid: ${{ number_format($invoice->amount_paid, 2) }}</small>
                                                    @endif
                                                    @if($invoice->currency && strtoupper($invoice->currency) !== 'USD')
                                                        <br><small class="text-muted">{{ strtoupper($invoice->currency) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="{{ $invoice->getStatusBadgeClass() }}">
                                                        <i class="fas fa-{{ $invoice->status === 'paid' ? 'check-circle' : ($invoice->status === 'open' ? 'clock' : 'times-circle') }} me-1"></i>
                                                        {{ $invoice->getHumanStatus() }}
                                                    </span>
                                                    @if($invoice->paid_at)
                                                        <br><small class="text-success">Paid: {{ $invoice->paid_at->format('M j, Y') }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($invoice->paid_at)
                                                        <strong class="text-success">{{ $invoice->paid_at->format('M j, Y') }}</strong>
                                                        <br><small class="text-muted">{{ $invoice->paid_at->format('g:i A') }}</small>
                                                    @elseif($invoice->due_date)
                                                        <strong class="text-warning">Due: {{ $invoice->due_date->format('M j, Y') }}</strong>
                                                    @else
                                                        <span class="text-warning">
                                                            <i class="fas fa-clock me-1"></i>Payment Required
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(auth()->user()->monthlyInvoiceSetting && auth()->user()->monthlyInvoiceSetting->next_billing_date)
                                                        <strong class="text-primary">{{ auth()->user()->monthlyInvoiceSetting->next_billing_date->format('M j, Y') }}</strong>
                                                        <br><small class="text-muted">Next charge</small>
                                                    @elseif(auth()->user()->monthlyInvoiceSetting && auth()->user()->monthlyInvoiceSetting->subscription_ends_at)
                                                        @php
                                                            $endDate = auth()->user()->monthlyInvoiceSetting->subscription_ends_at;
                                                            $isExpired = $endDate->isPast();
                                                        @endphp
                                                        <strong class="{{ $isExpired ? 'text-danger' : 'text-success' }}">
                                                            {{ $endDate->format('M j, Y') }}
                                                        </strong>
                                                        <br><small class="text-muted">{{ $isExpired ? 'Expired' : 'Expires' }}</small>
                                                    @else
                                                        <span class="text-muted">
                                                            <i class="fas fa-question-circle me-1"></i>Not Set
                                                        </span>
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
                                <div class="col-md-3 mb-2">
                                    <div class="stats-card" style="padding: 1rem; border-left: 4px solid #28a745;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <div>
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Total Paid</div>
                                                <div style="font-size: 1.25rem; font-weight: 600; color: #28a745;">${{ number_format($invoices->where('status', 'paid')->sum('amount_paid'), 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="stats-card" style="padding: 1rem; border-left: 4px solid #DE6262;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                            <div>
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Outstanding</div>
                                                <div style="font-size: 1.25rem; font-weight: 600; color: #DE6262;">${{ number_format($invoices->whereIn('status', ['open', 'draft'])->sum('amount_due'), 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="stats-card" style="padding: 1rem; border-left: 4px solid #2c3e50;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-invoice text-primary me-2"></i>
                                            <div>
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Total Invoices</div>
                                                <div style="font-size: 1.25rem; font-weight: 600; color: #2c3e50;">{{ $invoices->count() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="stats-card" style="padding: 1rem; border-left: 4px solid #17a2b8;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-check text-info me-2"></i>
                                            <div>
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-bottom: 0.25rem;">Last Payment</div>
                                                @php $lastPaidInvoice = $invoices->where('status', 'paid')->sortByDesc('paid_at')->first(); @endphp
                                                <div style="font-size: 1.1rem; font-weight: 600; color: #17a2b8;">
                                                    {{ $lastPaidInvoice && $lastPaidInvoice->paid_at ? $lastPaidInvoice->paid_at->format('M j, Y') : 'N/A' }}
                                                </div>
                                                @if($lastPaidInvoice && $lastPaidInvoice->paid_at)
                                                    <small class="text-muted">${{ number_format($lastPaidInvoice->amount_paid, 2) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Actions for Invoices -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('invoices.index') }}" class="btn-custom-primary">
                                            <i class="fas fa-list me-2"></i>View All Invoices
                                        </a>
                                        @if($invoices->whereIn('status', ['open', 'draft'])->count() > 0)
                                            <a href="{{ route('invoices.index') }}?filter=unpaid" class="btn-custom-danger">
                                                <i class="fas fa-exclamation-triangle me-2"></i>Pay Outstanding ({{ $invoices->whereIn('status', ['open', 'draft'])->count() }})
                                            </a>
                                        @endif
                                        @if($setting && $setting->subscription_starts_at)
                                            <a href="{{ route('subscription.portal') }}" class="btn-custom-secondary">
                                                <i class="fas fa-cog me-2"></i>Billing Portal
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-file-invoice text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">No Invoices Yet</h5>
                                <p class="text-muted">Your invoices will appear here once you have an active subscription.</p>
                                @if(!$user->hasActiveSubscription())
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

function selectPlan(planId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    button.disabled = true;

    fetch('{{ route("subscription.checkout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            subscription_plan_id: planId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            alert(data.error || 'Failed to create checkout session');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while starting checkout');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function startPersonalizedCheckout() {
    const button = document.querySelector('.btn-custom-primary');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    button.disabled = true;

    fetch('{{ route("subscription.checkout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            // No plan needed - using personalized pricing
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            alert(data.error || 'Failed to create checkout session');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while starting checkout');
        button.innerHTML = originalText;
        button.disabled = false;
    });
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