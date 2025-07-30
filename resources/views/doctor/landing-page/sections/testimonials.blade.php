@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
$doctor = $doctor ?? auth()->user()->doctor ?? null;
$reviews = $reviews ?? [];
@endphp

<section class="testimonials-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
         data-section-id="{{ $section['id'] ?? '' }}"
         style="background-color: {{ $config['background_color'] ?? '#ffffff' }};"
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
                    {{ $config['title'] ?? 'What Patients Say' }}
                </h2>

                @if(isset($config['subtitle']) && $config['subtitle'])
                <p class="section-subtitle lead text-muted">
                    {{ $config['subtitle'] }}
                </p>
                @endif
            </div>
        </div>

        <!-- Testimonials Content -->
        @if(($config['layout'] ?? 'carousel') === 'carousel')
        <!-- Carousel Layout -->
        <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @forelse($reviews->take(6) as $index => $review)
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="testimonial-card text-center p-5 rounded-4 shadow-lg bg-white position-relative">
                                <!-- Quote Icon -->
                                <div class="quote-icon mb-4">
                                    <i class="fas fa-quote-left fa-3x text-primary opacity-25"></i>
                                </div>

                                <!-- Review Content -->
                                <blockquote class="testimonial-text fs-5 mb-4 fst-italic"
                                           style="color: {{ $config['text_color'] ?? '#374151' }};">
                                    "{{ $review->comment }}"
                                </blockquote>

                                <!-- Rating -->
                                @if($config['show_ratings'] ?? true)
                                <div class="testimonial-rating mb-4">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                                @endif

                                <!-- Patient Info -->
                                <div class="testimonial-author">
                                    <div class="author-avatar mb-3">
                                        <div class="avatar-circle d-inline-flex align-items-center justify-content-center rounded-circle"
                                             style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                            <span class="text-white fw-bold fs-4">
                                                {{ strtoupper(substr($review->patient_name ?? 'Anonymous', 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <h5 class="author-name fw-bold mb-1">{{ $review->patient_name ?? 'Anonymous Patient' }}</h5>
                                    <p class="author-info text-muted mb-0">
                                        <small>{{ $review->created_at->format('M Y') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <!-- Default testimonials for demo -->
                @foreach([
                    ['name' => 'Sarah Johnson', 'text' => 'Excellent care and professional service. Dr. Smith took the time to listen and provided comprehensive treatment.', 'rating' => 5],
                    ['name' => 'Michael Chen', 'text' => 'Outstanding medical expertise combined with genuine compassion. Highly recommend!', 'rating' => 5],
                    ['name' => 'Emily Davis', 'text' => 'Professional, caring, and thorough. The best healthcare experience I\'ve had.', 'rating' => 5]
                ] as $index => $testimonial)
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="testimonial-card text-center p-5 rounded-4 shadow-lg bg-white position-relative">
                                <div class="quote-icon mb-4">
                                    <i class="fas fa-quote-left fa-3x text-primary opacity-25"></i>
                                </div>
                                <blockquote class="testimonial-text fs-5 mb-4 fst-italic">
                                    "{{ $testimonial['text'] }}"
                                </blockquote>
                                @if($config['show_ratings'] ?? true)
                                <div class="testimonial-rating mb-4">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                                @endif
                                <div class="testimonial-author">
                                    <div class="author-avatar mb-3">
                                        <div class="avatar-circle d-inline-flex align-items-center justify-content-center rounded-circle"
                                             style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                            <span class="text-white fw-bold fs-4">{{ strtoupper(substr($testimonial['name'], 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <h5 class="author-name fw-bold mb-1">{{ $testimonial['name'] }}</h5>
                                    <p class="author-info text-muted mb-0">
                                        <small>Verified Patient</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                @endforelse
            </div>

            <!-- Carousel Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
                <div class="carousel-control-icon">
                    <i class="fas fa-chevron-left fa-2x text-primary"></i>
                </div>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
                <div class="carousel-control-icon">
                    <i class="fas fa-chevron-right fa-2x text-primary"></i>
                </div>
            </button>

            <!-- Carousel Indicators -->
            <div class="carousel-indicators">
                @for($i = 0; $i < max(count($reviews), 3); $i++)
                <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="{{ $i }}"
                        class="{{ $i === 0 ? 'active' : '' }}"></button>
                @endfor
            </div>
        </div>

        @else
        <!-- Grid Layout -->
        <div class="row g-4">
            @forelse($reviews->take(6) as $index => $review)
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card h-100 p-4 rounded-3 shadow-sm bg-white position-relative"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="{{ $index * 100 }}"
                     @endif>

                    <!-- Quote Icon -->
                    <div class="quote-icon-small mb-3">
                        <i class="fas fa-quote-left fa-lg text-primary opacity-50"></i>
                    </div>

                    <!-- Review Text -->
                    <p class="testimonial-text mb-4" style="color: {{ $config['text_color'] ?? '#374151' }};">
                        "{{ Str::limit($review->comment, 120) }}"
                    </p>

                    <!-- Rating -->
                    @if($config['show_ratings'] ?? true)
                    <div class="testimonial-rating mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                    </div>
                    @endif

                    <!-- Author -->
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar me-3">
                            <div class="avatar-circle d-flex align-items-center justify-content-center rounded-circle"
                                 style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                <span class="text-white fw-bold small">
                                    {{ strtoupper(substr($review->patient_name ?? 'A', 0, 1)) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h6 class="author-name mb-0 fw-bold">{{ $review->patient_name ?? 'Anonymous' }}</h6>
                            <small class="text-muted">{{ $review->created_at->format('M Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <!-- Default testimonials -->
            @foreach([
                ['name' => 'Sarah Johnson', 'text' => 'Excellent care and professional service. Dr. Smith took the time to listen and provided comprehensive treatment.', 'rating' => 5],
                ['name' => 'Michael Chen', 'text' => 'Outstanding medical expertise combined with genuine compassion. Highly recommend!', 'rating' => 5],
                ['name' => 'Emily Davis', 'text' => 'Professional, caring, and thorough. The best healthcare experience I\'ve had.', 'rating' => 5]
            ] as $index => $testimonial)
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card h-100 p-4 rounded-3 shadow-sm bg-white position-relative"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="{{ $index * 100 }}"
                     @endif>
                    <div class="quote-icon-small mb-3">
                        <i class="fas fa-quote-left fa-lg text-primary opacity-50"></i>
                    </div>
                    <p class="testimonial-text mb-4">
                        "{{ $testimonial['text'] }}"
                    </p>
                    @if($config['show_ratings'] ?? true)
                    <div class="testimonial-rating mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                    </div>
                    @endif
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar me-3">
                            <div class="avatar-circle d-flex align-items-center justify-content-center rounded-circle"
                                 style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                <span class="text-white fw-bold small">{{ strtoupper(substr($testimonial['name'], 0, 1)) }}</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="author-name mb-0 fw-bold">{{ $testimonial['name'] }}</h6>
                            <small class="text-muted">Verified Patient</small>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
        @endif

        <!-- CTA Section -->
        @if(isset($config['show_cta']) && $config['show_cta'])
        <div class="row mt-5">
            <div class="col-12 text-center">
                <div class="testimonials-cta">
                    <h4 class="mb-3">Ready to Experience Quality Care?</h4>
                    <a href="{{ $config['cta_link'] ?? '#appointments' }}"
                       class="btn btn-primary btn-lg rounded-pill px-5">
                        {{ $config['cta_text'] ?? 'Book Your Appointment' }}
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

@if(!$isBuilder)
<style>
.testimonials-section {
    position: relative;
    overflow: hidden;
}

.testimonials-section::before {
    content: '';
    position: absolute;
    top: 10%;
    right: -5%;
    width: 200px;
    height: 200px;
    background: linear-gradient(45deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.05;
    z-index: 0;
}

.testimonial-card {
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
    position: relative;
    z-index: 1;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
    border-color: var(--primary-color, #3b82f6);
}

.carousel-control-prev,
.carousel-control-next {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.8;
    transition: all 0.3s ease;
}

.carousel-control-prev {
    left: -30px;
}

.carousel-control-next {
    right: -30px;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
    background: white;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.carousel-control-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.carousel-indicators {
    bottom: -50px;
}

.carousel-indicators button {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-color, #3b82f6);
    opacity: 0.3;
    border: none;
    margin: 0 5px;
    transition: all 0.3s ease;
}

.carousel-indicators button.active {
    opacity: 1;
    transform: scale(1.2);
}

.quote-icon {
    position: relative;
}

.quote-icon::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.1;
    z-index: -1;
}

.testimonial-text {
    line-height: 1.8;
    position: relative;
}

.avatar-circle {
    position: relative;
    overflow: hidden;
}

.avatar-circle::before {
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

.testimonial-card:hover .avatar-circle::before {
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

.testimonials-cta {
    padding: 3rem 2rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(16, 185, 129, 0.05));
    border-radius: 20px;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

@media (max-width: 768px) {
    .carousel-control-prev,
    .carousel-control-next {
        width: 40px;
        height: 40px;
    }

    .carousel-control-prev {
        left: -20px;
    }

    .carousel-control-next {
        right: -20px;
    }

    .testimonial-card {
        margin-bottom: 2rem;
    }

    .testimonials-cta {
        padding: 2rem 1rem;
    }
}
</style>
@endif
