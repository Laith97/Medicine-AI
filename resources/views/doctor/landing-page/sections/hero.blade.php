@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
@endphp

<section class="hero-section {{ $isBuilder ? 'builder-section' : '' }}"
         data-section-id="{{ $section['id'] ?? '' }}"
         style="
            background-color: {{ $config['background_color'] ?? '#3b82f6' }};
            color: {{ $config['text_color'] ?? '#ffffff' }};
            {{ isset($config['background_image']) && $config['background_image'] ? 'background-image: url(' . Storage::disk('public')->url($config['background_image']) . ');' : '' }}
            background-size: cover;
            background-position: center;
            position: relative;
            min-height: 600px;
            display: flex;
            align-items: center;
         "
         @if(isset($config['animation']) && $config['animation'] && !$isBuilder)
         data-aos="{{ $config['animation'] }}"
         data-aos-duration="1000"
         @endif>

    @if(isset($config['background_image']) && $config['background_image'])
    <div class="hero-overlay" style="
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, {{ $config['overlay_opacity'] ?? 0.5 }});
    "></div>
    @endif

    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="hero-title display-4 fw-bold mb-4"
                    style="color: {{ $config['text_color'] ?? '#ffffff' }};">
                    {{ $config['title'] ?? 'Welcome to My Practice' }}
                </h1>

                @if(isset($config['subtitle']) && $config['subtitle'])
                <p class="hero-subtitle lead mb-5"
                   style="color: {{ $config['text_color'] ?? '#ffffff' }}; opacity: 0.9;">
                    {{ $config['subtitle'] }}
                </p>
                @endif

                @if(isset($config['button_text']) && $config['button_text'])
                <div class="hero-actions">
                    <a href="{{ $config['button_link'] ?? '#' }}"
                       class="btn btn-lg px-5 py-3 rounded-pill"
                       style="
                        background-color: {{ $config['button_color'] ?? '#ffffff' }};
                        color: {{ $config['button_text_color'] ?? '#3b82f6' }};
                        border: none;
                        font-weight: 600;
                        text-decoration: none;
                        transition: all 0.3s ease;
                       "
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.2)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        {{ $config['button_text'] }}
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$isBuilder)
    <!-- Scroll indicator -->
    <div class="scroll-indicator position-absolute bottom-0 start-50 translate-middle-x mb-4">
        <div class="scroll-arrow animate__animated animate__bounce animate__infinite">
            <i class="fas fa-chevron-down" style="color: {{ $config['text_color'] ?? '#ffffff' }}; opacity: 0.7;"></i>
        </div>
    </div>
    @endif
</section>

@if(!$isBuilder)
<style>
.hero-section {
    background-attachment: fixed;
}

.scroll-arrow {
    animation-duration: 2s;
}

@media (max-width: 768px) {
    .hero-section {
        min-height: 500px;
        background-attachment: scroll;
    }

    .hero-title {
        font-size: 2.5rem !important;
    }
}
</style>
@endif
