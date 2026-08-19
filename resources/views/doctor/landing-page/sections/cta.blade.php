@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
@endphp

<section class="cta-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
         data-section-id="{{ $section['id'] ?? '' }}"
         style="
            background: {{ ($config['background_type'] ?? 'gradient') === 'gradient' ?
                'linear-gradient(135deg, ' . ($config['background_color'] ?? '#3b82f6') . ', ' . ($config['gradient_end'] ?? '#10b981') . ')' :
                ($config['background_type'] === 'image' && isset($config['background_image']) ?
                    'url(' . Storage::disk('public')->url($config['background_image']) . ')' :
                    ($config['background_color'] ?? '#3b82f6')) }};
            background-size: cover;
            background-position: center;
            color: {{ $config['text_color'] ?? '#ffffff' }};
            position: relative;
            overflow: hidden;
         "
         @if(isset($config['animation']) && $config['animation'] && !$isBuilder)
         data-aos="{{ $config['animation'] }}"
         data-aos-duration="1000"
         @endif>

    <!-- Background Overlay -->
    @if(($config['background_type'] ?? 'gradient') === 'image' && isset($config['background_image']))
    <div class="cta-overlay position-absolute top-0 start-0 w-100 h-100"
         style="background: rgba(0, 0, 0, {{ $config['overlay_opacity'] ?? 0.6 }});"></div>
    @endif

    <!-- Animated Background Elements -->
    <div class="cta-bg-elements position-absolute top-0 start-0 w-100 h-100">
        <div class="floating-element floating-element-1"></div>
        <div class="floating-element floating-element-2"></div>
        <div class="floating-element floating-element-3"></div>
        <div class="floating-element floating-element-4"></div>
    </div>

    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <!-- Main Content -->
                <div class="cta-content">
                    @if(isset($config['icon']) && $config['icon'])
                    <div class="cta-icon mb-4"
                         @if(!$isBuilder)
                         data-aos="zoom-in"
                         data-aos-delay="100"
                         @endif>
                        <div class="icon-wrapper d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                            <i class="{{ $config['icon'] }} fa-2x" style="color: {{ $config['text_color'] ?? '#ffffff' }};"></i>
                        </div>
                    </div>
                    @endif

                    <h2 class="cta-title display-4 fw-bold mb-4"
                        style="color: {{ $config['text_color'] ?? '#ffffff' }};"
                        @if(!$isBuilder)
                        data-aos="fade-up"
                        data-aos-delay="200"
                        @endif>
                        {{ $config['title'] ?? 'Ready to Get Started?' }}
                    </h2>

                    @if(isset($config['subtitle']) && $config['subtitle'])
                    <p class="cta-subtitle lead mb-5"
                       style="color: {{ $config['text_color'] ?? '#ffffff' }}; opacity: 0.9;"
                       @if(!$isBuilder)
                       data-aos="fade-up"
                       data-aos-delay="300"
                       @endif>
                        {{ $config['subtitle'] }}
                    </p>
                    @endif

                    <!-- CTA Buttons -->
                    <div class="cta-buttons"
                         @if(!$isBuilder)
                         data-aos="fade-up"
                         data-aos-delay="400"
                         @endif>
                        @if(isset($config['button_text']) && $config['button_text'])
                        <a href="{{ $config['button_link'] ?? '#appointments' }}"
                           class="btn btn-cta-primary btn-lg me-3 mb-3 rounded-pill px-5 py-3"
                           style="
                            background: {{ $config['button_color'] ?? '#ffffff' }};
                            color: {{ $config['button_text_color'] ?? '#3b82f6' }};
                            border: 2px solid {{ $config['button_color'] ?? '#ffffff' }};
                            font-weight: 600;
                            text-decoration: none;
                            transition: all 0.3s ease;
                           "
                           onmouseover="this.style.background='transparent'; this.style.color='{{ $config['button_color'] ?? '#ffffff' }}';"
                           onmouseout="this.style.background='{{ $config['button_color'] ?? '#ffffff' }}'; this.style.color='{{ $config['button_text_color'] ?? '#3b82f6' }}';">
                            <i class="fas fa-{{ $config['button_icon'] ?? 'calendar-plus' }} me-2"></i>
                            {{ $config['button_text'] }}
                        </a>
                        @endif

                        @if(isset($config['secondary_button_text']) && $config['secondary_button_text'])
                        <a href="{{ $config['secondary_button_link'] ?? '#contact' }}"
                           class="btn btn-cta-secondary btn-lg mb-3 rounded-pill px-5 py-3"
                           style="
                            background: transparent;
                            color: {{ $config['text_color'] ?? '#ffffff' }};
                            border: 2px solid {{ $config['text_color'] ?? '#ffffff' }};
                            font-weight: 600;
                            text-decoration: none;
                            transition: all 0.3s ease;
                           "
                           onmouseover="this.style.background='{{ $config['text_color'] ?? '#ffffff' }}'; this.style.color='{{ $config['background_color'] ?? '#3b82f6' }}';"
                           onmouseout="this.style.background='transparent'; this.style.color='{{ $config['text_color'] ?? '#ffffff' }}';">
                            <i class="fas fa-{{ $config['secondary_button_icon'] ?? 'info-circle' }} me-2"></i>
                            {{ $config['secondary_button_text'] }}
                        </a>
                        @endif
                    </div>

                    <!-- Additional Info -->
                    @if(isset($config['show_features']) && $config['show_features'])
                    <div class="cta-features mt-5"
                         @if(!$isBuilder)
                         data-aos="fade-up"
                         data-aos-delay="500"
                         @endif>
                        <div class="row g-4">
                            @foreach(($config['features'] ?? [
                                ['icon' => 'fas fa-clock', 'text' => 'Quick Appointments'],
                                ['icon' => 'fas fa-user-md', 'text' => 'Expert Care'],
                                ['icon' => 'fas fa-heart', 'text' => 'Compassionate Service']
                            ]) as $feature)
                            <div class="col-md-4">
                                <div class="feature-item d-flex align-items-center justify-content-center">
                                    <i class="{{ $feature['icon'] }} me-2" style="color: {{ $config['text_color'] ?? '#ffffff' }};"></i>
                                    <span style="color: {{ $config['text_color'] ?? '#ffffff' }};">{{ $feature['text'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Urgency Indicator -->
                    @if(isset($config['show_urgency']) && $config['show_urgency'])
                    <div class="cta-urgency mt-4"
                         @if(!$isBuilder)
                         data-aos="pulse"
                         data-aos-delay="600"
                         @endif>
                        <div class="urgency-badge d-inline-flex align-items-center px-4 py-2 rounded-pill"
                             style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);">
                            <i class="fas fa-fire text-warning me-2"></i>
                            <span style="color: {{ $config['text_color'] ?? '#ffffff' }};">
                                {{ $config['urgency_text'] ?? 'Limited Time Offer - Book Today!' }}
                            </span>
                        </div>
                    </div>
                    @endif

                    <!-- Contact Info -->
                    @if(isset($config['show_contact_info']) && $config['show_contact_info'])
                    <div class="cta-contact-info mt-5"
                         @if(!$isBuilder)
                         data-aos="fade-up"
                         data-aos-delay="700"
                         @endif>
                        <div class="contact-info-wrapper p-4 rounded-3"
                             style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
                            <h5 class="fw-bold mb-3" style="color: {{ $config['text_color'] ?? '#ffffff' }};">
                                Or Contact Us Directly
                            </h5>
                            <div class="row g-3">
                                @if(isset($config['phone']) && $config['phone'])
                                <div class="col-md-6">
                                    <a href="tel:{{ $config['phone'] }}"
                                       class="contact-link d-flex align-items-center justify-content-center p-3 rounded-2 text-decoration-none"
                                       style="background: rgba(255, 255, 255, 0.1); color: {{ $config['text_color'] ?? '#ffffff' }};">
                                        <i class="fas fa-phone me-2"></i>
                                        {{ $config['phone'] }}
                                    </a>
                                </div>
                                @endif
                                @if(isset($config['email']) && $config['email'])
                                <div class="col-md-6">
                                    <a href="mailto:{{ $config['email'] }}"
                                       class="contact-link d-flex align-items-center justify-content-center p-3 rounded-2 text-decoration-none"
                                       style="background: rgba(255, 255, 255, 0.1); color: {{ $config['text_color'] ?? '#ffffff' }};">
                                        <i class="fas fa-envelope me-2"></i>
                                        {{ $config['email'] }}
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    @if(isset($config['show_scroll_indicator']) && $config['show_scroll_indicator'])
    <div class="scroll-indicator position-absolute bottom-0 start-50 translate-middle-x mb-4">
        <div class="scroll-arrow animate__animated animate__bounce animate__infinite">
            <i class="fas fa-chevron-down" style="color: {{ $config['text_color'] ?? '#ffffff' }}; opacity: 0.7;"></i>
        </div>
    </div>
    @endif
</section>

@if(!$isBuilder)
<style>
.cta-section {
    min-height: 500px;
    display: flex;
    align-items: center;
}

.floating-element {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    animation: float 6s ease-in-out infinite;
}

.floating-element-1 {
    width: 60px;
    height: 60px;
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.floating-element-2 {
    width: 80px;
    height: 80px;
    top: 60%;
    right: 15%;
    animation-delay: 2s;
}

.floating-element-3 {
    width: 40px;
    height: 40px;
    bottom: 30%;
    left: 20%;
    animation-delay: 4s;
}

.floating-element-4 {
    width: 100px;
    height: 100px;
    top: 10%;
    right: 5%;
    animation-delay: 1s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
        opacity: 0.7;
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
        opacity: 1;
    }
}

.cta-title {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    position: relative;
}

.cta-subtitle {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.btn-cta-primary,
.btn-cta-secondary {
    position: relative;
    overflow: hidden;
    transform: translateY(0);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.btn-cta-primary:hover,
.btn-cta-secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
}

.btn-cta-primary::before,
.btn-cta-secondary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.btn-cta-primary:hover::before,
.btn-cta-secondary:hover::before {
    left: 100%;
}

.icon-wrapper {
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
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% {
        opacity: 0;
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    50% {
        opacity: 1;
        transform: translateX(0%) translateY(0%) rotate(45deg);
    }
}

.feature-item {
    padding: 1rem;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.feature-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-5px);
}

.urgency-badge {
    animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(255, 193, 7, 0.6);
    }
}

.contact-link {
    transition: all 0.3s ease;
}

.contact-link:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-2px);
    color: inherit !important;
}

.scroll-arrow {
    animation-duration: 2s;
}

/* Responsive Design */
@media (max-width: 768px) {
    .cta-section {
        min-height: 400px;
        padding: 3rem 0;
    }

    .cta-title {
        font-size: 2.5rem !important;
    }

    .btn-cta-primary,
    .btn-cta-secondary {
        display: block;
        width: 100%;
        margin-bottom: 1rem;
    }

    .floating-element {
        display: none;
    }

    .cta-features .col-md-4 {
        margin-bottom: 1rem;
    }

    .cta-contact-info .col-md-6 {
        margin-bottom: 1rem;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .btn-cta-primary,
    .btn-cta-secondary {
        border-width: 3px;
    }

    .floating-element {
        display: none;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .floating-element,
    .urgency-badge,
    .scroll-arrow {
        animation: none;
    }

    .btn-cta-primary:hover,
    .btn-cta-secondary:hover {
        transform: none;
    }
}

/* Print styles */
@media print {
    .cta-section {
        background: white !important;
        color: black !important;
    }

    .floating-element,
    .scroll-indicator {
        display: none;
    }
}
</style>
@endif
