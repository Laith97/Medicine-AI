@extends('master')

@section('title', 'Subscription Plans')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .pricing-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .pricing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .pricing-card.featured {
        border: 2px solid #DE6262;
    }
    
    .pricing-card.featured::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .price-display {
        font-size: 3rem;
        font-weight: 700;
        color: #DE6262;
    }
    
    .btn-select-plan {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        border: none;
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
    }
    
    .btn-select-plan:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(222, 98, 98, 0.3);
    }
    
    .feature-icon {
        color: #28a745;
        font-size: 1.2rem;
    }
</style>
@endpush

@section('content')
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">Pricing</h2>
    <p>View pricing plans</p>
</div>
<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark">Choose Your Subscription Plan</h2>
                    <p class="text-muted">Select the plan that best fits your medical practice needs</p>
                </div>
                
                @if(isset($trialInfo) && $trialInfo['is_in_trial'])
                    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(13, 202, 240, 0.2);">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-gift fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-2">
                                    <i class="fas fa-clock me-2"></i>Free Trial Active - {{ $trialInfo['trial_days_remaining'] }} Days Remaining
                                </h5>
                                <p class="mb-2">
                                    You're currently enjoying full access to all features! Choose a plan below to continue after your trial ends.
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @elseif(isset($trialInfo) && $trialInfo['has_used_trial'] && $trialInfo['trial_status'] === 'expired')
                    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-2">
                                    <i class="fas fa-hourglass-end me-2"></i>Trial Period Ended
                                </h5>
                                <p class="mb-2">
                                    Your free trial has expired. Choose a plan below to continue using all features.
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <div class="text-center">
                    
                    <!-- Billing Toggle -->
                    <div class="d-inline-flex align-items-center p-2 rounded-pill mt-3" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <span class="px-3 py-2 billing-period-label" id="monthly-label" style="border-radius: 20px; cursor: pointer; transition: all 0.3s ease; background: #DE6262; color: white;">Monthly</span>
                        <span class="px-3 py-2 billing-period-label" id="yearly-label" style="border-radius: 20px; cursor: pointer; transition: all 0.3s ease; margin-left: 5px;">Yearly <small class="text-success">(Save up to 17%)</small></span>
                    </div>
                </div>

                @if($plans && count($plans) > 0)
                    <div class="row g-4" id="pricing-plans">
                        @foreach($plans as $planKey => $plan)
                            <div class="col-md-6 plan-card" data-plan="{{ $planKey }}">
                                <div class="pricing-card h-100 {{ $planKey === 'yearly' ? 'featured' : '' }} position-relative">
                                    @if($planKey === 'yearly')
                                        <div class="position-absolute top-0 start-50 translate-middle">
                                            <span class="badge" style="background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%); color: white; padding: 8px 16px; border-radius: 20px;">Best Value</span>
                                        </div>
                                    @endif
                                    
                                    <div class="text-center mb-4" style="padding-top: {{ $planKey === 'yearly' ? '30px' : '0' }};">
                                        <h3 class="fw-bold text-dark mb-2">{{ $plan['name'] }}</h3>
                                        <p class="text-muted">{{ $plan['description'] }}</p>
                                    </div>
                                    
                                    <div class="text-center mb-4">
                                        <div class="price-display">${{ number_format($plan['price'], 0) }}</div>
                                        <span class="text-muted fs-5">
                                            /{{ $plan['billing_cycle'] === 'monthly' ? 'month' : 'year' }}
                                        </span>
                                        @if($planKey === 'yearly' && isset($plan['monthly_equivalent']))
                                            <div class="text-muted mt-1">
                                                <small>{{ $plan['monthly_equivalent'] }}</small>
                                            </div>
                                        @endif
                                    </div>
                                            
                                    @if($planKey === 'yearly' && isset($plan['savings']) && $plan['savings'] > 0)
                                        <div class="alert alert-success py-2 mb-3" style="border-radius: 15px;">
                                            <small class="fw-bold">
                                                <i class="bi bi-piggy-bank me-1"></i>
                                                Save ${{ number_format($plan['savings'], 0) }} annually ({{ $plan['savings_percentage'] }}% off)!
                                            </small>
                                        </div>
                                    @endif
                                    
                                    <ul class="list-unstyled text-start mb-4">
                                        <li class="mb-3">
                                            <i class="bi bi-check-circle-fill feature-icon me-2"></i>
                                            <span>Unlimited AI consultations</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="bi bi-check-circle-fill feature-icon me-2"></i>
                                            <span>Patient case management</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="bi bi-check-circle-fill feature-icon me-2"></i>
                                            <span>Medical document analysis</span>
                                        </li>
                                        <li class="mb-3">
                                            <i class="bi bi-check-circle-fill feature-icon me-2"></i>
                                            <span>24/7 support</span>
                                        </li>
                                        @if($planKey === 'yearly')
                                            <li class="mb-3">
                                                <i class="bi bi-check-circle-fill feature-icon me-2"></i>
                                                <span>Priority support</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="bi bi-check-circle-fill feature-icon me-2"></i>
                                                <span>Advanced analytics</span>
                                            </li>
                                        @endif
                                    </ul>
                                    
                                    <div class="text-center mt-auto">
                                        <button type="button" 
                                                class="btn btn-select-plan text-white w-100"
                                                onclick="selectPlan('{{ $planKey }}')">
                                            Choose {{ $plan['name'] }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                        
                    
                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="pricing-card text-center">
                                <h4 class="fw-bold text-dark mb-4">Why Choose Our Medical AI Platform?</h4>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="bi bi-shield-check" style="font-size: 3rem; color: #DE6262;"></i>
                                            <h6 class="mt-3 fw-bold">HIPAA Compliant</h6>
                                            <p class="text-muted">Your patient data is secure and compliant with healthcare regulations</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="bi bi-cpu" style="font-size: 3rem; color: #DE6262;"></i>
                                            <h6 class="mt-3 fw-bold">Advanced AI</h6>
                                            <p class="text-muted">Powered by the latest medical AI models for accurate analysis</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <i class="bi bi-headset" style="font-size: 3rem; color: #DE6262;"></i>
                                            <h6 class="mt-3 fw-bold">24/7 Support</h6>
                                            <p class="text-muted">Get help whenever you need it from our medical support team</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="text-center">
                                <a href="{{ route('subscription.manage') }}" class="btn btn-outline-secondary btn-lg" style="border-radius: 50px; padding: 12px 30px;">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Subscription Management
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="pricing-card text-center py-5">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                        <h4 class="mt-4 fw-bold">No Plans Available</h4>
                        <p class="text-muted mb-4">No subscription plans are currently available. Please contact support.</p>
                        <a href="{{ route('subscription.manage') }}" class="btn btn-select-plan text-white">
                            <i class="bi bi-arrow-left me-2"></i>Back to Subscription Management
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
// Billing Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const monthlyLabel = document.getElementById('monthly-label');
    const yearlyLabel = document.getElementById('yearly-label');
    const planCards = document.querySelectorAll('.plan-card');
    
    // Set default to show monthly plans
    showPlansForPeriod('monthly');
    
    if (monthlyLabel) {
        monthlyLabel.addEventListener('click', function() {
            setActiveLabel('monthly');
            showPlansForPeriod('monthly');
        });
    }
    
    if (yearlyLabel) {
        yearlyLabel.addEventListener('click', function() {
            setActiveLabel('yearly');
            showPlansForPeriod('yearly');
        });
    }
    
    function setActiveLabel(period) {
        // Reset both labels
        monthlyLabel.style.background = 'transparent';
        monthlyLabel.style.color = '#6c757d';
        yearlyLabel.style.background = 'transparent';
        yearlyLabel.style.color = '#6c757d';
        
        // Set active label
        if (period === 'monthly') {
            monthlyLabel.style.background = '#DE6262';
            monthlyLabel.style.color = 'white';
        } else {
            yearlyLabel.style.background = '#DE6262';
            yearlyLabel.style.color = 'white';
        }
    }
    
    function showPlansForPeriod(period) {
        planCards.forEach(card => {
            const planType = card.getAttribute('data-plan');
            if (planType === period) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
});

function selectPlan(planType) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    button.disabled = true;
    
    // Make AJAX request to create checkout session
    fetch('{{ route("subscription.checkout") }}', {
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
        if (data.checkout_url) {
            // Redirect to Stripe checkout
            window.location.href = data.checkout_url;
        } else if (data.error) {
            // Show error message
            alert('Error: ' + data.error);
            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;
        } else {
            // Unexpected response format
            throw new Error('Unexpected response format');
        }
    })
    .catch(error => {
        console.error('Checkout error:', error);
        clearTimeout(timeoutWarning);
        
        // More specific error messages
        let errorMessage = 'An error occurred while processing your request. Please try again.';
        
        if (error.message.includes('non-JSON response')) {
            errorMessage = 'Server configuration error. Please contact support.';
        } else if (error.message.includes('HTTP error')) {
            errorMessage = 'Network error. Please check your connection and try again.';
        }
        
        alert(errorMessage);
        
        // Restore button
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
@endsection