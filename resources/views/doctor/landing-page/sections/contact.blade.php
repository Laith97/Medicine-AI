@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
$doctor = $doctor ?? auth()->user()->doctor ?? null;
@endphp

<section class="contact-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
         data-section-id="{{ $section['id'] ?? '' }}"
         style="background-color: {{ $config['background_color'] ?? '#f8fafc' }};"
         @if(isset($config['animation']) && $config['animation'] && !$isBuilder)
         data-aos="{{ $config['animation'] }}"
         data-aos-duration="1000"
         @endif>

    <div class="container">
        <!-- Section Header -->
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="section-title h1 fw-bold mb-3"
                    style="color: {{ $config['text_color'] ?? '#374151' }};">
                    {{ $config['title'] ?? 'Get In Touch' }}
                </h2>

                @if(isset($config['subtitle']) && $config['subtitle'])
                <p class="section-subtitle lead text-muted">
                    {{ $config['subtitle'] }}
                </p>
                @endif
            </div>
        </div>

        <div class="row g-5">
            <!-- Contact Information -->
            <div class="col-lg-6">
                <div class="contact-info">
                    <h3 class="h4 fw-bold mb-4" style="color: {{ $config['text_color'] ?? '#374151' }};">
                        Contact Information
                    </h3>

                    <div class="contact-items">
                        @if($doctor && $doctor->phone)
                        <div class="contact-item d-flex align-items-start mb-4"
                             @if(!$isBuilder)
                             data-aos="fade-right"
                             data-aos-delay="100"
                             @endif>
                            <div class="contact-icon me-4">
                                <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-circle"
                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                    <i class="fas fa-phone text-white"></i>
                                </div>
                            </div>
                            <div class="contact-content">
                                <h5 class="contact-title fw-bold mb-1">Phone</h5>
                                <p class="contact-text mb-0">
                                    <a href="tel:{{ $doctor->phone }}" class="text-decoration-none">
                                        {{ $doctor->phone }}
                                    </a>
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($doctor && $doctor->user->email)
                        <div class="contact-item d-flex align-items-start mb-4"
                             @if(!$isBuilder)
                             data-aos="fade-right"
                             data-aos-delay="200"
                             @endif>
                            <div class="contact-icon me-4">
                                <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-circle"
                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                    <i class="fas fa-envelope text-white"></i>
                                </div>
                            </div>
                            <div class="contact-content">
                                <h5 class="contact-title fw-bold mb-1">Email</h5>
                                <p class="contact-text mb-0">
                                    <a href="mailto:{{ $doctor->user->email }}" class="text-decoration-none">
                                        {{ $doctor->user->email }}
                                    </a>
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($doctor && $doctor->address)
                        <div class="contact-item d-flex align-items-start mb-4"
                             @if(!$isBuilder)
                             data-aos="fade-right"
                             data-aos-delay="300"
                             @endif>
                            <div class="contact-icon me-4">
                                <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-circle"
                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                    <i class="fas fa-map-marker-alt text-white"></i>
                                </div>
                            </div>
                            <div class="contact-content">
                                <h5 class="contact-title fw-bold mb-1">Address</h5>
                                <p class="contact-text mb-0">{{ $doctor->address }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Working Hours -->
                        <div class="contact-item d-flex align-items-start mb-4"
                             @if(!$isBuilder)
                             data-aos="fade-right"
                             data-aos-delay="400"
                             @endif>
                            <div class="contact-icon me-4">
                                <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-circle"
                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <div class="contact-content">
                                <h5 class="contact-title fw-bold mb-1">Working Hours</h5>
                                <div class="working-hours">
                                    @if($doctor && $doctor->availability)
                                        @foreach($doctor->availability as $day => $hours)
                                            @if($hours['is_available'])
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>{{ ucfirst($day) }}:</span>
                                                <span>{{ $hours['start_time'] }} - {{ $hours['end_time'] }}</span>
                                            </div>
                                            @endif
                                        @endforeach
                                    @else
                                    <p class="contact-text mb-0">
                                        Mon - Fri: 9:00 AM - 6:00 PM<br>
                                        Sat: 9:00 AM - 2:00 PM<br>
                                        Sun: Closed
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    @if(isset($config['show_social']) && $config['show_social'])
                    <div class="social-links mt-5"
                         @if(!$isBuilder)
                         data-aos="fade-right"
                         data-aos-delay="500"
                         @endif>
                        <h5 class="fw-bold mb-3">Follow Us</h5>
                        <div class="social-icons">
                            @if(isset($config['social_links']['facebook']) && $config['social_links']['facebook'])
                            <a href="{{ $config['social_links']['facebook'] }}" class="social-icon me-3" target="_blank">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            @endif
                            @if(isset($config['social_links']['twitter']) && $config['social_links']['twitter'])
                            <a href="{{ $config['social_links']['twitter'] }}" class="social-icon me-3" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                            @endif
                            @if(isset($config['social_links']['linkedin']) && $config['social_links']['linkedin'])
                            <a href="{{ $config['social_links']['linkedin'] }}" class="social-icon me-3" target="_blank">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            @endif
                            @if(isset($config['social_links']['instagram']) && $config['social_links']['instagram'])
                            <a href="{{ $config['social_links']['instagram'] }}" class="social-icon me-3" target="_blank">
                                <i class="fab fa-instagram"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Contact Form -->
            @if(($config['show_form'] ?? true))
            <div class="col-lg-6">
                <div class="contact-form-wrapper"
                     @if(!$isBuilder)
                     data-aos="fade-left"
                     data-aos-delay="200"
                     @endif>
                    <div class="contact-form bg-white p-5 rounded-4 shadow-lg">
                        <h3 class="h4 fw-bold mb-4" style="color: {{ $config['text_color'] ?? '#374151' }};">
                            Send us a Message
                        </h3>

                        <form id="contactForm" class="contact-form-inner">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="firstName" name="first_name" placeholder="First Name" required>
                                        <label for="firstName">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Last Name" required>
                                        <label for="lastName">Last Name</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                        <label for="email">Email Address</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone">
                                        <label for="phone">Phone Number</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="">Select Subject</option>
                                            <option value="appointment">Appointment Request</option>
                                            <option value="consultation">General Consultation</option>
                                            <option value="emergency">Emergency</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <label for="subject">Subject</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="message" name="message" placeholder="Message" style="height: 120px" required></textarea>
                                        <label for="message">Your Message</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Send Message
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Form Status Messages -->
                        <div id="formStatus" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Map Section -->
        @if(($config['show_map'] ?? true) && $doctor && $doctor->address)
        <div class="row mt-5">
            <div class="col-12">
                <div class="map-wrapper rounded-4 overflow-hidden shadow-lg"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="300"
                     @endif>
                    <div class="map-container" style="height: 400px; background: #f8fafc; position: relative;">
                        <!-- Placeholder for map -->
                        <div class="map-placeholder d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                                <h5 class="fw-bold mb-2">Interactive Map</h5>
                                <p class="text-muted mb-3">{{ $doctor->address }}</p>
                                <a href="https://maps.google.com/?q={{ urlencode($doctor->address) }}"
                                   target="_blank"
                                   class="btn btn-primary rounded-pill">
                                    <i class="fas fa-external-link-alt me-2"></i>
                                    Open in Google Maps
                                </a>
                            </div>
                        </div>

                        <!-- Actual Google Maps integration would go here -->
                        <div id="googleMap" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Emergency Contact -->
        @if(isset($config['show_emergency']) && $config['show_emergency'])
        <div class="row mt-5">
            <div class="col-12">
                <div class="emergency-contact text-center p-4 rounded-4"
                     style="background: linear-gradient(135deg, #ef4444, #dc2626);"
                     @if(!$isBuilder)
                     data-aos="pulse"
                     data-aos-delay="400"
                     @endif>
                    <div class="text-white">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h4 class="fw-bold mb-2">Medical Emergency?</h4>
                        <p class="mb-3">For urgent medical situations, please call immediately</p>
                        <a href="tel:{{ $config['emergency_phone'] ?? '911' }}"
                           class="btn btn-light btn-lg rounded-pill">
                            <i class="fas fa-phone me-2"></i>
                            {{ $config['emergency_phone'] ?? '911' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

@if(!$isBuilder)
<style>
.contact-section {
    position: relative;
    overflow: hidden;
}

.contact-section::before {
    content: '';
    position: absolute;
    bottom: 10%;
    left: -5%;
    width: 150px;
    height: 150px;
    background: linear-gradient(45deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.05;
    z-index: 0;
}

.contact-item {
    transition: all 0.3s ease;
    padding: 1rem;
    border-radius: 12px;
    position: relative;
    z-index: 1;
}

.contact-item:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: translateX(10px);
}

.icon-wrapper {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.icon-wrapper::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transform: rotate(45deg);
    transition: all 0.6s ease;
    opacity: 0;
}

.contact-item:hover .icon-wrapper::before {
    opacity: 1;
    animation: shimmer 1.5s ease-in-out;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}

.contact-form {
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.contact-form:hover {
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 20px 40px rgba(59, 130, 246, 0.1) !important;
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
    color: var(--primary-color, #3b82f6);
}

.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.social-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    color: white;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.social-icon:hover {
    color: white;
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}

.working-hours {
    font-size: 0.9rem;
    line-height: 1.6;
}

.map-wrapper {
    transition: all 0.3s ease;
}

.map-wrapper:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15) !important;
}

.map-placeholder {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border: 2px dashed #cbd5e1;
}

.emergency-contact {
    animation: pulse-glow 3s infinite;
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(239, 68, 68, 0.5);
    }
}

.contact-text a {
    color: inherit;
    transition: color 0.3s ease;
}

.contact-text a:hover {
    color: var(--primary-color, #3b82f6);
}

@media (max-width: 768px) {
    .contact-item:hover {
        transform: none;
    }

    .contact-form {
        margin-top: 2rem;
    }

    .social-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .map-wrapper:hover {
        transform: none;
    }
}

/* Form validation styles */
.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-valid {
    border-color: #198754;
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.valid-feedback {
    display: block;
    color: #198754;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const formStatus = document.getElementById('formStatus');

    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;

            // Simulate form submission (replace with actual AJAX call)
            setTimeout(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                // Show success message
                formStatus.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Thank you! Your message has been sent successfully. We'll get back to you soon.
                    </div>
                `;
                formStatus.style.display = 'block';

                // Reset form
                contactForm.reset();

                // Hide message after 5 seconds
                setTimeout(() => {
                    formStatus.style.display = 'none';
                }, 5000);
            }, 2000);
        });
    }
});
</script>
@endif
