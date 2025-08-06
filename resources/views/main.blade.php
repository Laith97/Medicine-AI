@extends('master')

@section('title', 'MedCura AI - Complete Healthcare Platform | AI Diagnosis, Patient Management & Practice Growth')

@push('styles')
<style>
.theme-primary { background-color: #DE6262 !important; }
.text-theme-primary { color: #DE6262 !important; }
.border-theme-primary { border-color: #DE6262 !important; }

.btn-theme-primary {
    background: linear-gradient(45deg, #DE6262, #E87A7A);
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
}
.btn-theme-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
    color: white;
}

.btn-theme-outline {
    background: transparent;
    border: 2px solid #DE6262;
    color: #DE6262;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}
.btn-theme-outline:hover {
    background: #DE6262;
    color: white;
    transform: translateY(-2px);
}

.hero-section {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.hero-pattern {
    position: absolute;
    top: 0;
    right: 0;
    width: 50%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="50" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="30" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
}

.feature-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(45deg, #DE6262, #E87A7A);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 2rem;
}

.step-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.step-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.step-number {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: #DE6262;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.stats-section {
    background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
    position: relative;
}

.stat-item {
    text-align: center;
    padding: 30px 20px;
}
.stat-item h5{
    color: white
}

.stat-number {
    font-size: 3rem;
    font-weight: bold;
    color: #DE6262;
    margin-bottom: 10px;
}

.testimonial-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    position: relative;
    margin-top: 30px;
}

.testimonial-card::before {
    content: '"';
    position: absolute;
    top: -10px;
    left: 30px;
    font-size: 4rem;
    color: #DE6262;
    font-family: serif;
}

.cta-section {
    background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
    padding: 80px 0;
}

.section-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 20px;
    color: #2C3E50;
}

.section-subtitle {
    font-size: 1.2rem;
    color: #6C757D;
    margin-bottom: 50px;
}

/* Pricing Section Styles */
.pricing-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    border: 2px solid #f0f0f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border-color: #DE6262;
}

.pricing-card.featured {
    border-color: #DE6262;
    transform: scale(1.05);
}

.pricing-card.featured:hover {
    transform: scale(1.05) translateY(-10px);
}

.popular-badge {
    position: absolute;
    top: 20px;
    right: -30px;
    background: linear-gradient(45deg, #DE6262, #E87A7A);
    color: white;
    padding: 5px 40px;
    font-size: 0.8rem;
    font-weight: 600;
    transform: rotate(45deg);
    text-align: center;
}

.pricing-header {
    margin-bottom: 30px;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2C3E50;
    margin-bottom: 15px;
}

.price-container {
    margin-bottom: 10px;
}

.price {
    font-size: 3rem;
    font-weight: 700;
    color: #DE6262;
}

.period {
    font-size: 1.2rem;
    color: #6C757D;
    font-weight: 500;
}

.pricing-body {
    margin-bottom: 30px;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    padding: 8px 0;
    font-size: 1rem;
    color: #495057;
}

.pricing-footer {
    margin-top: auto;
}

/* Billing Toggle */
.form-check-input:checked {
    background-color: #DE6262;
    border-color: #DE6262;
}

.form-check-input:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.25rem rgba(222, 98, 98, 0.25);
}

/* Responsive pricing cards */
@media (max-width: 992px) {
    .pricing-card.featured {
        transform: none;
        margin-top: 0;
    }

    .pricing-card.featured:hover {
        transform: translateY(-10px);
    }
}

@media (max-width: 768px) {
    .pricing-card {
        padding: 20px;
        margin-bottom: 20px;
    }

    .price {
        font-size: 2.5rem;
    }

    .popular-badge {
        font-size: 0.7rem;
        padding: 3px 35px;
    }
}
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="hero-section d-flex align-items-center">
    <div class="hero-pattern"></div>
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="text-white mb-5">
                    <h1 class="display-3 fw-bold mb-4" data-animate="fadeInUp">
                        <span style="color: #DE6262;">Complete AI-Powered</span><br><span style="color: #FFE4E1;">Healthcare Platform</span>
                    </h1>
                    <p class="lead mb-4 opacity-90" data-animate="fadeInUp" data-delay="200">
                        Revolutionary AI platform combining intelligent diagnosis, voice assistance, patient management, and professional online presence. Transform your medical practice with cutting-edge technology.
                    </p>
                    <div class="d-flex flex-wrap gap-3" data-animate="fadeInUp" data-delay="400">
                        <a href="/login" class="btn btn-theme-primary btn-lg">
                            <i class="fas fa-stethoscope me-2"></i>Start Diagnosis
                        </a>
                        <a href="#features" class="btn btn-theme-outline btn-lg">
                            <i class="fas fa-play me-2"></i>Learn More
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="position-relative" data-animate="fadeInRight">
                    <div class="d-inline-block position-relative">
                        <div class="bg-white rounded-circle p-5 shadow-lg" style="width: 300px; height: 300px; display: flex; align-items: center; justify-content: center;">
                            <i class="icon-medical-i-cardiology text-theme-primary" style="font-size: 120px;"></i>
                        </div>
                        <div class="position-absolute top-0 end-0 bg-white rounded-circle p-3 shadow">
                            <i class="fas fa-brain text-theme-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div class="position-absolute bottom-0 start-0 bg-white rounded-circle p-3 shadow">
                            <i class="fas fa-heart-pulse text-theme-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5" style="background: #F8F9FA;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Complete Healthcare Solution</h2>
            <p class="section-subtitle">Everything you need to run a modern medical practice</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h4 class="mb-3">AI-Powered Diagnosis</h4>
                    <p class="text-muted">Advanced GPT-4 powered analysis with voice transcription, manual diagnosis creation, and intelligent follow-up questions for comprehensive patient care.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h4 class="mb-3">Voice Assistant</h4>
                    <p class="text-muted">Hands-free consultation documentation with real-time speech-to-text, automatic chart filling, and AI-powered clinical analysis.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 class="mb-3">Patient Management</h4>
                    <p class="text-muted">Complete patient lifecycle management with appointment booking, case tracking, automated notifications, and review systems.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h4 class="mb-3">Professional Online Presence</h4>
                    <p class="text-muted">Customizable landing pages, blog management, live chat widgets, and patient testimonials to grow your practice online.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="mb-3">HIPAA Compliant Security</h4>
                    <p class="text-muted">Enterprise-grade encryption, secure data handling, role-based access control, and comprehensive audit trails for complete compliance.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4 class="mb-3">Multi-Channel Communication</h4>
                    <p class="text-muted">Automated email and SMS notifications, real-time chat, appointment reminders, and subscription management across all devices.</p>
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
            <p class="section-subtitle">Simple process for comprehensive healthcare management</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="mt-4">
                        <i class="fas fa-user-doctor text-theme-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="mb-3">Register & Setup</h4>
                        <p class="text-muted">Create your account, customize your profile, set up your professional landing page, and configure your practice preferences.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="mt-4">
                        <i class="fas fa-stethoscope text-theme-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="mb-3">Manage Patients</h4>
                        <p class="text-muted">Use AI diagnosis tools, voice assistant, appointment booking, and automated patient communication to streamline your practice.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="mt-4">
                        <i class="fas fa-chart-line text-theme-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="mb-3">Grow Your Practice</h4>
                        <p class="text-muted">Leverage professional landing pages, patient reviews, blog content, and analytics to expand your reach and improve patient outcomes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section py-5 text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">25K+</div>
                    <h5>AI Diagnoses Created</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">1,200+</div>
                    <h5>Healthcare Professionals</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">50K+</div>
                    <h5>Patient Appointments</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <h5>System Uptime</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Platform Highlights Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Platform Highlights</h2>
            <p class="section-subtitle">Advanced features that set us apart</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="mb-3">Enterprise Security</h4>
                    <p class="text-muted">HIPAA-compliant platform with end-to-end encryption, role-based access control, and comprehensive audit trails for complete data protection.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4 class="mb-3">Multi-Channel Communication</h4>
                    <p class="text-muted">Reach patients through SMS (Twilio, Plivo), automated email campaigns, live chat widgets, and appointment reminders across all devices.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5" style="background: #F8F9FA;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">What Healthcare Professionals Say</h2>
            <p class="section-subtitle">Trusted by medical professionals worldwide</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-6">
                <div class="testimonial-card">
                    <div class="mb-4">
                       <!-- <img src="https://via.placeholder.com/80x80/DE6262/FFFFFF?text=SA" alt="Dr. Sarah Ahmed" class="rounded-circle mb-3" width="80" height="80">-->
                        <h5 class="mb-1">Dr. Saif Al-Zawahrah</h5>
                        <small class="text-muted">Internal Medicine</small>
                    </div>
                    <p class="text-muted">This comprehensive AI platform has transformed my practice completely. From AI diagnosis to voice assistant and patient management - everything I need in one place. Patient satisfaction has increased dramatically.</p>
                </div>
            </div>
            <div class="col-lg-5 col-md-6">
                <div class="testimonial-card">
                    <div class="mb-4">
                    <!--    <img src="https://via.placeholder.com/80x80/DE6262/FFFFFF?text=KM" alt="Dr. Khaled Mansour" class="rounded-circle mb-3" width="80" height="80">-->
                        <h5 class="mb-1">Dr. Khaled Mansour</h5>
                        <small class="text-muted">Family Physician</small>
                    </div>
                    <p class="text-muted">The voice assistant and automated patient communication features have saved me hours daily. The professional landing page has brought in 40% more patients. Incredible value!</p>
                </div>
            </div>
        </div>
    </div>
</section>

@if($showPricingSection)
<!-- Pricing Information Section -->
<section id="pricing" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Custom Medical Solutions</h2>
            <p class="section-subtitle">Personalized pricing based on your practice needs</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="pricing-info-card text-center p-5" style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #f0f0f0;">
                    <div class="feature-icon mb-4">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3 class="mb-3">Complete Healthcare Platform</h3>
                    <p class="mb-4 text-muted">
                        Our comprehensive AI healthcare platform is customized for each medical practice. 
                        Each account receives personalized pricing based on practice size, specialty, feature requirements, and usage patterns.
                    </p>
                    
                    <div class="features-grid row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-center">
                                <i class="fas fa-check text-success me-3"></i>
                                <span>AI diagnosis & voice assistant</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-center">
                                <i class="fas fa-check text-success me-3"></i>
                                <span>Complete patient management</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-center">
                                <i class="fas fa-check text-success me-3"></i>
                                <span>Professional online presence</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-center">
                                <i class="fas fa-check text-success me-3"></i>
                                <span>Multi-channel communication</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-center">
                                <i class="fas fa-check text-success me-3"></i>
                                <span>HIPAA-compliant security</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-center">
                                <i class="fas fa-check text-success me-3"></i>
                                <span>24/7 platform support</span>
                            </div>
                        </div>
                    </div>

                    @guest
                        <a href="{{ route('contact') }}" class="btn btn-theme-primary btn-lg">
                            <i class="fas fa-envelope me-2"></i>
                            Contact Us for Custom Pricing
                        </a>
                        <p class="mt-3 text-muted small">
                            Get in touch to discuss your specific needs and receive a personalized quote
                        </p>
                    @endguest

                    @auth
                        <div class="user-pricing-info p-4 rounded" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #DE6262;">
                            <h4 class="text-theme-primary mb-3">Your Personalized Plan</h4>
                            @if(Auth::user()->monthlyInvoiceSetting && Auth::user()->monthlyInvoiceSetting->is_active)
                                @php
                                    $setting = Auth::user()->monthlyInvoiceSetting;
                                    $monthlyPrice = $setting->monthly_price ?? 0;
                                    $yearlyPrice = $setting->yearly_price ?? 0;
                                @endphp
                                <div class="pricing-options mb-3">
                                    <div class="row text-center">
                                        <div class="col-md-6">
                                            <div class="price-option p-3 rounded" style="background: rgba(222, 98, 98, 0.1);">
                                                <small class="text-muted d-block">Monthly</small>
                                                <span class="h4 text-theme-primary">${{ number_format($monthlyPrice, 0) }}</span>
                                                <small class="text-muted">/month</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="price-option p-3 rounded" style="background: rgba(222, 98, 98, 0.1);">
                                                <small class="text-muted d-block">Yearly</small>
                                                <span class="h4 text-theme-primary">${{ number_format($yearlyPrice, 0) }}</span>
                                                <small class="text-muted">/year</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted mb-3">Your personalized pricing - Choose monthly or yearly billing</p>
                                <a href="{{ route('subscription.manage') }}" class="btn btn-theme-primary">
                                    <i class="fas fa-cog me-2"></i>
                                    Manage Subscription
                                </a>
                            @else
                                <p class="text-muted mb-3">Your account is currently being set up. Please contact support for activation.</p>
                                <a href="{{ route('contact') }}" class="btn btn-theme-outline">
                                    <i class="fas fa-phone me-2"></i>
                                    Contact Support
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- For Patients Section -->
<section class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="pe-lg-5">
                    <h2 class="section-title text-start mb-4">
                        <i class="fas fa-user-injured text-theme-primary me-3"></i>
                        Are You a Patient?
                    </h2>
                    <p class="lead mb-4">
                        Find qualified doctors, book appointments, and manage your healthcare journey with ease.
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-search text-theme-primary me-3"></i>
                                <span>Find Doctors by Specialty</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-check text-theme-primary me-3"></i>
                                <span>Easy Online Booking</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-shield text-theme-primary me-3"></i>
                                <span>No Account Required</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star text-theme-primary me-3"></i>
                                <span>Leave Reviews</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('doctors.index') }}" class="btn btn-theme-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Find a Doctor
                        </a>
                        <a href="{{ route('patient.register') }}" class="btn btn-theme-outline btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Account creation is optional - you can book appointments as a guest
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                         alt="Patient Care" class="img-fluid rounded-3 shadow-lg">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient-overlay rounded-3"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="section-title">Ready to Transform Your Practice?</h2>
        <p class="section-subtitle">Join thousands of doctors already using AI-powered diagnosis</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="/login" class="btn btn-theme-primary btn-lg">
                <i class="fas fa-rocket me-2"></i>Start Free Trial
            </a>
            <a href="{{ route('contact') }}" class="btn btn-theme-outline btn-lg">
                <i class="fas fa-phone me-2"></i>Contact Sales
            </a>
        </div>
    </div>
</section>

<script>
// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all cards and sections
document.querySelectorAll('.feature-card, .step-card, .testimonial-card, .pricing-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'all 0.6s ease';
    observer.observe(el);
});

// Custom pricing system - no complex subscription handling needed here
document.addEventListener('DOMContentLoaded', function() {
    console.log('Custom pricing system loaded - individual pricing managed per user');
});
</script>
@endsection
