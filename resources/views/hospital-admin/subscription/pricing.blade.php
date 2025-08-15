@extends('layouts.app')

@section('page-title', 'Subscription Plans')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Choose Your Subscription Plan</h1>
                    <p class="text-muted">Select the best plan for your hospital</p>
                    @if($doctorCount > 1)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Pricing is calculated for <strong>{{ $doctorCount }} doctors</strong> in your hospital.
                        </div>
                    @endif
                </div>
                <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Subscription
                </a>
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

            <div class="row justify-content-center">
                @if(!empty($plans))
                    @foreach($plans as $planKey => $plan)
                        <div class="col-lg-6 col-xl-5 mb-4">
                            <div class="card h-100 {{ $planKey === 'yearly' ? 'border-primary' : '' }} position-relative">
                                @if($planKey === 'yearly')
                                    <div class="position-absolute top-0 start-50 translate-middle">
                                        <span class="badge bg-primary px-3 py-2">Most Popular</span>
                                    </div>
                                @endif
                                
                                <div class="card-header text-center bg-transparent border-0 pt-4">
                                    <h4 class="fw-bold text-capitalize">{{ $planKey }} Plan</h4>
                                    <div class="display-4 fw-bold text-primary">
                                        ${{ number_format($plan['price'], 0) }}
                                    </div>
                                    <span class="text-muted fs-5">
                                        /{{ $plan['billing_cycle'] === 'monthly' ? 'month' : 'year' }}
                                    </span>
                                    @if($planKey === 'yearly' && isset($plan['monthly_equivalent']))
                                        <div class="text-muted mt-1">
                                            <small>{{ $plan['monthly_equivalent'] }} per month</small>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="card-body">
                                    @if($planKey === 'yearly' && isset($plan['savings']) && $plan['savings'] > 0)
                                        <div class="alert alert-success py-2 mb-3">
                                            <small class="fw-bold">
                                                <i class="fas fa-piggy-bank me-1"></i>
                                                Save ${{ number_format($plan['savings'], 0) }} annually ({{ $plan['savings_percentage'] }}% off)!
                                            </small>
                                        </div>
                                    @endif
                                    
                                    <ul class="list-unstyled">
                                        <li class="mb-3">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span>Unlimited AI consultations for all doctors</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span>Patient case management system</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span>Medical document analysis</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span>Hospital analytics dashboard</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span>Doctor performance tracking</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span>24/7 support</span>
                                        </li>
                                        @if($planKey === 'yearly')
                                            <li class="mb-3">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <span>Priority support</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <span>Advanced analytics & reports</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <span>Custom integrations</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                                
                                <div class="card-footer bg-transparent border-0 text-center pb-4">
                                    @if($setting && $setting->isActive() && $setting->subscription_period_months == ($planKey === 'yearly' ? 12 : 1))
                                        <button class="btn btn-success btn-lg w-100" disabled>
                                            <i class="fas fa-check me-2"></i>Current Plan
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-lg w-100" onclick="selectPlan('{{ $planKey }}')">
                                            <i class="fas fa-credit-card me-2"></i>Select {{ ucfirst($planKey) }} Plan
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="card text-center py-5">
                            <div class="card-body">
                                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                                <h4 class="mt-4 fw-bold">No Plans Available</h4>
                                <p class="text-muted mb-4">No subscription plans are currently available. Please contact support.</p>
                                <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Subscription Management
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function selectPlan(planType) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    button.disabled = true;
    
    // Make AJAX request to create checkout session
    fetch('{{ route("hospital-admin.subscription.checkout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            plan_type: planType
        })
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success && data.checkout_url) {
            // Redirect to Stripe checkout
            window.location.href = data.checkout_url;
        } else {
            throw new Error(data.error || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Checkout error:', error);
        
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Show error message
        let errorMessage = 'An error occurred while processing your request. Please try again.';
        
        if (error.message.includes('HTTP error')) {
            errorMessage = 'Server error. Please try again later or contact support.';
        } else if (error.message !== 'Unknown error occurred') {
            errorMessage = error.message;
        }
        
        // Create and show alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            ${errorMessage}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert alert at the top of the container
        const container = document.querySelector('.container-fluid .row .col-12');
        container.insertBefore(alertDiv, container.children[1]);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    });
}
</script>
@endsection