@extends('layouts.landing-page')

@section('title', $landingPage->page_title ?: $doctor->user->name . ' - Medical Professional')
@section('description', $landingPage->page_description ?: 'Book an appointment with ' . $doctor->user->name)

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-color: {{ $landingPage->colors['primary'] ?? '#0f766e' }};
    --secondary-color: {{ $landingPage->colors['secondary'] ?? '#64748b' }};
    --accent-color: {{ $landingPage->colors['accent'] ?? '#059669' }};
    --medical-blue: #1e40af;
    --medical-green: #059669;
    --medical-teal: #0f766e;
    --warm-white: #fefefe;
    --soft-gray: #f8fafc;
    --text-dark: #1e293b;
    --text-light: #64748b;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
    background: var(--warm-white);
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Merriweather', serif;
    font-weight: 700;
    line-height: 1.2;
    color: var(--text-dark);
}

/* Medical-themed Navigation */
.medical-navbar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(15, 118, 110, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    padding: 1rem 0;
}

.medical-navbar.scrolled {
    background: rgba(255, 255, 255, 0.98);
    padding: 0.5rem 0;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.navbar-brand {
    font-family: 'Merriweather', serif;
    font-weight: 700;
    font-size: 1.75rem;
    color: var(--primary-color) !important;
    display: flex;
    align-items: center;
}

.brand-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    box-shadow: 0 4px 15px rgba(15, 118, 110, 0.3);
}

.navbar-nav .nav-link {
    font-weight: 500;
    color: var(--text-dark) !important;
    margin: 0 0.5rem;
    padding: 0.75rem 1.25rem !important;
    border-radius: 25px;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: var(--primary-color) !important;
    background: rgba(15, 118, 110, 0.1);
    transform: translateY(-2px);
}

.navbar-nav .nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.navbar-nav .nav-link:hover::after,
.navbar-nav .nav-link.active::after {
    width: 80%;
}

/* Medical Hero Section */
.medical-hero {
    background: linear-gradient(135deg,
        rgba(15, 118, 110, 0.95) 0%,
        rgba(5, 150, 105, 0.9) 50%,
        rgba(30, 64, 175, 0.85) 100%),
        url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3') center/cover;
    min-height: 100vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.medical-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M30 30c0-11.046-8.954-20-20-20s-20 8.954-20 20 8.954 20 20 20 20-8.954 20-20zm0 0c0 11.046 8.954 20 20 20s20-8.954 20-20-8.954-20-20-20-20 8.954-20 20z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    margin-bottom: 2rem;
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
}

.hero-title {
    font-size: 4rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1.5rem;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    line-height: 1.1;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 3rem;
    max-width: 600px;
    line-height: 1.6;
}

.hero-stats {
    display: flex;
    gap: 3rem;
    margin-bottom: 3rem;
}

.stat-item {
    text-align: center;
    color: white;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    display: block;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn-medical-primary {
    background: white;
    color: var(--primary-color);
    border: 2px solid white;
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.btn-medical-primary:hover {
    background: transparent;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
}

.btn-medical-secondary {
    background: transparent;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.5);
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-medical-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
    color: white;
    transform: translateY(-3px);
}

/* Medical Cards */
.medical-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(15, 118, 110, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.medical-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.medical-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

.medical-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 25px rgba(15, 118, 110, 0.3);
}

.medical-icon i {
    font-size: 1.8rem;
    color: white;
}

/* Trust Indicators */
.trust-section {
    background: var(--soft-gray);
    padding: 5rem 0;
}

.trust-badge {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.trust-badge:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
}

.trust-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--medical-blue), var(--medical-teal));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

/* Medical Timeline */
.medical-timeline {
    position: relative;
    padding: 2rem 0;
}

.medical-timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
    transform: translateX(-50%);
}

.timeline-item {
    position: relative;
    margin-bottom: 3rem;
}

.timeline-item:nth-child(odd) .timeline-content {
    margin-right: calc(50% + 2rem);
    text-align: right;
}

.timeline-item:nth-child(even) .timeline-content {
    margin-left: calc(50% + 2rem);
}

.timeline-marker {
    position: absolute;
    left: 50%;
    top: 1rem;
    width: 20px;
    height: 20px;
    background: var(--primary-color);
    border: 4px solid white;
    border-radius: 50%;
    transform: translateX(-50%);
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.2);
}

.timeline-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

/* Medical Footer */
.medical-footer {
    background: linear-gradient(135deg, var(--text-dark) 0%, #334155 100%);
    color: white;
    padding: 4rem 0 2rem;
    position: relative;
}

.medical-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
}

.footer-section h5 {
    color: white;
    margin-bottom: 1.5rem;
    font-family: 'Merriweather', serif;
}

.footer-link {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    display: block;
    margin-bottom: 0.5rem;
}

.footer-link:hover {
    color: var(--accent-color);
    transform: translateX(5px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }

    .hero-stats {
        flex-direction: column;
        gap: 1.5rem;
        text-align: center;
    }

    .hero-cta {
        flex-direction: column;
        align-items: stretch;
    }

    .medical-timeline::before {
        left: 2rem;
    }

    .timeline-item:nth-child(odd) .timeline-content,
    .timeline-item:nth-child(even) .timeline-content {
        margin-left: 4rem;
        margin-right: 0;
        text-align: left;
    }

    .timeline-marker {
        left: 2rem;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .medical-card,
    .trust-badge {
        border: 2px solid var(--text-dark);
    }
}

/* Print styles */
@media print {
    .medical-navbar,
    .hero-cta,
    .floating-cta {
        display: none !important;
    }

    .medical-hero {
        background: white !important;
        color: black !important;
        min-height: auto;
    }
}
</style>
@endpush

@section('content')
<!-- Medical Navigation -->
<nav class="navbar navbar-expand-lg medical-navbar fixed-top" id="medicalNavbar">
    <div class="container">
        <a class="navbar-brand" href="#home">
            <div class="brand-icon">
                <i class="fas fa-heartbeat text-white"></i>
            </div>
            {{ $doctor->user->name }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#experience">Experience</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#testimonials">Reviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-medical-primary text-white px-4 ms-2" href="#appointments">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Book Appointment
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Medical Hero Section -->
<section id="home" class="medical-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                    <div class="hero-badge">
                        <i class="fas fa-award me-2"></i>
                        {{ $doctor->specialty->name ?? 'Medical Professional' }}
                    </div>

                    <h1 class="hero-title">
                        Exceptional Healthcare<br>
                        <span style="color: var(--accent-color);">You Can Trust</span>
                    </h1>

                    <p class="hero-subtitle">
                        {{ $landingPage->tagline ?: 'Providing comprehensive medical care with a personal touch. Your health and well-being are my top priorities.' }}
                    </p>

                    <div class="hero-stats" data-aos="fade-up" data-aos-delay="200">
                        <div class="stat-item">
                            <span class="stat-number">{{ $doctor->experience_years ?? '10' }}+</span>
                            <span class="stat-label">Years Experience</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ $reviews->count() > 0 ? $reviews->count() : '500' }}+</span>
                            <span class="stat-label">Happy Patients</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ $reviews->avg('rating') ? number_format($reviews->avg('rating'), 1) : '4.9' }}</span>
                            <span class="stat-label">Patient Rating</span>
                        </div>
                    </div>

                    <div class="hero-cta" data-aos="fade-up" data-aos-delay="400">
                        <a href="#appointments" class="btn-medical-primary">
                            <i class="fas fa-calendar-check me-2"></i>
                            Schedule Consultation
                        </a>
                        <a href="#about" class="btn-medical-secondary">
                            <i class="fas fa-user-md me-2"></i>
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="hero-image" data-aos="fade-left" data-aos-delay="600">
                    @if($doctor->user->profile_photo_path)
                        <img src="{{ Storage::url($doctor->user->profile_photo_path) }}"
                             alt="{{ $doctor->user->name }}"
                             class="img-fluid rounded-4 shadow-lg"
                             style="border: 5px solid rgba(255, 255, 255, 0.2);">
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust Indicators -->
<section class="trust-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="trust-badge">
                    <div class="trust-icon">
                        <i class="fas fa-certificate text-white"></i>
                    </div>
                    <h5>Board Certified</h5>
                    <p class="text-muted mb-0">Licensed medical professional</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="trust-badge">
                    <div class="trust-icon">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <h5>HIPAA Compliant</h5>
                    <p class="text-muted mb-0">Your privacy is protected</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="trust-badge">
                    <div class="trust-icon">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <h5>Same Day Appointments</h5>
                    <p class="text-muted mb-0">Quick scheduling available</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="trust-badge">
                    <div class="trust-icon">
                        <i class="fas fa-video text-white"></i>
                    </div>
                    <h5>Telemedicine</h5>
                    <p class="text-muted mb-0">Virtual consultations available</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-content">
                    <h2 class="mb-4">About {{ $doctor->user->name }}</h2>
                    <p class="lead mb-4">
                        {{ $landingPage->about_text ?: $doctor->bio ?: 'Dedicated to providing exceptional healthcare with compassion and expertise.' }}
                    </p>

                    @if($doctor->education || $doctor->specialty)
                    <div class="credentials mb-4">
                        @if($doctor->education)
                        <div class="credential-item mb-3">
                            <i class="fas fa-graduation-cap text-primary me-3"></i>
                            <strong>Education:</strong> {{ $doctor->education }}
                        </div>
                        @endif
                        @if($doctor->specialty)
                        <div class="credential-item mb-3">
                            <i class="fas fa-stethoscope text-primary me-3"></i>
                            <strong>Specialty:</strong> {{ $doctor->specialty->name }}
                        </div>
                        @endif
                        @if($doctor->experience_years)
                        <div class="credential-item mb-3">
                            <i class="fas fa-award text-primary me-3"></i>
                            <strong>Experience:</strong> {{ $doctor->experience_years }} years
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="medical-timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Medical Education</h5>
                            <p>{{ $doctor->education ?: 'Completed medical degree from accredited institution' }}</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Specialization</h5>
                            <p>{{ $doctor->specialty ? 'Specialized in ' . $doctor->specialty->name : 'Completed specialized training in chosen field' }}</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Practice</h5>
                            <p>{{ $doctor->experience_years ? $doctor->experience_years . ' years of dedicated practice' : 'Years of dedicated medical practice' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="mb-3">Medical Services</h2>
                <p class="lead text-muted">Comprehensive healthcare solutions tailored to your needs</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="medical-card text-center">
                    <div class="medical-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h4 class="mb-3">General Consultation</h4>
                    <p class="text-muted">Comprehensive health assessments and routine check-ups to maintain your overall well-being.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="medical-card text-center">
                    <div class="medical-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h4 class="mb-3">Preventive Care</h4>
                    <p class="text-muted">Proactive healthcare measures including screenings and vaccinations to prevent illness.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="medical-card text-center">
                    <div class="medical-icon">
                        <i class="fas fa-prescription-bottle-alt"></i>
                    </div>
                    <h4 class="mb-3">Treatment Plans</h4>
                    <p class="text-muted">Personalized treatment strategies designed specifically for your health conditions and goals.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
@if($reviews->count() > 0)
<section id="testimonials" class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="mb-3">Patient Testimonials</h2>
                <p class="lead text-muted">What our patients say about their experience</p>
            </div>
        </div>

        <div class="row g-4">
            @foreach($reviews->take(3) as $index => $review)
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="{{ ($index + 1) * 100 }}">
                <div class="medical-card">
                    <div class="mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                    </div>
                    <blockquote class="mb-4">
                        "{{ $review->comment }}"
                    </blockquote>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3">
                            <span class="fw-bold">{{ strtoupper(substr($review->patient_name ?? 'A', 0, 1)) }}</span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $review->patient_name ?? 'Anonymous Patient' }}</h6>
                            <small class="text-muted">{{ $review->created_at->format('M Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Contact Section -->
<section id="contact" class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="mb-3">Get In Touch</h2>
                <p class="lead text-muted">Ready to take the next step in your healthcare journey?</p>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-6">
                <div class="contact-info">
                    @if($doctor->phone)
                    <div class="contact-item mb-4" data-aos="fade-right" data-aos-delay="100">
                        <div class="medical-icon me-4">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h5>Phone</h5>
                            <p class="mb-0">
                                <a href="tel:{{ $doctor->phone }}" class="text-decoration-none">{{ $doctor->phone }}</a>
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($doctor->user->email)
                    <div class="contact-item mb-4" data-aos="fade-right" data-aos-delay="200">
                        <div class="medical-icon me-4">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h5>Email</h5>
                            <p class="mb-0">
                                <a href="mailto:{{ $doctor->user->email }}" class="text-decoration-none">{{ $doctor->user->email }}</a>
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($doctor->address)
                    <div class="contact-item mb-4" data-aos="fade-right" data-aos-delay="300">
                        <div class="medical-icon me-4">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h5>Address</h5>
                            <p class="mb-0">{{ $doctor->address }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="medical-card" data-aos="fade-left">
                    <h4 class="mb-4">Schedule Your Appointment</h4>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Last Name" required>
                            </div>
                            <div class="col-12">
                                <input type="email" class="form-control" placeholder="Email Address" required>
                            </div>
                            <div class="col-12">
                                <input type="tel" class="form-control" placeholder="Phone Number">
                            </div>
                            <div class="col-12">
                                <select class="form-select" required>
                                    <option value="">Select Appointment Type</option>
                                    <option value="consultation">General Consultation</option>
                                    <option value="checkup">Routine Check-up</option>
                                    <option value="followup">Follow-up Visit</option>
                                    <option value="urgent">Urgent Care</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <textarea class="form-control" rows="4" placeholder="Additional Notes (Optional)"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-medical-primary w-100">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Request Appointment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Medical Footer -->
<footer class="medical-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-section">
                    <h5>{{ $doctor->user->name }}</h5>
                    <p class="text-light mb-4">
                        {{ $doctor->specialty->name ?? 'Medical Professional' }}<br>
                        Committed to providing exceptional healthcare with compassion and expertise.
                    </p>
                    <div class="social-links">
                        <!-- Social media links would go here -->
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <a href="#about" class="footer-link">About</a>
                    <a href="#services" class="footer-link">Services</a>
                    <a href="#testimonials" class="footer-link">Reviews</a>
                    <a href="#contact" class="footer-link">Contact</a>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="footer-section">
                    <h5>Services</h5>
                    <a href="#" class="footer-link">General Consultation</a>
                    <a href="#" class="footer-link">Preventive Care</a>
                    <a href="#" class="footer-link">Treatment Plans</a>
                    <a href="#" class="footer-link">Telemedicine</a>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="footer-section">
                    <h5>Contact Info</h5>
                    @if($doctor->phone)
                    <a href="tel:{{ $doctor->phone }}" class="footer-link">
                        <i class="fas fa-phone me-2"></i>{{ $doctor->phone }}
                    </a>
                    @endif
                    @if($doctor->user->email)
                    <a href="mailto:{{ $doctor->user->email }}" class="footer-link">
                        <i class="fas fa-envelope me-2"></i>{{ $doctor->user->email }}
                    </a>
                    @endif
                    @if($doctor->address)
                    <div class="footer-link">
                        <i class="fas fa-map-marker-alt me-2"></i>{{ $doctor->address }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <hr class="my-4" style="border-color: rgba(255, 255, 255, 0.1);">

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-light">&copy; {{ date('Y') }} {{ $doctor->user->name }}. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="#" class="footer-link me-3">Privacy Policy</a>
                <a href="#" class="footer-link">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating CTA -->
<a href="#appointments" class="floating-cta">
    <i class="fas fa-calendar-plus me-2"></i>
    Book Now
</a>
@endsection

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 1000,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });

    // Navbar scroll effect
    const navbar = document.getElementById('medicalNavbar');

    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Active navigation highlighting
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link[href^="#"]');

    window.addEventListener('scroll', function() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= sectionTop && pageYOffset < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });

    // Form submission
    const appointmentForm = document.querySelector('#contact form');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;

            // Simulate form submission
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Request Sent!';
                submitBtn.classList.remove('btn-medical-primary');
                submitBtn.classList.add('btn-success');

                // Reset after 3 seconds
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.remove('btn-success');
                    submitBtn.classList.add('btn-medical-primary');
                    submitBtn.disabled = false;
                    this.reset();
                }, 3000);
            }, 2000);
        });
    }
});
</script>
@endpush
