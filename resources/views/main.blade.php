@extends('master')

@section('title', 'AI Medical Diagnosis - Smart Healthcare Solutions')

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
                        <span style="color: #DE6262;">AI-Powered</span><br><span style="color: #FFE4E1;">Medical Diagnosis</span>
                    </h1>
                    <p class="lead mb-4 opacity-90" data-animate="fadeInUp" data-delay="200">
                        Revolutionary artificial intelligence that empowers doctors with instant, accurate diagnostic insights. Transform patient care with cutting-edge technology.
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
            <h2 class="section-title">Why Choose Our AI System?</h2>
            <p class="section-subtitle">Advanced technology designed for modern healthcare</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h4 class="mb-3">Advanced AI Analysis</h4>
                    <p class="text-muted">Machine learning algorithms trained on millions of medical cases provide unprecedented diagnostic accuracy.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="mb-3">Instant Results</h4>
                    <p class="text-muted">Get comprehensive diagnostic insights within seconds, enabling faster patient care and treatment decisions.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="mb-3">HIPAA Compliant</h4>
                    <p class="text-muted">Enterprise-grade security ensures all patient data is encrypted and protected according to medical standards.</p>
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
            <p class="section-subtitle">Simple 3-step process for AI-powered diagnosis</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="mt-4">
                        <i class="fas fa-user-doctor text-theme-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="mb-3">Input Patient Data</h4>
                        <p class="text-muted">Securely enter patient symptoms, medical history, and relevant clinical information through our intuitive interface.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="mt-4">
                        <i class="fas fa-cogs text-theme-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="mb-3">AI Processing</h4>
                        <p class="text-muted">Our advanced AI analyzes the data against vast medical databases and clinical guidelines for accurate insights.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="mt-4">
                        <i class="fas fa-chart-line text-theme-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="mb-3">Get Results</h4>
                        <p class="text-muted">Receive detailed diagnostic suggestions, treatment recommendations, and confidence scores instantly.</p>
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
                    <div class="stat-number">15K+</div>
                    <h5>Diagnoses Generated</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <h5>Accuracy Rate</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <h5>Doctors Using</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <h5>Available</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5" style="background: #F8F9FA;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">What Doctors Say</h2>
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
                    <p class="text-muted">This AI system has revolutionized my practice. The accuracy and speed of diagnosis have improved patient outcomes significantly.</p>
                </div>
            </div>
            <div class="col-lg-5 col-md-6">
                <div class="testimonial-card">
                    <div class="mb-4">
                    <!--    <img src="https://via.placeholder.com/80x80/DE6262/FFFFFF?text=KM" alt="Dr. Khaled Mansour" class="rounded-circle mb-3" width="80" height="80">-->
                        <h5 class="mb-1">Dr. Khaled Mansour</h5>
                        <small class="text-muted">Family Physician</small>
                    </div>
                    <p class="text-muted">An essential tool for modern healthcare. The AI provides insights I might have missed, making me a better doctor.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@if($showPricingSection)
<!-- Pricing Section -->
<section id="pricing" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Choose Your Plan</h2>
            <p class="section-subtitle">Flexible pricing for practices of all sizes</p>
            
            <!-- Billing Toggle -->
            <div class="d-flex justify-content-center align-items-center mb-4">
                <span class="me-3">Monthly</span>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="billingToggle" style="transform: scale(1.5);">
                </div>
                <span class="ms-3">Yearly <span class="badge bg-success">Save 17%</span></span>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Basic Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card h-100">
                    <div class="pricing-header text-center">
                        <h4 class="plan-name">Basic</h4>
                        <div class="price-container">
                            <span class="price monthly-price">$29</span>
                            <span class="price yearly-price d-none">$24</span>
                            <span class="period">/month</span>
                        </div>
                        <p class="text-muted">Perfect for small practices</p>
                    </div>
                    <div class="pricing-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check text-success me-2"></i>100 AI diagnoses per month</li>
                            <li><i class="fas fa-check text-success me-2"></i>Basic patient management</li>
                            <li><i class="fas fa-check text-success me-2"></i>Email support</li>
                            <li><i class="fas fa-check text-success me-2"></i>Standard security</li>
                        </ul>
                    </div>
                    <div class="pricing-footer text-center">
                        <button class="btn btn-theme-outline w-100 subscribe-btn" 
                                data-plan="basic" 
                                data-monthly-price="29" 
                                data-yearly-price="290">
                            Get Started
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pro Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card h-100 featured">
                    <div class="popular-badge">Most Popular</div>
                    <div class="pricing-header text-center">
                        <h4 class="plan-name">Professional</h4>
                        <div class="price-container">
                            <span class="price monthly-price">$79</span>
                            <span class="price yearly-price d-none">$66</span>
                            <span class="period">/month</span>
                        </div>
                        <p class="text-muted">Ideal for growing practices</p>
                    </div>
                    <div class="pricing-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check text-success me-2"></i>500 AI diagnoses per month</li>
                            <li><i class="fas fa-check text-success me-2"></i>Advanced patient management</li>
                            <li><i class="fas fa-check text-success me-2"></i>Priority email support</li>
                            <li><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                            <li><i class="fas fa-check text-success me-2"></i>Export capabilities</li>
                        </ul>
                    </div>
                    <div class="pricing-footer text-center">
                        <button class="btn btn-theme-primary w-100 subscribe-btn" 
                                data-plan="pro" 
                                data-monthly-price="79" 
                                data-yearly-price="790">
                            Get Started
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enterprise Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card h-100">
                    <div class="pricing-header text-center">
                        <h4 class="plan-name">Enterprise</h4>
                        <div class="price-container">
                            <span class="price monthly-price">$199</span>
                            <span class="price yearly-price d-none">$166</span>
                            <span class="period">/month</span>
                        </div>
                        <p class="text-muted">For large healthcare organizations</p>
                    </div>
                    <div class="pricing-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check text-success me-2"></i>Unlimited AI diagnoses</li>
                            <li><i class="fas fa-check text-success me-2"></i>Complete patient management suite</li>
                            <li><i class="fas fa-check text-success me-2"></i>24/7 phone & email support</li>
                            <li><i class="fas fa-check text-success me-2"></i>Advanced analytics & reporting</li>
                            <li><i class="fas fa-check text-success me-2"></i>API access</li>
                            <li><i class="fas fa-check text-success me-2"></i>Custom integrations</li>
                            <li><i class="fas fa-check text-success me-2"></i>Dedicated account manager</li>
                        </ul>
                    </div>
                    <div class="pricing-footer text-center">
                        <button class="btn btn-theme-outline w-100 subscribe-btn" 
                                data-plan="enterprise" 
                                data-monthly-price="199" 
                                data-yearly-price="1990">
                            Get Started
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <p class="text-muted">All plans include a 14-day free trial. No credit card required.</p>
            <p class="text-muted">Need a custom solution? <a href="{{ route('contact') }}" class="text-theme-primary">Contact our sales team</a></p>
        </div>
    </div>
</section>
@endif

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

// Pricing functionality
document.addEventListener('DOMContentLoaded', function() {
    const billingToggle = document.getElementById('billingToggle');
    const monthlyPrices = document.querySelectorAll('.monthly-price');
    const yearlyPrices = document.querySelectorAll('.yearly-price');
    const subscribeButtons = document.querySelectorAll('.subscribe-btn');

    // Toggle between monthly and yearly pricing
    billingToggle.addEventListener('change', function() {
        const isYearly = this.checked;
        
        monthlyPrices.forEach(price => {
            price.classList.toggle('d-none', isYearly);
        });
        
        yearlyPrices.forEach(price => {
            price.classList.toggle('d-none', !isYearly);
        });
    });

    // Handle subscription button clicks
    subscribeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const plan = this.dataset.plan;
            const billingCycle = billingToggle.checked ? 'yearly' : 'monthly';
            
            // Check if user is authenticated
            @auth
                // User is logged in, proceed with checkout
                handleSubscription(plan, billingCycle);
            @else
                // Redirect to registration with plan info
                window.location.href = `/register?plan=${plan}&billing=${billingCycle}`;
            @endauth
        });
    });

    @auth
    function handleSubscription(plan, billingCycle) {
        // Show loading state
        const button = document.querySelector(`[data-plan="${plan}"]`);
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        button.disabled = true;

        // Debug logging
        console.log('Starting subscription checkout for:', plan, billingCycle);
        
        // Check CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token meta tag not found');
            alert('Page error: CSRF token missing. Please refresh the page.');
            button.innerHTML = originalText;
            button.disabled = false;
            return;
        }
        
        console.log('CSRF token found:', csrfToken.getAttribute('content').substring(0, 10) + '...');

        // Create checkout session
        fetch('/subscription/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            },
            body: JSON.stringify({
                plan: plan,
                billing_cycle: billingCycle
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(data => {
                    console.error('Server error response:', data);
                    throw new Error(data.error || 'Failed to create checkout session');
                }).catch(jsonError => {
                    console.error('Failed to parse error response as JSON:', jsonError);
                    throw new Error(`Server error (${response.status}): ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                throw new Error(data.error || 'Failed to create checkout session');
            }
        })
        .catch(error => {
            console.error('Subscription checkout error:', error);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            
            // Show more specific error message
            let errorMessage = error.message;
            if (errorMessage.includes('not configured')) {
                errorMessage = 'Payment system is currently being set up. Please contact support for assistance.';
            } else if (errorMessage.includes('Price ID not configured')) {
                errorMessage = 'This plan is temporarily unavailable. Please contact support.';
            }
            
            // Log the final error message being shown to user
            console.error('Showing user error:', errorMessage);
            
            alert('Error: ' + errorMessage);
            
            // Reset button
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
    @endauth
});
</script>
@endsection