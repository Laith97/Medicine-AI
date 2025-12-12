@extends('layouts.landing-page')

@section('title', $landingPage->page_title ?: $doctor->user->name . ' - Medical Professional')
@section('description', $landingPage->page_description ?: 'Book an appointment with ' . $doctor->user->name)

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-color: {{ $landingPage->colors['primary'] ?? '#3b82f6' }};
    --secondary-color: {{ $landingPage->colors['secondary'] ?? '#64748b' }};
    --accent-color: {{ $landingPage->colors['accent'] ?? '#10b981' }};
    --button-color: {{ $landingPage->colors['button'] ?? '#3b82f6' }};
    --header-bg: {{ $landingPage->colors['header_bg'] ?? '#ffffff' }};
    --footer-bg: {{ $landingPage->colors['footer_bg'] ?? '#f8fafc' }};
    --primary-font: {{ $landingPage->fonts_config['primary'] ?? 'Inter' }}, sans-serif;
    --heading-font: {{ $landingPage->fonts_config['heading'] ?? 'Poppins' }}, sans-serif;
    --animations-enabled: {{ $landingPage->enable_animations ? '1' : '0' }};
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--primary-font);
    line-height: 1.6;
    color: #374151;
    overflow-x: hidden;
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--heading-font);
    font-weight: 600;
    line-height: 1.2;
}

.btn {
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
    border: none;
    padding: 0.75rem 1.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
    color: white;
}

.btn-outline-primary {
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    background: transparent;
}

.btn-outline-primary:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

/* Custom Navbar */
.custom-navbar {
    background: var(--header-bg);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    padding: 1rem 0;
}

.custom-navbar.scrolled {
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    padding: 0.5rem 0;
}

.navbar-brand {
    font-family: var(--heading-font);
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--primary-color) !important;
}

.navbar-nav .nav-link {
    font-weight: 500;
    color: #374151 !important;
    margin: 0 0.5rem;
    padding: 0.5rem 1rem !important;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover {
    color: var(--primary-color) !important;
    background: rgba(59, 130, 246, 0.1);
}

.navbar-nav .nav-link.active {
    color: var(--primary-color) !important;
    background: rgba(59, 130, 246, 0.1);
}

/* Page Layout Styles */
.layout-fullwidth {
    width: 100%;
    max-width: none;
}

.layout-boxed {
    max-width: 1200px;
    margin: 0 auto;
    box-shadow: 0 0 50px rgba(0, 0, 0, 0.1);
}

.layout-sidebar {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

/* Animation Classes */
@media (prefers-reduced-motion: no-preference) {
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease;
    }

    .animate-on-scroll.animated {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Floating Action Button */
.floating-cta {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 1000;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    border-radius: 50px;
    padding: 1rem 2rem;
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.floating-cta:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.4);
    color: white;
    text-decoration: none;
}

@keyframes pulse {
    0% {
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    }
    50% {
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.5);
    }
    100% {
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    }
}

/* Scroll Progress Bar */
.scroll-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    z-index: 9999;
    transition: width 0.1s ease;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 768px) {
    .layout-sidebar {
        grid-template-columns: 1fr;
    }

    .floating-cta {
        bottom: 1rem;
        right: 1rem;
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }

    .custom-navbar {
        padding: 0.5rem 0;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    :root {
        --header-bg: #1f2937;
        --footer-bg: #111827;
    }

    body {
        background: #111827;
        color: #f9fafb;
    }

    .custom-navbar {
        background: rgba(31, 41, 55, 0.95);
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }

    .navbar-nav .nav-link {
        color: #f9fafb !important;
    }
}
</style>
@endpush

@section('content')
<!-- Scroll Progress Bar -->
<div class="scroll-progress" id="scrollProgress"></div>

<!-- Custom Navigation -->
<nav class="navbar navbar-expand-lg custom-navbar fixed-top" id="mainNavbar">
    <div class="container{{ ($landingPage->page_layout ?? 'default') === 'fullwidth' ? '-fluid' : '' }}">
        <a class="navbar-brand" href="#home">
            @if($doctor->user->profile_photo_path)
                <img src="{{ Storage::url($doctor->user->profile_photo_path) }}"
                     alt="{{ $doctor->user->name }}"
                     class="rounded-circle me-2"
                     style="width: 40px; height: 40px; object-fit: cover;">
            @endif
            {{ $doctor->user->name }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Default Navigation Links -->
                <li class="nav-item">
                    <a class="nav-link" href="#home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#testimonials">Reviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>

                <!-- Custom Navigation Links -->
                @if(isset($landingPage->navbar_config['custom_links']) && is_array($landingPage->navbar_config['custom_links']))
                    @foreach($landingPage->navbar_config['custom_links'] as $link)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ $link['url'] ?? '#' }}"
                           {{ ($link['external'] ?? false) ? 'target="_blank"' : '' }}>
                            @if(isset($link['icon']) && $link['icon'])
                                <i class="{{ $link['icon'] }} me-1"></i>
                            @endif
                            {{ $link['text'] ?? 'Link' }}
                        </a>
                    </li>
                    @endforeach
                @endif

                <li class="nav-item">
                    <a class="nav-link btn btn-primary text-white px-3 ms-2" href="#appointments">
                        Book Appointment
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="main-content layout-{{ $landingPage->page_layout ?? 'default' }}">
    @if($landingPage->page_sections && count($landingPage->page_sections) > 0)
        @foreach($landingPage->page_sections as $section)
            @if(view()->exists('doctor.landing-page.sections.' . $section['type']))
                @include('doctor.landing-page.sections.' . $section['type'], ['section' => $section, 'isBuilder' => false])
            @endif
        @endforeach
    @else
        <!-- Default sections if no custom sections are defined -->
        @include('doctor.landing-page.sections.hero', [
            'section' => [
                'id' => 'hero',
                'type' => 'hero',
                'config' => [
                    'title' => 'Welcome to ' . $doctor->user->name . "'s Practice",
                    'subtitle' => $landingPage->tagline ?: 'Providing quality healthcare with compassion',
                    'background_color' => $landingPage->colors['primary'] ?? '#3b82f6',
                    'text_color' => '#ffffff',
                    'button_text' => 'Book Appointment',
                    'button_link' => '#appointments',
                    'animation' => 'fadeInUp'
                ]
            ],
            'isBuilder' => false
        ])

        @include('doctor.landing-page.sections.about', [
            'section' => [
                'id' => 'about',
                'type' => 'about',
                'config' => [
                    'title' => 'About ' . $doctor->user->name,
                    'content' => $landingPage->about_text ?: $doctor->bio,
                    'layout' => 'image-left',
                    'animation' => 'fadeInLeft'
                ]
            ],
            'isBuilder' => false
        ])

        @include('doctor.landing-page.sections.services', [
            'section' => [
                'id' => 'services',
                'type' => 'services',
                'config' => [
                    'title' => 'Our Services',
                    'subtitle' => 'Comprehensive healthcare solutions',
                    'layout' => 'grid-3',
                    'animation' => 'fadeInUp'
                ]
            ],
            'isBuilder' => false
        ])
    @endif
</main>

<!-- Floating CTA Button -->
<a href="#appointments" class="floating-cta">
    <i class="fas fa-calendar-plus me-2"></i>
    Book Now
</a>

@endsection

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS (Animate On Scroll)
    if ({{ $landingPage->enable_animations ? 'true' : 'false' }}) {
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    }

    // Navbar scroll effect
    const navbar = document.getElementById('mainNavbar');
    const scrollProgress = document.getElementById('scrollProgress');

    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const rate = scrolled / (document.body.scrollHeight - window.innerHeight);

        // Update scroll progress bar
        scrollProgress.style.width = (rate * 100) + '%';

        // Update navbar appearance
        if (scrolled > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
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

    // Intersection Observer for animations
    if ({{ $landingPage->enable_animations ? 'true' : 'false' }}) {
        const landingObserverOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const landingObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                }
            });
        }, landingObserverOptions);

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            landingObserver.observe(el);
        });
    }

    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            heroSection.style.transform = `translateY(${rate}px)`;
        });
    }

    // Custom cursor effect
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    cursor.style.cssText = `
        position: fixed;
        width: 20px;
        height: 20px;
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transition: all 0.1s ease;
        opacity: 0;
    `;
    document.body.appendChild(cursor);

    document.addEventListener('mousemove', function(e) {
        cursor.style.left = e.clientX - 10 + 'px';
        cursor.style.top = e.clientY - 10 + 'px';
        cursor.style.opacity = '0.7';
    });

    document.addEventListener('mouseleave', function() {
        cursor.style.opacity = '0';
    });

    // Interactive elements hover effects
    document.querySelectorAll('.btn, .card, .service-card').forEach(el => {
        el.addEventListener('mouseenter', function() {
            cursor.style.transform = 'scale(2)';
            cursor.style.opacity = '0.3';
        });

        el.addEventListener('mouseleave', function() {
            cursor.style.transform = 'scale(1)';
            cursor.style.opacity = '0.7';
        });
    });

    // Loading animation
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');

        // Animate elements in sequence
        const animateElements = document.querySelectorAll('[data-animate-delay]');
        animateElements.forEach(el => {
            const delay = el.getAttribute('data-animate-delay');
            setTimeout(() => {
                el.classList.add('animate__animated', el.getAttribute('data-animate'));
            }, delay);
        });
    });

    // Performance optimization: Lazy load images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
});
</script>
@endpush
