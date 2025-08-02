@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
$images = $config['images'] ?? [];
@endphp

<section class="gallery-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
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
                    {{ $config['title'] ?? 'Our Facility' }}
                </h2>

                @if(isset($config['subtitle']) && $config['subtitle'])
                <p class="section-subtitle lead text-muted">
                    {{ $config['subtitle'] }}
                </p>
                @endif
            </div>
        </div>

        <!-- Gallery Content -->
        @if(($config['layout'] ?? 'masonry') === 'masonry')
        <!-- Masonry Layout -->
        <div class="gallery-masonry" data-columns="{{ $config['columns'] ?? 3 }}">
            @forelse($images as $index => $image)
            <div class="gallery-item"
                 @if(!$isBuilder)
                 data-aos="fade-up"
                 data-aos-delay="{{ $index * 100 }}"
                 @endif>
                <div class="gallery-card position-relative overflow-hidden rounded-3 shadow-sm">
                    <img src="{{ Storage::url($image['url']) }}"
                         alt="{{ $image['caption'] ?? 'Gallery Image' }}"
                         class="img-fluid gallery-image">

                    <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                        <div class="gallery-actions">
                            <button class="btn btn-light btn-sm rounded-circle me-2"
                                    onclick="openLightbox({{ $index }})"
                                    title="View Image">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            @if(isset($image['caption']) && $image['caption'])
                            <button class="btn btn-light btn-sm rounded-circle"
                                    title="{{ $image['caption'] }}">
                                <i class="fas fa-info"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                    @if(isset($image['caption']) && $image['caption'])
                    <div class="gallery-caption position-absolute bottom-0 start-0 w-100 p-3">
                        <p class="mb-0 text-white small">{{ $image['caption'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <!-- Default gallery images for demo -->
            @foreach([
                ['url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400', 'caption' => 'Modern Reception Area'],
                ['url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400', 'caption' => 'Consultation Room'],
                ['url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=400', 'caption' => 'Medical Equipment'],
                ['url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400', 'caption' => 'Waiting Area'],
                ['url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400', 'caption' => 'Treatment Room'],
                ['url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=400', 'caption' => 'Laboratory']
            ] as $index => $image)
            <div class="gallery-item"
                 @if(!$isBuilder)
                 data-aos="fade-up"
                 data-aos-delay="{{ $index * 100 }}"
                 @endif>
                <div class="gallery-card position-relative overflow-hidden rounded-3 shadow-sm">
                    <img src="{{ $image['url'] }}"
                         alt="{{ $image['caption'] }}"
                         class="img-fluid gallery-image">

                    <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                        <div class="gallery-actions">
                            <button class="btn btn-light btn-sm rounded-circle me-2"
                                    onclick="openLightbox({{ $index }})"
                                    title="View Image">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-light btn-sm rounded-circle"
                                    title="{{ $image['caption'] }}">
                                <i class="fas fa-info"></i>
                            </button>
                        </div>
                    </div>

                    <div class="gallery-caption position-absolute bottom-0 start-0 w-100 p-3">
                        <p class="mb-0 text-white small">{{ $image['caption'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>

        @else
        <!-- Grid Layout -->
        <div class="gallery-grid row g-4">
            @forelse($images as $index => $image)
            <div class="col-lg-{{ 12 / ($config['columns'] ?? 3) }} col-md-6">
                <div class="gallery-item"
                     @if(!$isBuilder)
                     data-aos="zoom-in"
                     data-aos-delay="{{ $index * 100 }}"
                     @endif>
                    <div class="gallery-card position-relative overflow-hidden rounded-3 shadow-sm h-100">
                        <img src="{{ Storage::url($image['url']) }}"
                             alt="{{ $image['caption'] ?? 'Gallery Image' }}"
                             class="img-fluid gallery-image w-100 h-100 object-fit-cover">

                        <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                            <div class="gallery-actions">
                                <button class="btn btn-light btn-sm rounded-circle me-2"
                                        onclick="openLightbox({{ $index }})"
                                        title="View Image">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                @if(isset($image['caption']) && $image['caption'])
                                <button class="btn btn-light btn-sm rounded-circle"
                                        title="{{ $image['caption'] }}">
                                    <i class="fas fa-info"></i>
                                </button>
                                @endif
                            </div>
                        </div>

                        @if(isset($image['caption']) && $image['caption'])
                        <div class="gallery-caption position-absolute bottom-0 start-0 w-100 p-3">
                            <p class="mb-0 text-white small">{{ $image['caption'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <!-- Default grid images -->
            @foreach([
                ['url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400', 'caption' => 'Modern Reception Area'],
                ['url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400', 'caption' => 'Consultation Room'],
                ['url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=400', 'caption' => 'Medical Equipment'],
                ['url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400', 'caption' => 'Waiting Area'],
                ['url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400', 'caption' => 'Treatment Room'],
                ['url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=400', 'caption' => 'Laboratory']
            ] as $index => $image)
            <div class="col-lg-{{ 12 / ($config['columns'] ?? 3) }} col-md-6">
                <div class="gallery-item"
                     @if(!$isBuilder)
                     data-aos="zoom-in"
                     data-aos-delay="{{ $index * 100 }}"
                     @endif>
                    <div class="gallery-card position-relative overflow-hidden rounded-3 shadow-sm h-100">
                        <img src="{{ $image['url'] }}"
                             alt="{{ $image['caption'] }}"
                             class="img-fluid gallery-image w-100 h-100 object-fit-cover">

                        <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                            <div class="gallery-actions">
                                <button class="btn btn-light btn-sm rounded-circle me-2"
                                        onclick="openLightbox({{ $index }})"
                                        title="View Image">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <button class="btn btn-light btn-sm rounded-circle"
                                        title="{{ $image['caption'] }}">
                                    <i class="fas fa-info"></i>
                                </button>
                            </div>
                        </div>

                        <div class="gallery-caption position-absolute bottom-0 start-0 w-100 p-3">
                            <p class="mb-0 text-white small">{{ $image['caption'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
        @endif

        <!-- Load More Button -->
        @if(isset($config['show_load_more']) && $config['show_load_more'])
        <div class="row mt-5">
            <div class="col-12 text-center">
                <button class="btn btn-outline-primary btn-lg rounded-pill px-5" id="loadMoreBtn">
                    <i class="fas fa-plus me-2"></i>
                    Load More Images
                </button>
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Lightbox Modal -->
<div class="modal fade" id="galleryLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="lightbox-container text-center">
                    <img id="lightboxImage" src="" alt="" class="img-fluid rounded-3">
                    <div class="lightbox-caption mt-3">
                        <p id="lightboxCaption" class="text-white mb-0"></p>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="lightbox-nav">
                    <button class="btn btn-light rounded-circle lightbox-prev" onclick="navigateLightbox(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-light rounded-circle lightbox-next" onclick="navigateLightbox(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$isBuilder)
<style>
.gallery-section {
    position: relative;
    overflow: hidden;
}

.gallery-section::before {
    content: '';
    position: absolute;
    top: 20%;
    right: -10%;
    width: 200px;
    height: 200px;
    background: linear-gradient(45deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.05;
    z-index: 0;
}

/* Masonry Layout */
.gallery-masonry {
    column-count: 3;
    column-gap: 1.5rem;
    position: relative;
    z-index: 1;
}

.gallery-masonry[data-columns="2"] {
    column-count: 2;
}

.gallery-masonry[data-columns="4"] {
    column-count: 4;
}

.gallery-item {
    break-inside: avoid;
    margin-bottom: 1.5rem;
}

.gallery-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #e2e8f0;
}

.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
    border-color: var(--primary-color, #3b82f6);
}

.gallery-image {
    transition: all 0.3s ease;
    width: 100%;
    height: auto;
    display: block;
}

.gallery-card:hover .gallery-image {
    transform: scale(1.05);
}

.gallery-overlay {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(16, 185, 129, 0.8));
    opacity: 0;
    transition: all 0.3s ease;
}

.gallery-card:hover .gallery-overlay {
    opacity: 1;
}

.gallery-actions {
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.gallery-card:hover .gallery-actions {
    transform: translateY(0);
}

.gallery-caption {
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
    transform: translateY(100%);
    transition: all 0.3s ease;
}

.gallery-card:hover .gallery-caption {
    transform: translateY(0);
}

/* Grid Layout */
.gallery-grid .gallery-card {
    height: 250px;
}

.gallery-grid .gallery-image {
    object-fit: cover;
}

/* Lightbox */
.modal-content {
    background: rgba(0, 0, 0, 0.9) !important;
}

.lightbox-container {
    position: relative;
    max-height: 80vh;
    overflow: hidden;
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 2rem;
    pointer-events: none;
}

.lightbox-prev,
.lightbox-next {
    pointer-events: all;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-prev {
    position: absolute;
    left: 2rem;
}

.lightbox-next {
    position: absolute;
    right: 2rem;
}

/* Responsive Design */
@media (max-width: 992px) {
    .gallery-masonry {
        column-count: 2;
    }

    .gallery-masonry[data-columns="4"] {
        column-count: 3;
    }
}

@media (max-width: 768px) {
    .gallery-masonry {
        column-count: 1;
    }

    .gallery-masonry[data-columns="2"],
    .gallery-masonry[data-columns="3"],
    .gallery-masonry[data-columns="4"] {
        column-count: 1;
    }

    .gallery-grid .col-lg-4,
    .gallery-grid .col-lg-3 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .gallery-card {
        margin-bottom: 2rem;
    }

    .lightbox-nav {
        padding: 0 1rem;
    }

    .lightbox-prev {
        left: 1rem;
    }

    .lightbox-next {
        right: 1rem;
    }
}

/* Loading Animation */
.gallery-image {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

.gallery-image[src] {
    background: none;
    animation: none;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Hover Effects */
.gallery-actions .btn {
    transform: scale(0.8);
    transition: all 0.3s ease;
}

.gallery-card:hover .gallery-actions .btn {
    transform: scale(1);
}

.gallery-actions .btn:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Focus States for Accessibility */
.gallery-card:focus,
.gallery-actions .btn:focus {
    outline: 2px solid var(--primary-color, #3b82f6);
    outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .gallery-overlay {
        background: rgba(0, 0, 0, 0.8);
    }

    .gallery-actions .btn {
        border: 2px solid currentColor;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .gallery-card,
    .gallery-image,
    .gallery-overlay,
    .gallery-actions,
    .gallery-caption {
        transition: none;
    }

    .gallery-card:hover .gallery-image {
        transform: none;
    }
}
</style>

<script>
let currentLightboxIndex = 0;
const galleryImages = @json($images ?: [
    ['url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=800', 'caption' => 'Modern Reception Area'],
    ['url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800', 'caption' => 'Consultation Room'],
    ['url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800', 'caption' => 'Medical Equipment'],
    ['url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=800', 'caption' => 'Waiting Area'],
    ['url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800', 'caption' => 'Treatment Room'],
    ['url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800', 'caption' => 'Laboratory']
]);

function openLightbox(index) {
    currentLightboxIndex = index;
    updateLightboxContent();

    const lightboxModal = new bootstrap.Modal(document.getElementById('galleryLightbox'));
    lightboxModal.show();
}

function updateLightboxContent() {
    const image = galleryImages[currentLightboxIndex];
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');

    if (image) {
        lightboxImage.src = image.url.includes('Storage::url') ? image.url : image.url;
        lightboxImage.alt = image.caption || 'Gallery Image';
        lightboxCaption.textContent = image.caption || '';
    }
}

function navigateLightbox(direction) {
    currentLightboxIndex += direction;

    if (currentLightboxIndex < 0) {
        currentLightboxIndex = galleryImages.length - 1;
    } else if (currentLightboxIndex >= galleryImages.length) {
        currentLightboxIndex = 0;
    }

    updateLightboxContent();
}

// Keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
    const lightboxModal = document.getElementById('galleryLightbox');
    if (lightboxModal.classList.contains('show')) {
        if (e.key === 'ArrowLeft') {
            navigateLightbox(-1);
        } else if (e.key === 'ArrowRight') {
            navigateLightbox(1);
        }
    }
});

// Load more functionality
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            // Simulate loading more images
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            this.disabled = true;

            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-check me-2"></i>All Images Loaded';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
            }, 2000);
        });
    }

    // Lazy loading for images
    const images = document.querySelectorAll('.gallery-image');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    });

    images.forEach(img => {
        if (img.dataset.src) {
            imageObserver.observe(img);
        }
    });
});
</script>
@endif
