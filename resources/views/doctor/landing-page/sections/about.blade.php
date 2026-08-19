@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
$doctor = $doctor ?? auth()->user()->doctor ?? null;
@endphp

<section class="about-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
         data-section-id="{{ $section['id'] ?? '' }}"
         style="background-color: {{ $config['background_color'] ?? '#ffffff' }};"
         @if(isset($config['animation']) && $config['animation'] && !$isBuilder)
         data-aos="{{ $config['animation'] }}"
         data-aos-duration="1000"
         @endif>

    <div class="container">
        <div class="row align-items-center">
            @if(($config['layout'] ?? 'image-left') === 'image-left')
            <!-- Image Column -->
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="about-image-wrapper position-relative">
                    @if(isset($config['image']) && $config['image'])
                        <img src="{{ Storage::disk('public')->url($config['image']) }}"
                             alt="Doctor Photo"
                             class="img-fluid rounded-3 shadow-lg">
                    @elseif($doctor && $doctor->user->profile_photo_path)
                        <img src="{{ Storage::disk('public')->url($doctor->user->profile_photo_path) }}"
                             alt="{{ $doctor->user->name }}"
                             class="img-fluid rounded-3 shadow-lg">
                    @else
                        <div class="placeholder-image d-flex align-items-center justify-content-center rounded-3 shadow-lg"
                             style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-user-md fa-5x text-white opacity-50"></i>
                        </div>
                    @endif

                    <!-- Decorative elements -->
                    <div class="position-absolute top-0 start-0 translate-middle">
                        <div class="bg-primary rounded-circle" style="width: 20px; height: 20px; opacity: 0.8;"></div>
                    </div>
                    <div class="position-absolute bottom-0 end-0 translate-middle">
                        <div class="bg-accent rounded-circle" style="width: 30px; height: 30px; opacity: 0.6;"></div>
                    </div>
                </div>
            </div>

            <!-- Content Column -->
            <div class="col-lg-7">
                <div class="about-content ps-lg-5">
            @else
            <!-- Content Column -->
            <div class="col-lg-7">
                <div class="about-content pe-lg-5">
            @endif
                    <div class="section-header mb-4">
                        <h2 class="section-title h1 fw-bold mb-3"
                            style="color: {{ $config['text_color'] ?? '#374151' }};">
                            {{ $config['title'] ?? 'About Dr. ' . ($doctor->user->name ?? '[Name]') }}
                        </h2>

                        @if(isset($config['subtitle']) && $config['subtitle'])
                        <p class="section-subtitle text-muted fs-5">
                            {{ $config['subtitle'] }}
                        </p>
                        @endif
                    </div>

                    <div class="about-text">
                        <p class="lead mb-4" style="color: {{ $config['text_color'] ?? '#374151' }}; line-height: 1.8;">
                            {{ $config['content'] ?? $config['about_text'] ?? ($doctor->bio ?? 'Your professional bio goes here...') }}
                        </p>

                        @if($doctor)
                        <!-- Credentials -->
                        <div class="credentials mb-4">
                            <div class="row g-3">
                                @if($doctor->specialty)
                                <div class="col-sm-6">
                                    <div class="credential-item d-flex align-items-center">
                                        <div class="credential-icon me-3">
                                            <i class="fas fa-stethoscope text-primary"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Specialty</small>
                                            <strong>{{ $doctor->specialty->name }}</strong>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($doctor->experience_years)
                                <div class="col-sm-6">
                                    <div class="credential-item d-flex align-items-center">
                                        <div class="credential-icon me-3">
                                            <i class="fas fa-award text-primary"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Experience</small>
                                            <strong>{{ $doctor->experience_years }} Years</strong>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($doctor->education)
                                <div class="col-12">
                                    <div class="credential-item d-flex align-items-start">
                                        <div class="credential-icon me-3 mt-1">
                                            <i class="fas fa-graduation-cap text-primary"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Education</small>
                                            <strong>{{ $doctor->education }}</strong>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(isset($config['show_cta']) && $config['show_cta'])
                        <div class="about-cta">
                            <a href="{{ $config['cta_link'] ?? '#appointments' }}"
                               class="btn btn-primary btn-lg rounded-pill px-4">
                                {{ $config['cta_text'] ?? 'Book Appointment' }}
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if(($config['layout'] ?? 'image-left') === 'image-right')
            <!-- Image Column -->
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="about-image-wrapper position-relative">
                    @if(isset($config['image']) && $config['image'])
                        <img src="{{ Storage::disk('public')->url($config['image']) }}"
                             alt="Doctor Photo"
                             class="img-fluid rounded-3 shadow-lg">
                    @elseif($doctor && $doctor->user->profile_photo_path)
                        <img src="{{ Storage::disk('public')->url($doctor->user->profile_photo_path) }}"
                             alt="{{ $doctor->user->name }}"
                             class="img-fluid rounded-3 shadow-lg">
                    @else
                        <div class="placeholder-image d-flex align-items-center justify-content-center rounded-3 shadow-lg"
                             style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-user-md fa-5x text-white opacity-50"></i>
                        </div>
                    @endif

                    <!-- Decorative elements -->
                    <div class="position-absolute top-0 start-0 translate-middle">
                        <div class="bg-primary rounded-circle" style="width: 20px; height: 20px; opacity: 0.8;"></div>
                    </div>
                    <div class="position-absolute bottom-0 end-0 translate-middle">
                        <div class="bg-accent rounded-circle" style="width: 30px; height: 30px; opacity: 0.6;"></div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

@if(!$isBuilder)
<style>
.about-section {
    position: relative;
    overflow: hidden;
}

.about-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 200px;
    height: 200px;
    background: linear-gradient(45deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.05;
    z-index: 0;
}

.about-content {
    position: relative;
    z-index: 1;
}

.credential-item {
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border-left: 4px solid var(--primary-color, #3b82f6);
    transition: all 0.3s ease;
}

.credential-item:hover {
    background: rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.credential-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 50%;
}

.about-image-wrapper {
    transform: perspective(1000px) rotateY(-5deg);
    transition: all 0.3s ease;
}

.about-image-wrapper:hover {
    transform: perspective(1000px) rotateY(0deg) scale(1.02);
}

@media (max-width: 768px) {
    .about-image-wrapper {
        transform: none;
    }

    .about-image-wrapper:hover {
        transform: scale(1.02);
    }

    .about-content {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
}
</style>
@endif
