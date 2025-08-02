@extends('layouts.admin')

@section('title', 'Subscription Plans')

@push('styles')
<style>
    .admin-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .admin-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }

    .plan-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }

    .plan-card:hover {
        transform: translateY(-5px);
    }

    .plan-card.featured {
        border: 2px solid #28a745;
        position: relative;
    }

    .featured-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 5px 15px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .plan-price {
        font-size: 2rem;
        font-weight: bold;
        color: #2c3e50;
    }

    .plan-cycle {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .feature-list {
        list-style: none;
        padding: 0;
    }

    .feature-list li {
        padding: 0.25rem 0;
        color: #6c757d;
    }

    .feature-list li:before {
        content: "✓";
        color: #28a745;
        font-weight: bold;
        margin-right: 0.5rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-active {
        background: #d4edda;
        color: #155724;
    }

    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2 text-white">Subscription Plans</h1>
                    <p class="mb-0 opacity-75">Manage subscription plans for users</p>
                </div>
                <div>
                    <a href="{{ route('admin.user-pricing.index') }}" class="btn btn-success me-2">
                        <i class="bi bi-currency-dollar me-2"></i>User Pricing
                    </a>
                    <a href="{{ route('admin.subscription-plans.create') }}" class="btn btn-light">
                        <i class="bi bi-plus-circle me-2"></i>Create New Plan
                    </a>
                </div>
            </div>
        </div>

        <!-- Deprecation Warning -->
        <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 15px; padding: 1.5rem;">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle text-warning me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">⚠️ System-Wide Plans Are Deprecated</h5>
                    <p class="mb-2">
                        <strong>This page is now obsolete.</strong> The system has been updated to use <strong>per-user pricing</strong> instead of system-wide subscription plans.
                    </p>
                    <p class="mb-2">
                        • <strong>Editing these plans will NOT affect user pricing</strong><br>
                        • Each user now has individual monthly/yearly pricing<br>
                        • Use <strong><a href="{{ route('admin.users.index') }}" class="alert-link">Manage Users</a></strong> to set user-specific pricing
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>Recommended:</strong> Use the <a href="{{ route('admin.user-pricing.index') }}" class="alert-link"><i class="fas fa-dollar-sign"></i> User Pricing</a> page instead.
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Plans Grid -->
        @if($plans->count() > 0)
            <div class="row">
                @foreach($plans as $plan)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="plan-card {{ $plan->is_featured ? 'featured' : '' }}">
                            @if($plan->is_featured)
                                <div class="featured-badge">FEATURED</div>
                            @endif

                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">{{ $plan->name }}</h5>
                                    <span class="status-badge {{ $plan->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.subscription-plans.show', $plan) }}">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.subscription-plans.edit', $plan) }}">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.subscription-plans.toggle-active', $plan) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-{{ $plan->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </li>
                                        @if($plan->monthlyInvoiceSettings()->count() == 0)
                                            <li>
                                                <form action="{{ route('admin.subscription-plans.destroy', $plan) }}" method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this plan?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            <div class="text-center mb-3">
                                <div class="plan-price">${{ number_format($plan->price, 0) }}</div>
                                <div class="plan-cycle">{{ $plan->billing_cycle === 'monthly' ? 'per month' : 'per year' }}</div>
                                @if($plan->billing_cycle === 'yearly')
                                    <small class="text-success">
                                        <i class="bi bi-arrow-down"></i>
                                        Save ${{ number_format(($plan->price / 12) * 12 - $plan->price, 0) }} annually
                                    </small>
                                @endif
                            </div>

                            @if($plan->description)
                                <p class="text-muted small mb-3">{{ $plan->description }}</p>
                            @endif

                            @if($plan->features && count($plan->features) > 0)
                                <ul class="feature-list mb-3">
                                    @foreach(array_slice($plan->features, 0, 4) as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                    @if(count($plan->features) > 4)
                                        <li class="text-muted">+ {{ count($plan->features) - 4 }} more features</li>
                                    @endif
                                </ul>
                            @endif

                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span>{{ $plan->monthlyInvoiceSettings()->count() }} users</span>
                                <span>Sort: {{ $plan->sort_order }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-credit-card" style="font-size: 4rem; color: #dee2e6;"></i>
                </div>
                <h4 class="text-muted">No Subscription Plans</h4>
                <p class="text-muted">Create your first subscription plan to get started.</p>
                <a href="{{ route('admin.subscription-plans.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Create First Plan
                </a>
            </div>
        @endif
    </div>
</div>
@endsection