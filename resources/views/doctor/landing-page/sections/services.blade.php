@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
$services = $config['services'] ?? [
    ['title' => 'General Consultation', 'description' => 'Comprehensive health checkups', 'icon' => 'fas fa-stethoscope'],
    ['title' => 'Preventive Care', 'description' => 'Regular health screenings', 'icon' => 'fas fa-shield-alt'],
    ['title' => 'Treatment Plans', 'description' => 'Personalized treatment approaches', 'icon' => 'fas fa-prescription-bottle-alt'],
];
@endphp

<section class="services-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
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
                    {{ $config['title'] ?? 'Our Services' }}
                </h2>

                @if(isset($config['subtitle']) && $config['subtitle'])
                <p class="section-subtitle lead text-muted">
                    {{ $config['subtitle'] }}
                </p>
                @endif
            </div>
        </div>

        <!-- Services Grid -->
        <div class="row g-4">
            @foreach($services as $index => $service)
            <div class="col-lg-{{ ($config['layout'] ?? 'grid-3') === 'grid-2' ? '6' : '4' }} col-md-6">
                <div class="service-card h-100 text-center p-4 rounded-3 shadow-sm bg-white position-relative overflow-hidden"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="{{ $index * 100 }}"
                     @endif
                     style="transition: all 0.3s ease; border: 1px solid #e2e8f0;">

                    <!-- Background decoration -->
                    <div class="service-bg-decoration position-absolute top-0 end-0 opacity-10">
                        <i class="{{ $service['icon'] ?? 'fas fa-medical-kit' }} fa-4x text-primary"></i>
                    </div>

                    <!-- Service Icon -->
                    <div class="service-icon mb-4 position-relative">
                        <div class="icon-wrapper d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                            <i class="{{ $service['icon'] ?? 'fas fa-medical-kit' }} fa-2x text-white"></i>
                        </div>
                    </div>

                    <!-- Service Content -->
                    <div class="service-content position-relative">
                        <h4 class="service-title h5 fw-bold mb-3"
                            style="color: {{ $config['text_color'] ?? '#374151' }};">
                            {{ $service['title'] ?? 'Service Title' }}
                        </h4>

                        <p class="service-description text-muted mb-4">
                            {{ $service['description'] ?? 'Service description goes here...' }}
                        </p>

                        @if(isset($service['features']) && is_array($service['features']))
                        <ul class="service-features list-unstyled mb-4">
                            @foreach($service['features'] as $feature)
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <small>{{ $feature }}</small>
                            </li>
                            @endforeach
                        </ul>
                        @endif

                        @if(isset($config['show_service_cta']) && $config['show_service_cta'])
                        <a href="{{ $service['link'] ?? '#appointments' }}"
                           class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            Learn More
                            <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                        @endif
                    </div>

                    <!-- Hover effect overlay -->
                    <div class="service-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center opacity-0"
                         style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(16, 185, 129, 0.9)); transition: all 0.3s ease;">
                        <div class="text-white text-center">
                            <i class="{{ $service['icon'] ?? 'fas fa-medical-kit' }} fa-3x mb-3"></i>
                            <h5 class="fw-bold">{{ $service['title'] ?? 'Service Title' }}</h5>
                            <p class="mb-3">{{ $service['description'] ?? 'Service description...' }}</p>
                            <a href="{{ $service['link'] ?? '#appointments' }}"
                               class="btn btn-light btn-sm rounded-pill">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if(isset($config['show_all_services_cta']) && $config['show_all_services_cta'])
        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="{{ $config['all_services_link'] ?? '#appointments' }}"
                   class="btn btn-primary btn-lg rounded-pill px-5">
                    {{ $config['all_services_text'] ?? 'View All Services' }}
                    <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
        @endif
    </div>
</section>

@if(!$isBuilder)
<style>
.services-section {
    position: relative;
    overflow: hidden;
}

.services-section::before {
    content: '';
    position: absolute;
    top: 20%;
    left: -5%;
    width: 150px;
    height: 150px;
    background: linear-gradient(45deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.05;
    z-index: 0;
}

.service-card {
    cursor: pointer;
    transform: translateY(0);
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
    border-color: var(--primary-color, #3b82f6) !important;
}

.service-card:hover .service-overlay {
    opacity: 1;
}

.service-card:hover .service-content,
.service-card:hover .service-icon {
    opacity: 0;
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
    transition: all 0.6s ease;
    opacity: 0;
}

.service-card:hover .icon-wrapper::before {
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

.service-bg-decoration {
    top: -20px;
    right: -20px;
    transform: rotate(15deg);
}

.service-features {
    text-align: left;
}

@media (max-width: 768px) {
    .service-card {
        margin-bottom: 2rem;
    }

    .service-card:hover {
        transform: translateY(-5px);
    }

    .service-overlay {
        position: relative !important;
        opacity: 0 !important;
        height: auto !important;
        background: none !important;
        padding: 1rem 0 0 0;
    }

    .service-card:hover .service-content,
    .service-card:hover .service-icon {
        opacity: 1;
    }
}

/* Grid layout variations */
.services-section[data-layout="grid-2"] .col-lg-4 {
    flex: 0 0 50%;
    max-width: 50%;
}

.services-section[data-layout="grid-4"] .col-lg-4 {
    flex: 0 0 25%;
    max-width: 25%;
}

@media (max-width: 992px) {
    .services-section[data-layout="grid-4"] .col-lg-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}
</style>
@endif
