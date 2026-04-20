@extends('master')

@section('title', 'MedCura AI - Modern EMR System for Healthcare Practices')

@push('styles')
<style>
:root {
    --primary: #DE6262;
    --primary-dark: #c55555;
    --text-dark: #2C3E50;
    --text-muted: #6C757D;
    --bg-light: #F8F9FA;
}

/* Hero Section */
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
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(222,98,98,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.btn-primary-custom {
    background: linear-gradient(135deg, var(--primary) 0%, #E87A7A 100%);
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

/* Feature Cards */
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
    background: linear-gradient(135deg, var(--primary), #E87A7A);
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
    background: linear-gradient(135deg, var(--primary), #E87A7A);
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
    border: 2px solid var(--primary);
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
    color: var(--primary);
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

/* Stats Section */
.stats-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 60px 0;
}

.stat-card {
    text-align: center;
    padding: 30px;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 10px;
}

.stat-label {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
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

@media (max-width: 768px) {
    .section-title { font-size: 2rem; }
    .price { font-size: 2.5rem; }
    .stat-number { font-size: 2rem; }
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
                            <i class="fas fa-check-circle me-2"></i>No credit card required
                            <i class="fas fa-check-circle ms-3 me-2"></i>Cancel anytime
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="position-relative">
                    <div class="d-inline-block position-relative">
                        <div class="bg-white rounded-circle p-5 shadow-lg" style="width: 300px; height: 300px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-medical" style="font-size: 120px; color: var(--primary);"></i>
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

<!-- AI Features Spotlight Section -->
<section id="ai-features" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="d-inline-block px-4 py-2 rounded-pill mb-3" style="background: linear-gradient(135deg, rgba(222,98,98,0.1), rgba(232,122,122,0.1)); border: 2px solid var(--primary);">
                <i class="fas fa-brain me-2" style="color: var(--primary);"></i>
                <span style="color: var(--primary); font-weight: 600;">AI-POWERED FEATURES</span>
            </div>
            <h2 class="section-title">Intelligent Healthcare Technology</h2>
            <p class="section-subtitle">Advanced AI features that transform your practice</p>
        </div>
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <div class="p-4">
                    <div class="mb-4">
                        <div class="d-flex align-items-start mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), #E87A7A);">
                                    <i class="fas fa-robot text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-2">AI Medical Copilot</h4>
                                <p class="text-muted mb-0">Intelligent assistant that helps with diagnosis suggestions, treatment recommendations, and clinical decision support based on patient data.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), #E87A7A);">
                                    <i class="fas fa-microphone-alt text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-2">Ambient Listening</h4>
                                <p class="text-muted mb-0">Real-time consultation recording with automatic transcription, speaker identification, and clinical chart population.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), #E87A7A);">
                                    <i class="fas fa-pills text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-2">Smart Prescription Suggestions</h4>
                                <p class="text-muted mb-0">AI-powered medication recommendations based on diagnosis, patient history, and drug interactions analysis.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), #E87A7A);">
                                    <i class="fas fa-chart-network text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-2">Predictive Analytics</h4>
                                <p class="text-muted mb-0">Advanced analytics for risk assessment, patient outcome predictions, and practice performance optimization.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="position-relative">
                    <div class="rounded-4 p-5 text-center" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
                        <i class="fas fa-brain mb-4" style="font-size: 120px; color: var(--primary);"></i>
                        <h3 class="text-white mb-3">AI-Powered Healthcare</h3>
                        <p class="text-white-50 mb-4">Experience the future of medical practice management with cutting-edge artificial intelligence</p>
                        <div class="d-flex justify-content-center gap-4 text-white">
                            <div>
                                <div class="h2 fw-bold mb-0" style="color: var(--primary);">24/7</div>
                                <small class="text-white-50">AI Availability</small>
                            </div>
                            <div>
                                <div class="h2 fw-bold mb-0" style="color: var(--primary);">95%</div>
                                <small class="text-white-50">Accuracy Rate</small>
                            </div>
                            <div>
                                <div class="h2 fw-bold mb-0" style="color: var(--primary);">50%</div>
                                <small class="text-white-50">Time Saved</small>
                            </div>
                        </div>
                    </div>
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
                        <i class="fas fa-user-doctor mb-3" style="font-size: 3rem; color: var(--primary);"></i>
                        <h4 class="mb-3">Create Account</h4>
                        <p class="text-muted">Sign up and set up your practice profile in minutes.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="mt-4">
                        <i class="fas fa-cog mb-3" style="font-size: 3rem; color: var(--primary);"></i>
                        <h4 class="mb-3">Configure Settings</h4>
                        <p class="text-muted">Set your availability, appointment types, and preferences.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="mt-4">
                        <i class="fas fa-rocket mb-3" style="font-size: 3rem; color: var(--primary);"></i>
                        <h4 class="mb-3">Start Managing</h4>
                        <p class="text-muted">Begin accepting appointments and managing patients.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Real Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Active Doctors</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Appointments Booked</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number">25K+</div>
                    <div class="stat-label">Patients Served</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number">4.8★</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section id="why-choose-us" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why Choose MedCura AI</h2>
            <p class="section-subtitle">Built for modern healthcare professionals</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-clock" style="font-size: 3rem; color: var(--primary);"></i>
                    </div>
                    <h5 class="mb-2">Save Time</h5>
                    <p class="text-muted mb-0">Reduce documentation time with voice transcription and automated workflows</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary);"></i>
                    </div>
                    <h5 class="mb-2">Secure & Reliable</h5>
                    <p class="text-muted mb-0">Enterprise-grade security with encrypted data storage and backups</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-mobile-alt" style="font-size: 3rem; color: var(--primary);"></i>
                    </div>
                    <h5 class="mb-2">Access Anywhere</h5>
                    <p class="text-muted mb-0">Cloud-based platform accessible from any device, anytime</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-headset" style="font-size: 3rem; color: var(--primary);"></i>
                    </div>
                    <h5 class="mb-2">Expert Support</h5>
                    <p class="text-muted mb-0">Dedicated support team to help you get the most from the platform</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); position: relative; overflow: hidden;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(222,98,98,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite;"></div>
    <div class="container text-center position-relative" style="z-index: 2; padding: 60px 0;">
        <h2 class="display-4 fw-bold mb-4 text-white">Ready to Transform Your Practice?</h2>
        <p class="lead mb-5 text-white opacity-90">Join healthcare professionals using modern EMR technology</p>
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
                <i class="fas fa-check-circle me-2"></i>No credit card required
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
