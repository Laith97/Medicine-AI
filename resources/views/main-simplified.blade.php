@extends('master')

@section('title', 'MedCura AI - Modern EMR System for Healthcare Practices')

@push('styles')
<style>
/* Simplified Professional Theme */
:root {
    --primary-color: #DE6262;
    --primary-hover: #c55555;
    --text-dark: #2C3E50;
    --text-muted: #6C757D;
    --bg-light: #F8F9FA;
}

/* Hero Section - Keep as requested */
.hero-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%
;
    height: 200%;
    background: radial-gradient(circle, rgba(222,98,98,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.btn-primary-custom {
    background: linear-gradient(135deg, var(--primary-color) 0%, #E87A7A 100%);
    border: none;
    color: white;
    padding: 14px 35px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(222, 98, 98, 0.35);
}

.btn-primary-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(222, 98, 98, 0.5);
    color: white;
}

.btn-outline-custom {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 14px 35px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-custom:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: white;
    color: white;
    transform: translateY(-3px);
}

/* Feature Cards - Simplified */
.feature-card {
    background: white;
    border-radius: 16px;
    padding: 40px 30px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), #E87A7A);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 2rem;
}

/* Step Cards */
.step-card {
    background: white;
    border-radius: 16px;
    padding: 40px 30px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.step-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.step-number {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), #E87A7A);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.4rem;
}

/* Pricing Card */
.pricing-card {
    background: white;
    border: 2px solid var(--primary-color);
    border-radius: 20px;
    padding: 40px;
    transition: all 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(222, 98, 98, 0.2);
}

.price {
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--primary-color);
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.feature-list li:last-child {
    border-bottom: none;
}

/* Section Titles */
.section-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 20px;
}

.section-subtitle {
    font-size: 1.2rem;
    color: var(--text-muted);
    margin-bottom: 50px;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 80px 0;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .section-title {
        font-size: 2rem;
    }
    
    .price {
        font-size: 2.5rem;
    }
}
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="hero-section d-flex align-items-center">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="text-white mb-5">
                    <h1 class="display-3 fw-bold mb-4">
                        <span style="color: #DE6262;">Modern EMR System</span><br>
                        <span style="color: #FFE4E1;">for Healthcare Practices</span>
                    </h1>
                    <p class="lead mb-4 opacity-90">
                        Complete patient management, appointment scheduling, voice transcription, and billing in one secure platform.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('register.doctor') }}" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-stethoscope me-2"></i>Start Free Trial
                        </a>
                        <a href="#features" class="btn btn-outline-custom btn-lg">
                            <i class="fas fa-play me-2"></i>See Features
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="text-white-50">
                            <i class="fas fa-check-circle me-2"></i>14-day free trial
                            <i class="fas fa-check-circle ms-3 me-2"></i>No credit card required
                            <i class="fas fa-check-circle ms-3 me-2"></i>Cancel anytime
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="position-relative">
                    <div class="d-inline-block position-relative">
                        <div class="bg-white rounded-circle p-5 shadow-lg" style="width: 300px; height: 300px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-medical" style="font-size: 120px; color: var(--primary-color);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Core Features</h2>
            <p class="section-subtitle">Everything you need to run a modern medical practice</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 class="mb-3">Patient Management</h4>
                    <p class="text-muted">Complete patient records, medical history, and treatment tracking in one place.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4 class="mb-3">Smart Scheduling</h4>
                    <p class="text-muted">Automated appointment booking with reminders and calendar integration.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h4 class="mb-3">Voice Transcription</h4>
                    <p class="text-muted">Real-time speech-to-text for clinical notes and documentation.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-prescription"></i>
                    </div>
                    <h4 class="mb-3">Digital Prescriptions</h4>
                    <p class="text-muted">Create and manage prescriptions digitally with patient history.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h4 class="mb-3">Billing & Invoicing</h4>
                    <p class="text-muted">Automated billing, payment tracking, and financial reporting.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="mb-3">Analytics Dashboard</h4>
                    <p class="text-muted">Track appointments, revenue, and practice performance metrics.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Get started in three simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="mt-4">
                        <i class="fas fa-user-doctor mb-3" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h4 class="mb-3">Create Account</h4>
                        <p class="text-muted">Sign up and set up your practice profile in minutes.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="mt-4">
                        <i class="fas fa-cog mb-3" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h4 class="mb-3">Configure Settings</h4>
                        <p class="text-muted">Set your availability, appointment types, and preferences.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="mt-4">
                        <i class="fas fa-rocket mb-3" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h4 class="mb-3">Start Managing</h4>
                        <p class="text-muted">Begin accepting appointments and managing patients.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if($showPricingSection)
<!-- Pricing Section -->
<section id="pricing" class="py-5" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Simple, Transparent Pricing</h2>
            <p class="section-subtitle">One plan with everything you need</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                @if(isset($pricingPlans) && !empty($pricingPlans))
                    @foreach($pricingPlans as $plan)
                    <div class="pricing-card text-center">
                        <h3 class="mb-3">{{ $plan['name'] }}</h3>
                        <p class="text-muted mb-4">{{ $plan['description'] }}</p>
                        
                        <div class="mb-4">
                            <span class="price">${{ $plan['price_monthly'] }}</span>
                            <span class="text-muted">/month</span>
                            <div class="mt-2">
                                <small class="text-muted">or ${{ $plan['price_yearly'] }}/year (save 17%)</small>
                            </div>
                        </div>
                        
                        <ul class="feature-list text-start mb-4">
                            @foreach(array_slice($plan['features'], 0, 8) as $feature)
                            <li><i class="fas fa-check text-success me-2"></i>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        
                        <a href="{{ $plan['button_url'] }}" class="btn btn-primary-custom btn-lg w-100">
                            {{ $plan['button_text'] }}
                        </a>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success me-1"></i>
                                14-day free trial • No credit card required
                            </small>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</section>
@endif

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="display-4 fw-bold mb-4">Ready to Get Started?</h2>
        <p class="lead mb-5">Join healthcare professionals using modern EMR technology</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="{{ route('register.doctor') }}" class="btn btn-primary-custom btn-lg">
                <i class="fas fa-stethoscope me-2"></i>Start Free Trial
            </a>
            <a href="{{ route('contact') }}" class="btn btn-outline-custom btn-lg">
                <i class="fas fa-phone me-2"></i>Contact Sales
            </a>
        </div>
        <div class="mt-4">
            <p class="text-white-50 mb-0">
                <i class="fas fa-check-circle me-2"></i>14-day free trial
                <i class="fas fa-check-circle ms-3 me-2"></i>No credit card required
                <i class="fas fa-check-circle ms-3 me-2"></i>Cancel anytime
            </p>
        </div>
    </div>
</section>

<script>
// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Simple fade-in animation on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.feature-card, .step-card, .pricing-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.5s ease';
    observer.observe(el);
});
</script>
@endsection
