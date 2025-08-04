@extends('master')

@section('title', 'Contact Us - AI Medical Diagnosis')

@section('content')
<!-- Page Title -->
<section class="page-title page-title-parallax parallax scroll-detect dark page-title-center" style="padding: 140px 0;">
    <img src="demos/medical/images/contact/page-title.jpg" class="parallax-bg">
    <div class="container z-2">
        <div class="page-title-row">
            <div class="page-title-content">
                <div class="emphasis-title dark mb-0">
                    <span class="before-heading text-white fst-italic">info@medcuraai.com</span>
                    <h2 class="fw-bold ls-0 text-white">Contact Us</h2>
                </div>
                <span class="fw-semibold ls-1 text-uppercase" style="color: #EEE;">
                    We're here to help with your medical AI needs
                </span>
            </div>
        </div>
    </div>
    <div class="video-overlay z-1" style="background: rgba(222,98,98,0.85);"></div>
</section>

<section id="content">
    <div class="content-wrap">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <h3 class="mb-4">Get in Touch with Our Team</h3>
                    <p class="text-muted mb-4">Have questions about our AI Assistant system? Need technical support? Our team of medical AI experts is here to help.</p>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="form-widget">
                        <div class="form-result"></div>
                        <form class="row mb-0" id="contact-form" method="post" action="{{ route('contact.store') }}">
                            @csrf
                            <div class="form-process" style="display: none;">
                                <div class="css3-spinner">
                                    <div class="css3-spinner-scaler"></div>
                                </div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="name">Full Name <small>*</small></label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control required" required>
                                @error('name')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="email">Email Address <small>*</small></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control required" required>
                                @error('email')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control">
                                @error('phone')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="service">Inquiry Type</label>
                                <select id="service" name="service" class="form-select">
                                    <option value="">-- Select One --</option>
                                    <option value="General Inquiry" {{ old('service') == 'General Inquiry' ? 'selected' : '' }}>General Inquiry</option>
                                    <option value="Technical Support" {{ old('service') == 'Technical Support' ? 'selected' : '' }}>Technical Support</option>
                                    <option value="Partnership" {{ old('service') == 'Partnership' ? 'selected' : '' }}>Partnership Opportunities</option>
                                    <option value="Demo Request" {{ old('service') == 'Demo Request' ? 'selected' : '' }}>Demo Request</option>
                                    <option value="Billing" {{ old('service') == 'Billing' ? 'selected' : '' }}>Billing & Pricing</option>
                                </select>
                                @error('service')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 form-group">
                                <label for="subject">Subject <small>*</small></label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" class="form-control required" required>
                                @error('subject')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 form-group">
                                <label for="message">Message <small>*</small></label>
                                <textarea class="form-control required" id="message" name="message" rows="6" cols="30" placeholder="Tell us about your needs or questions regarding our AI Assistant system..." required>{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 form-group">
                                <button class="button button-3d m-0" style="background-color: #DE6262; border-color: #DE6262;" type="submit" id="contact-submit">
                                    <span id="submit-text">Send Message</span>
                                    <span id="submit-loading" style="display: none;">
                                        <i class="fas fa-spinner fa-spin"></i> Sending...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div style="background-color: #f5f5f5; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background-color: #DE6262; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px;">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h5>24/7 Support</h5>
                            <p class="text-muted">Our AI system is available around the clock, and our support team is here to help.</p>
                            <p><strong>Response Time:</strong> Within 2 hours</p>
                        </div>
                    </div>

                    <div style="background-color: #f5f5f5; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background-color: #DE6262; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5>HIPAA Compliance</h5>
                            <p class="text-muted">All communications and data are encrypted and HIPAA compliant.</p>
                            <p><strong>Security Level:</strong> Enterprise Grade</p>
                        </div>
                    </div>

                    <div style="background-color: #f5f5f5; padding: 30px; border-radius: 10px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background-color: #DE6262; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5>Expert Team</h5>
                            <p class="text-muted">Our team includes medical professionals and AI specialists.</p>
                            <p><strong>Expertise:</strong> Medical AI & Healthcare</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="clear"></div>


    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const submitBtn = document.getElementById('contact-submit');
    const submitText = document.getElementById('submit-text');
    const submitLoading = document.getElementById('submit-loading');
    const formResult = document.querySelector('.form-result');

    // Check if elements exist
    if (!form || !submitBtn || !submitText || !submitLoading || !formResult) {
        console.error('Contact form elements not found');
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading state
        submitBtn.disabled = true;
        submitText.style.display = 'none';
        submitLoading.style.display = 'inline';

        // Clear previous results
        formResult.innerHTML = '';

        // Gather form data
        const formData = new FormData(form);

        // Form data ready to submit

        // Make AJAX request
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                formResult.innerHTML = `
                    <div class="contact-success-notification">
                        <div class="notification-content">
                            <div class="notification-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="notification-message">
                                <h5>Message Sent Successfully!</h5>
                                <p>${data.message}</p>
                            </div>
                            <button type="button" class="notification-close" onclick="this.parentElement.parentElement.style.display='none'">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
                form.reset();
            } else {
                formResult.innerHTML = `
                    <div class="contact-error-notification">
                        <div class="notification-content">
                            <div class="notification-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="notification-message">
                                <h5>Error Sending Message</h5>
                                <p>${data.message || 'Please check your form and try again.'}</p>
                            </div>
                            <button type="button" class="notification-close" onclick="this.parentElement.parentElement.style.display='none'">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Contact form error:', error);
            formResult.innerHTML = `
                <div class="contact-error-notification">
                    <div class="notification-content">
                        <div class="notification-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="notification-message">
                            <h5>Connection Error</h5>
                            <p>Something went wrong. Please try again later.</p>
                        </div>
                        <button type="button" class="notification-close" onclick="this.parentElement.parentElement.style.display='none'">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            submitLoading.style.display = 'none';
        });
    });

    // Add fallback for direct form submission if JavaScript fails
    form.addEventListener('submit', function(e) {
        // If AJAX is not supported or fails, allow normal form submission
        if (!window.fetch) {
            // Allow normal form submission
            return true;
        }
    });
});
</script>

<style>
/* Custom Contact Form Notifications */
.contact-success-notification, .contact-error-notification {
    margin: 20px 0;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: slideInDown 0.5s ease-out;
}

.contact-success-notification {
    background: linear-gradient(135deg, #10B981, #059669);
    border: 1px solid #059669;
    color: white;
}

.contact-error-notification {
    background: linear-gradient(135deg, #EF4444, #DC2626);
    border: 1px solid #DC2626;
    color: white;
}

.notification-content {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    position: relative;
}

.notification-icon {
    flex-shrink: 0;
    margin-right: 15px;
    margin-top: 2px;
}

.notification-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.notification-message {
    flex: 1;
}

.notification-message h5 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: white;
}

.notification-message p {
    margin: 0;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
}

.notification-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.notification-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.notification-close svg {
    width: 18px;
    height: 18px;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Medical theme colors - complementing your site */
.contact-success-notification {
    background: linear-gradient(135deg, #0369A1, #0284C7);
    border: 1px solid #0284C7;
    box-shadow: 0 4px 12px rgba(3, 105, 161, 0.2);
}

.contact-error-notification {
    background: linear-gradient(135deg, #DC2626, #B91C1C);
    border: 1px solid #B91C1C;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
    .contact-success-notification {
        background: linear-gradient(135deg, #0369A1, #0284C7);
        box-shadow: 0 4px 12px rgba(3, 105, 161, 0.3);
    }

    .contact-error-notification {
        background: linear-gradient(135deg, #DC2626, #B91C1C);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .notification-content {
        padding: 15px;
    }

    .notification-message h5 {
        font-size: 16px;
    }

    .notification-message p {
        font-size: 13px;
    }

    .notification-icon {
        margin-right: 12px;
    }
}
</style>

@endsection
