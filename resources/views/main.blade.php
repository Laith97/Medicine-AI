@extends('master')

@section('title', 'MedCura AI - Complete Healthcare Platform | Clinical Decision Support, Patient Management & Practice Growth')

@push('styles')
<style>
/* ==================== THEME COLORS ==================== */
.theme-primary { background-color: #DE6262 !important; }
.text-theme-primary { color: #DE6262 !important; }
.border-theme-primary { border-color: #DE6262 !important; }

/* ==================== MODERN BUTTONS ==================== */
.btn-theme-primary {
    background: linear-gradient(135deg, #DE6262 0%, #E87A7A 100%);
    border: none;
    color: white;
    padding: 14px 35px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 25px rgba(222, 98, 98, 0.35);
    position: relative;
    overflow: hidden;
}

.btn-theme-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn-theme-primary:hover::before {
    left: 100%;
}

.btn-theme-primary:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 35px rgba(222, 98, 98, 0.5);
    color: white;
}

.btn-theme-outline {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 14px 35px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-theme-outline:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: white;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
}

/* ==================== ANIMATED HERO SECTION ==================== */
.hero-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

/* Animated gradient background */
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

/* Floating particles effect */
.hero-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.hero-pattern::before,
.hero-pattern::after {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
    animation: float 15s infinite ease-in-out;
}

.hero-pattern::before {
    background: linear-gradient(135deg, #DE6262, #E87A7A);
    top: 20%;
    right: 10%;
    animation-delay: -5s;
}

.hero-pattern::after {
    background: linear-gradient(135deg, #4A90E2, #7B68EE);
    bottom: 20%;
    left: 10%;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}

/* ==================== GLASSMORPHISM CARDS ==================== */
.feature-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 45px 35px;
    text-align: center;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(222, 98, 98, 0.1);
    height: 100%;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(222,98,98,0.05), transparent);
    transition: left 0.6s;
}

.feature-card:hover::before {
    left: 100%;
}

.feature-card:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 25px 50px rgba(222, 98, 98, 0.2);
    border-color: rgba(222, 98, 98, 0.3);
}

/* Animated gradient icon */
.feature-icon {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #DE6262 0%, #E87A7A 50%, #FF9A9A 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    color: white;
    font-size: 2.2rem;
    position: relative;
    animation: iconFloat 3s ease-in-out infinite;
    box-shadow: 0 10px 30px rgba(222, 98, 98, 0.3);
}

.feature-icon::after {
    content: '';
    position: absolute;
    inset: -5px;
    border-radius: 50%;
    background: linear-gradient(135deg, #DE6262, #E87A7A);
    opacity: 0.3;
    filter: blur(15px);
    z-index: -1;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* ==================== STEP CARDS WITH ANIMATION ==================== */
.step-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 40px 30px;
    text-align: center;
    position: relative;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid rgba(222, 98, 98, 0.1);
}

.step-card:hover {
    transform: translateY(-10px) scale(1.03);
    box-shadow: 0 20px 40px rgba(222, 98, 98, 0.2);
    border-color: rgba(222, 98, 98, 0.4);
}

.step-number {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #DE6262, #E87A7A);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.4rem;
    box-shadow: 0 8px 20px rgba(222, 98, 98, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 8px 20px rgba(222, 98, 98, 0.4); }
    50% { box-shadow: 0 8px 30px rgba(222, 98, 98, 0.6); }
}

/* ==================== ANIMATED STATS SECTION ==================== */
.stats-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    position: relative;
    overflow: hidden;
}

.stats-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(222,98,98,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
    background-size: cover;
    opacity: 0.3;
}

.stat-item {
    text-align: center;
    padding: 35px 25px;
    transition: all 0.4s ease;
}

.stat-item:hover {
    transform: scale(1.05);
}

.stat-item h5 {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    font-size: 1.1rem;
}

.stat-number {
    font-size: 3.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #DE6262, #E87A7A, #FF9A9A);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
    animation: numberGlow 2s ease-in-out infinite;
}

@keyframes numberGlow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

/* ==================== TESTIMONIAL CARDS ==================== */
.testimonial-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(255,255,255,0.95) 100%);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
    position: relative;
    margin-top: 35px;
    border: 2px solid rgba(222, 98, 98, 0.1);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.testimonial-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(222, 98, 98, 0.15);
    border-color: rgba(222, 98, 98, 0.3);
}

.testimonial-card::before {
    content: '"';
    position: absolute;
    top: -15px;
    left: 35px;
    font-size: 5rem;
    background: linear-gradient(135deg, #DE6262, #E87A7A);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-family: serif;
    font-weight: bold;
}

/* ==================== PREMIUM PRICING CARDS ==================== */
.pricing-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(255,255,255,0.95) 100%);
    backdrop-filter: blur(20px);
    border-radius: 28px;
    padding: 40px 35px;
    border: 2px solid rgba(222, 98, 98, 0.15);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.pricing-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #DE6262, #E87A7A, #FF9A9A);
    transform: scaleX(0);
    transition: transform 0.5s;
}

.pricing-card:hover::before {
    transform: scaleX(1);
}

.pricing-card:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 25px 60px rgba(222, 98, 98, 0.25);
    border-color: rgba(222, 98, 98, 0.4);
}

.pricing-card.featured {
    border-color: #DE6262;
    transform: scale(1.05);
    box-shadow: 0 20px 50px rgba(222, 98, 98, 0.3);
}

.pricing-card.featured:hover {
    transform: scale(1.05) translateY(-15px);
}

.popular-badge {
    position: absolute;
    top: 25px;
    right: -35px;
    background: linear-gradient(135deg, #DE6262, #E87A7A);
    color: white;
    padding: 6px 45px;
    font-size: 0.85rem;
    font-weight: 700;
    transform: rotate(45deg);
    text-align: center;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.4);
    letter-spacing: 0.5px;
}

.plan-name {
    font-size: 1.8rem;
    font-weight: 800;
    color: #2C3E50;
    margin-bottom: 20px;
}

.price {
    font-size: 3.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #DE6262, #E87A7A);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.period {
    font-size: 1.3rem;
    color: #6C757D;
    font-weight: 500;
}

.feature-list li {
    padding: 12px 0;
    font-size: 1.05rem;
    color: #495057;
    transition: all 0.3s ease;
}

.feature-list li:hover {
    transform: translateX(5px);
    color: #DE6262;
}

/* ==================== SCROLL ANIMATIONS ==================== */
[data-animate] {
    opacity: 0;
    animation-fill-mode: forwards;
}

[data-animate="fadeInUp"] {
    animation: fadeInUp 0.8s ease-out forwards;
}

[data-animate="fadeInLeft"] {
    animation: fadeInLeft 0.8s ease-out forwards;
}

[data-animate="fadeInRight"] {
    animation: fadeInRight 0.8s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* ==================== SECTION TITLES ==================== */
.section-title {
    font-size: 2.8rem;
    font-weight: 800;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #2C3E50, #34495E);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.section-subtitle {
    font-size: 1.3rem;
    color: #6C757D;
    margin-bottom: 60px;
    font-weight: 400;
}

/* ==================== BILLING TOGGLE ==================== */
.billing-period-label {
    padding: 10px 25px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 600;
    font-size: 15px;
}

.billing-period-label:hover {
    transform: scale(1.05);
}

/* ==================== RESPONSIVE DESIGN ==================== */
@media (max-width: 992px) {
    .pricing-card.featured {
        transform: none;
        margin-top: 0;
    }

    .pricing-card.featured:hover {
        transform: translateY(-10px);
    }
    
    .section-title {
        font-size: 2.2rem;
    }
    
    .stat-number {
        font-size: 2.8rem;
    }
}

@media (max-width: 768px) {
    .pricing-card {
        padding: 25px;
        margin-bottom: 25px;
    }

    .price {
        font-size: 2.8rem;
    }

    .section-title {
        font-size: 1.9rem;
    }
    
    .feature-icon {
        width: 70px;
        height: 70px;
        font-size: 1.8rem;
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
                        <span style="color: #DE6262;">Clinical Decision</span><br><span style="color: #FFE4E1;">Electronic Medical Records</span>
                    </h1>
                    <p class="lead mb-4 opacity-90" data-animate="fadeInUp" data-delay="200">
                        Transform healthcare delivery with our comprehensive EMR system enhanced by advanced analytics. Complete patient management, clinical decision support, and seamless workflow integration in one platform.
                    </p>
                    <div class="d-flex flex-wrap gap-3" data-animate="fadeInUp" data-delay="400">
                        <a href="{{ route('register.doctor') }}" class="btn btn-theme-primary btn-lg">
                            <i class="fas fa-stethoscope me-2"></i>Start Free Trial
                        </a>
                        <a href="#features" class="btn btn-theme-outline btn-lg">
                            <i class="fas fa-play me-2"></i>Explore Features
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="position-relative" data-animate="fadeInRight">
                    <div class="d-inline-block position-relative">
                        <div class="bg-white rounded-circle p-5 shadow-lg" style="width: 300px; height: 300px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-medical text-theme-primary" style="font-size: 120px;"></i>
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
            <h2 class="section-title">Comprehensive EMR System Features</h2>
            <p class="section-subtitle">Complete healthcare management with advanced analytics capabilities</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h4 class="mb-3">Clinical Decision Support</h4>
                    <p class="text-muted">Advanced clinical tools analyze patient symptoms, medical history, and test results to assist in clinical decision-making with comprehensive analysis.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h4 class="mb-3">Comprehensive EMR System</h4>
                    <p class="text-muted">Complete electronic medical records with patient history, treatments, prescriptions, lab results, and imaging in one secure, accessible platform.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h4 class="mb-3">AI Voice Transcription</h4>
                    <p class="text-muted">Real-time speech-to-text conversion during consultations, automatically generating structured clinical notes for seamless documentation.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h4 class="mb-3">Smart Scheduling</h4>
                    <p class="text-muted">Intelligent appointment management with automated reminders, conflict detection, and optimization for maximum practice efficiency.</p>
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
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="mb-3">AI Analytics Dashboard</h4>
                    <p class="text-muted">Real-time insights and predictive analytics to optimize healthcare delivery, resource allocation, and patient outcomes.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h4 class="mb-3">Real-time Notifications</h4>
                    <p class="text-muted">Instant updates and alerts for appointments, diagnoses, reviews, and system events through WebSocket-based notifications.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h4 class="mb-3">Patient Management</h4>
                    <p class="text-muted">Complete patient lifecycle management with registration, profile management, appointment booking, and communication tools.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h4 class="mb-3">Billing & Claims</h4>
                    <p class="text-muted">Automated medical billing, insurance verification, and claim submission with integrated payment processing.</p>
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
                        <p class="text-muted">Use clinical decision support tools, voice assistant, appointment booking, and automated patient communication to streamline your practice.</p>
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
                    <div class="stat-number">50K+</div>
                    <h5>Clinical Insights Provided</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <h5>Electronic Records Managed</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">75K+</div>
                    <h5>Patient Interactions</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <h5>System Uptime</h5>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">4.8★</div>
                    <h5>Patient Satisfaction</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">40%</div>
                    <h5>Avg. Time Saved</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">300+</div>
                    <h5>Medical Practices</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <h5>Support Available</h5>
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
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h4 class="mb-3">Cloud-Based Accessibility</h4>
                    <p class="text-muted">Access patient records and tools from anywhere with secure cloud infrastructure and multi-device synchronization.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h4 class="mb-3">Real-time Synchronization</h4>
                    <p class="text-muted">Multi-device support with real-time updates across all platforms for seamless workflow continuity.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h4 class="mb-3">Workflow Automation</h4>
                    <p class="text-muted">Automated processes for appointment scheduling, follow-up reminders, insurance verification, and billing workflows.</p>
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
                        <h5 class="mb-1">Dr. Sarah Al-Zawahrah</h5>
                        <small class="text-muted">Cardiology Practice</small>
                    </div>
                    <p class="text-muted">"The advanced EMR system has revolutionized our patient care. The clinical decision support and voice transcription capabilities have reduced documentation time by 60% while improving accuracy. The comprehensive patient records are always accessible and well-organized."</p>
                </div>
            </div>
            <div class="col-lg-5 col-md-6">
                <div class="testimonial-card">
                    <div class="mb-4">
                    <!--    <img src="https://via.placeholder.com/80x80/DE6262/FFFFFF?text=KM" alt="Dr. Khaled Mansour" class="rounded-circle mb-3" width="80" height="80">-->
                        <h5 class="mb-1">Dr. Khaled Mansour</h5>
                        <small class="text-muted">Family Medicine</small>
                    </div>
                    <p class="text-muted">"The integration of clinical decision support with our EMR system has improved our diagnostic accuracy and patient outcomes. The system's predictive analytics help us identify at-risk patients before complications arise. Essential for modern medical practice."</p>
                </div>
            </div>
        </div>
    </div>
</section>

@if($showPricingSection)
<!-- Pricing Plans Section -->
<section id="pricing" class="py-5 bg-white">
    <div class="container">
        <!-- Billing Toggle for All Users -->
        <div class="text-center mb-5">
            <div class="d-inline-flex align-items-center p-2 rounded-pill" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                <span class="px-3 py-2 billing-period-label active" id="monthly-label" style="border-radius: 20px; cursor: pointer; transition: all 0.3s ease; background: #DE6262; color: white;">Monthly</span>
                <span class="px-3 py-2 billing-period-label" id="yearly-label" style="border-radius: 20px; cursor: pointer; transition: all 0.3s ease; margin-left: 5px;">Yearly <small class="text-success">(Save 17%)</small></span>
            </div>
        </div>



        <!-- 3 Pricing Plans -->
        <div class="row justify-content-center g-4">
            @if(isset($pricingPlans) && !empty($pricingPlans))
                @foreach($pricingPlans as $planKey => $plan)
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card h-100 d-flex flex-column {{ $plan['is_featured'] ? 'featured' : '' }}">
                        @if($plan['is_featured'])
                            <div class="popular-badge">Most Popular</div>
                        @endif

                        <div class="pricing-header">
                            <h3 class="plan-name">{{ $plan['name'] }}</h3>
                            <p class="text-muted mb-3">{{ $plan['description'] }}</p>
                            <div class="price-container">
                                @if($plan['price_monthly'] == 0)
                                    <div class="price-display">
                                        <span class="price">Free</span>
                                    </div>
                                @else
                                    @guest
                                        <div class="price-display monthly-price">
                                            <span class="price">${{ $plan['price_monthly'] }}</span>
                                            <span class="period">/month</span>
                                        </div>
                                        <div class="price-display yearly-price" style="display: none;">
                                            <span class="price">${{ $plan['price_yearly'] }}</span>
                                            <span class="period">/year</span>
                                            <div class="mt-2">
                                                <small class="text-success">Save ${{ ($plan['price_monthly'] * 12) - $plan['price_yearly'] }}</small>
                                            </div>
                                        </div>
                                    @endguest
                                    @auth
                                        <div class="price-display">
                                            <span class="price">${{ $plan['price_monthly'] }}</span>
                                            <span class="period">/month</span>
                                        </div>
                                    @endauth
                                @endif
                            </div>
                        </div>

                        <div class="pricing-body flex-grow-1">
                            <ul class="feature-list">
                                @foreach($plan['features'] as $feature)
                                <li><i class="fas fa-check text-success me-2"></i>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="pricing-footer">
                            <a href="{{ $plan['button_url'] }}" class="btn {{ $plan['is_featured'] ? 'btn-theme-primary' : 'btn-theme-outline' }} btn-lg w-100">
                                {{ $plan['button_text'] }}
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h5>Pricing Plans</h5>
                        <p>Our pricing plans are being loaded. Please refresh the page.</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Pricing Display with Toggle -->
        <!-- <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="pricing-card featured text-center">
                    <div class="popular-badge">Most Popular</div>
                    <div class="pricing-header">
                        <h3 class="plan-name">Professional Plan</h3>
                        <p class="text-muted">Complete healthcare management solution</p>
                        <div class="price-container">
                            <div class="price-display monthly-price">
                                <span class="price">${{ $professionalMonthly ?? 30 }}</span>
                                <span class="period">/month</span>
                            </div>
                            <div class="price-display yearly-price" style="display: none;">
                                <span class="price">${{ $professionalYearly ?? 300 }}</span>
                                <span class="period">/year</span>
                                <div class="mt-2">
                                    <small class="text-success">Save ${{ (($professionalMonthly ?? 30) * 12) - ($professionalYearly ?? 300) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pricing-body">
                        <ul class="feature-list text-start">
                            <li><i class="fas fa-check text-success me-2"></i>Comprehensive Diagnosis</li>
                            <li><i class="fas fa-check text-success me-2"></i>Voice Assistant</li>
                            <li><i class="fas fa-check text-success me-2"></i>Patient Management</li>
                            <li><i class="fas fa-check text-success me-2"></i>Practice Analytics</li>
                            <li><i class="fas fa-check text-success me-2"></i>Professional Landing Page</li>
                            <li><i class="fas fa-check text-success me-2"></i>24/7 Support</li>
                        </ul>
                    </div>

                    <div class="pricing-footer">
                        <a href="{{ route('register.doctor') }}" class="btn btn-theme-primary btn-lg w-100">
                            Start Free Trial
                        </a>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="text-center mt-4">
            <p class="text-muted">
                <i class="fas fa-shield-alt text-success me-2"></i>
                HIPAA compliant • No credit card required • Cancel anytime
            </p>
        </div>
    </div>
</section>

<script>
// Pricing billing toggle for guests
document.addEventListener('DOMContentLoaded', function() {
    const monthlyLabel = document.getElementById('monthly-label');
    const yearlyLabel = document.getElementById('yearly-label');
    const monthlyPrices = document.querySelectorAll('.monthly-price');
    const yearlyPrices = document.querySelectorAll('.yearly-price');
    const pricingCards = document.querySelectorAll('.pricing-card');

    if (monthlyLabel && yearlyLabel) {
        monthlyLabel.addEventListener('click', function() {
            // Switch to monthly
            monthlyLabel.style.background = '#DE6262';
            monthlyLabel.style.color = 'white';
            yearlyLabel.style.background = 'transparent';
            yearlyLabel.style.color = '#6C757D';

            monthlyPrices.forEach(price => price.style.display = 'block');
            yearlyPrices.forEach(price => price.style.display = 'none');

            // Update button links to monthly
            pricingCards.forEach(card => {
                const button = card.querySelector('.pricing-footer a');
                if (button) {
                    const currentUrl = button.getAttribute('href');
                    const urlWithoutBilling = currentUrl.split('&billing=')[0];
                    button.setAttribute('href', urlWithoutBilling + '&billing=monthly');
                }
            });
        });

        yearlyLabel.addEventListener('click', function() {
            // Switch to yearly
            yearlyLabel.style.background = '#DE6262';
            yearlyLabel.style.color = 'white';
            monthlyLabel.style.background = 'transparent';
            monthlyLabel.style.color = '#6C757D';

            monthlyPrices.forEach(price => price.style.display = 'none');
            yearlyPrices.forEach(price => price.style.display = 'block');

            // Update button links to yearly
            pricingCards.forEach(card => {
                const button = card.querySelector('.pricing-footer a');
                if (button) {
                    const currentUrl = button.getAttribute('href');
                    const urlWithoutBilling = currentUrl.split('&billing=')[0];
                    button.setAttribute('href', urlWithoutBilling + '&billing=yearly');
                }
            });
        });
    }
});
</script>

<!-- Pricing Cards CSS -->
<style>
.pricing-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.pricing-card.featured {
    border-color: #DE6262;
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.05), rgba(222, 98, 98, 0.02));
    transform: scale(1.05);
    z-index: 1;
}

.popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #DE6262;
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3);
}

.pricing-header {
    text-align: center;
    margin-bottom: 2rem;
}

.plan-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.price-display .price {
    font-size: 2.5rem;
    font-weight: 800;
    color: #DE6262;
    line-height: 1;
}

.price-display .period {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 500;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}

.feature-list li {
    padding: 0.5rem 0;
    color: #4a5568;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.feature-list li i {
    font-size: 1rem;
    margin-right: 0.75rem;
}

.pricing-footer {
    margin-top: auto;
}

.btn-theme-primary {
    background: #DE6262;
    border-color: #DE6262;
    color: white;
    font-weight: 600;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-theme-primary:hover {
    background: #c55555;
    border-color: #c55555;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(222, 98, 98, 0.3);
}

.btn-theme-outline {
    background: transparent;
    border: 2px solid #DE6262;
    color: #DE6262;
    font-weight: 600;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-theme-outline:hover {
    background: #DE6262;
    border-color: #DE6262;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(222, 98, 98, 0.3);
}

@media (max-width: 768px) {
    .pricing-card.featured {
        transform: none;
        margin-bottom: 2rem;
    }

    .price-display .price {
        font-size: 2rem;
    }
}
</style>
@endif

<!-- For Patients Section -->
<section class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="pe-lg-5">
                    <h2 class="section-title text-start mb-4">
                        <i class="fas fa-user-injured text-theme-primary me-3"></i>
                        For Patients: Seamless Healthcare Experience
                    </h2>
                    <p class="lead mb-4">
                        Access world-class healthcare with advanced diagnostics, easy appointment booking, and comprehensive patient management - all in one secure platform.
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-search text-theme-primary me-3"></i>
                                <span>Find Doctors by Specialty & Location</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-check text-theme-primary me-3"></i>
                                <span>Instant Online Booking</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-medical text-theme-primary me-3"></i>
                                <span>Access Your Medical Records</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-comments text-theme-primary me-3"></i>
                                <span>Direct Doctor Communication</span>
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
                                <span>Rate & Review Doctors</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('doctors.index') }}" class="btn btn-theme-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Find a Doctor
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-theme-outline btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Free Account
                        </a>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-shield-alt text-success me-1"></i>
                            HIPAA-compliant platform • No registration required for booking • 24/7 secure access to your health data
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

<!-- For Healthcare Providers Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-center mb-4 mb-lg-0">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1551190822-a9333d879b1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                         alt="Healthcare Professional" class="img-fluid rounded-3 shadow-lg">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient-overlay rounded-3"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ps-lg-5">
                    <h2 class="section-title text-start mb-4">
                        <i class="fas fa-user-md text-theme-primary me-3"></i>
                        For Healthcare Providers: Complete Practice Management
                    </h2>
                    <p class="lead mb-4">
                        Transform your practice with advanced diagnostics, comprehensive patient management, and business growth tools designed specifically for modern healthcare professionals.
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-stethoscope text-theme-primary me-3"></i>
                                <span>Advanced Diagnostics</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users-cog text-theme-primary me-3"></i>
                                <span>Staff Management</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-line text-theme-primary me-3"></i>
                                <span>Practice Analytics</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-blog text-theme-primary me-3"></i>
                                <span>Content Marketing</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-prescription text-theme-primary me-3"></i>
                                <span>Digital Prescriptions</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-dollar-sign text-theme-primary me-3"></i>
                                <span>Automated Billing</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('register.doctor') }}" class="btn btn-theme-primary btn-lg">
                            <i class="fas fa-stethoscope me-2"></i>Start Free Trial
                        </a>
                        <a href="{{ route('contact') }}" class="btn btn-theme-outline btn-lg">
                            <i class="fas fa-phone me-2"></i>Schedule Demo
                        </a>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-clock text-warning me-1"></i>
                            14-day free trial • No setup fees • Cancel anytime • Enterprise-grade security
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="section-title">Ready to Transform Your Practice?</h2>
        <p class="section-subtitle">Join thousands of healthcare professionals using advanced EMR technology</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            @auth
                <a href="{{ route('subscription.pricing') }}" class="btn btn-theme-primary btn-lg">
                    <i class="fas fa-file-medical me-2"></i>Get EMR Access
                </a>
            @else
                <a href="/login" class="btn btn-theme-primary btn-lg">
                    <i class="fas fa-file-medical me-2"></i>Get EMR Access
                </a>
            @endauth
            <a href="{{ route('contact') }}" class="btn btn-theme-outline btn-lg">
                <i class="fas fa-calendar-check me-2"></i>Schedule Demo
            </a>
            <a href="{{ route('contact') }}" class="btn btn-theme-outline btn-lg btn-lg-custom">
                <i class="fas fa-calendar-check me-2"></i>Book Live Demo
            </a>
            <a href="{{ route('doctors.index') }}" class="btn btn-outline-light btn-lg btn-lg-custom">
                <i class="fas fa-search me-2"></i>Find a Doctor
            </a>
        </div>
        <div class="mt-4">
            <p class="text-white-50 mb-2">
                <i class="fas fa-shield-alt text-success me-2"></i>
                HIPAA Compliant • 99.9% Uptime • 24/7 Support
            </p>
            <p class="text-white-50 small">
                No credit card required • Cancel anytime • Enterprise-grade security
            </p>
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

// Pricing billing toggle for guests
document.addEventListener('DOMContentLoaded', function() {
    const monthlyLabel = document.getElementById('monthly-label');
    const yearlyLabel = document.getElementById('yearly-label');
    const monthlyPrices = document.querySelectorAll('.monthly-price');
    const yearlyPrices = document.querySelectorAll('.yearly-price');

    if (monthlyLabel && yearlyLabel) {
        monthlyLabel.addEventListener('click', function() {
            // Switch to monthly
            monthlyLabel.style.background = '#DE6262';
            monthlyLabel.style.color = 'white';
            yearlyLabel.style.background = 'transparent';
            yearlyLabel.style.color = '#6C757D';

            monthlyPrices.forEach(price => price.style.display = 'block');
            yearlyPrices.forEach(price => price.style.display = 'none');
        });

        yearlyLabel.addEventListener('click', function() {
            // Switch to yearly
            yearlyLabel.style.background = '#DE6262';
            yearlyLabel.style.color = 'white';
            monthlyLabel.style.background = 'transparent';
            monthlyLabel.style.color = '#6C757D';

            monthlyPrices.forEach(price => price.style.display = 'none');
            yearlyPrices.forEach(price => price.style.display = 'block');
        });
    }
});

// ==================== SCROLL ANIMATIONS ====================
// Intersection Observer for scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.animation = entry.target.dataset.animate + ' 0.8s ease-out forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all animated elements
document.addEventListener('DOMContentLoaded', () => {
    // Add animations to elements
    document.querySelectorAll('[data-animate]').forEach(el => {
        observer.observe(el);
    });

    // Feature cards animation
    document.querySelectorAll('.feature-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.dataset.animate = 'fadeInUp';
        card.style.animationDelay = `${index * 0.1}s`;
        observer.observe(card);
    });

    // Step cards animation
    document.querySelectorAll('.step-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.dataset.animate = 'fadeInUp';
        card.style.animationDelay = `${index * 0.15}s`;
        observer.observe(card);
    });

    // Testimonial cards animation
    document.querySelectorAll('.testimonial-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.dataset.animate = index % 2 === 0 ? 'fadeInLeft' : 'fadeInRight';
        card.style.animationDelay = `${index * 0.2}s`;
        observer.observe(card);
    });

    // Pricing cards animation
    document.querySelectorAll('.pricing-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.dataset.animate = 'fadeInUp';
        card.style.animationDelay = `${index * 0.15}s`;
        observer.observe(card);
    });

    // ==================== COUNTER ANIMATION ====================
    const animateCounter = (element) => {
        const target = parseInt(element.textContent.replace(/[^0-9.]/g, ''));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        const suffix = element.textContent.replace(/[0-9.]/g, '');

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current) + suffix;
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target + suffix;
            }
        };

        updateCounter();
    };

    // Stats counter animation
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumber = entry.target.querySelector('.stat-number');
                if (statNumber && !statNumber.dataset.animated) {
                    statNumber.dataset.animated = 'true';
                    animateCounter(statNumber);
                }
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stat-item').forEach(item => {
        statsObserver.observe(item);
    });

    // ==================== PARALLAX EFFECT ====================
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = heroSection.querySelectorAll('.hero-pattern, .hero-section::before');
            
            if (scrolled < heroSection.offsetHeight) {
                heroSection.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    }

    // ==================== SMOOTH SCROLL ====================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // ==================== BUTTON RIPPLE EFFECT ====================
    document.querySelectorAll('.btn-theme-primary, .btn-theme-outline').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.className = 'ripple';

            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    });
});

// Add ripple effect styles
const style = document.createElement('style');
style.textContent = `
    .btn-theme-primary .ripple,
    .btn-theme-outline .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>
@endsection
