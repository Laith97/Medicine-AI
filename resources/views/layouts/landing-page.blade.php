<!DOCTYPE html>
<html lang="{{ $language ?? 'en' }}" dir="{{ ($language ?? 'en') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>@yield('title', 'Medical Professional')</title>
    <meta name="description" content="@yield('description', 'Professional medical services')">
    <meta name="keywords" content="doctor, medical, healthcare, appointment, {{ $doctor->specialty->name ?? 'medical professional' }}">
    <meta name="author" content="{{ $doctor->user->name ?? 'Medical Professional' }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('title', 'Medical Professional')">
    <meta property="og:description" content="@yield('description', 'Professional medical services')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    @if($landingPage->hero_image)
    <meta property="og:image" content="{{ Storage::url($landingPage->hero_image) }}">
    @endif

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Medical Professional')">
    <meta name="twitter:description" content="@yield('description', 'Professional medical services')">
    @if($landingPage->hero_image)
    <meta name="twitter:image" content="{{ Storage::url($landingPage->hero_image) }}">
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Styles -->
    @stack('styles')

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MedicalBusiness",
        "name": "{{ $doctor->user->name }}",
        "description": "{{ $landingPage->page_description ?? 'Professional medical services' }}",
        @if($doctor->specialty)
        "medicalSpecialty": "{{ $doctor->specialty->name }}",
        @endif
        @if($doctor->user->profile_photo_path)
        "image": "{{ Storage::url($doctor->user->profile_photo_path) }}",
        @endif
        "url": "{{ request()->url() }}",
        "telephone": "{{ $doctor->phone ?? '' }}",
        "email": "{{ $doctor->user->email }}",
        @if($doctor->address)
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "{{ $doctor->address }}"
        },
        @endif
        "priceRange": "$",
        "openingHours": [
            @if($doctor->availability)
                @foreach($doctor->availability as $day => $hours)
                    @if($hours['is_available'])
                    "{{ ucfirst($day) }} {{ $hours['start_time'] }}-{{ $hours['end_time'] }}"{{ !$loop->last ? ',' : '' }}
                    @endif
                @endforeach
            @endif
        ]
    }
    </script>

    <!-- Analytics -->
    @if(config('services.google_analytics.tracking_id'))
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.tracking_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.google_analytics.tracking_id') }}');
    </script>
    @endif

    <!-- Custom CSS from page builder -->
    @if(isset($landingPage->custom_css) && $landingPage->custom_css)
    <style>
        {!! $landingPage->custom_css !!}
    </style>
    @endif
</head>

<body class="landing-page-body">
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <p class="loading-text mt-3">Loading...</p>
        </div>
    </div>

    <!-- Skip to main content (accessibility) -->
    <a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>

    <!-- Main Content -->
    <div id="main-content">
        @yield('content')
    </div>

    <!-- Cookie Consent Banner -->
    <div id="cookieConsent" class="cookie-consent" style="display: none;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0">
                        <i class="fas fa-cookie-bite me-2"></i>
                        We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-sm btn-outline-light me-2" onclick="showCookieSettings()">
                        Settings
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="acceptCookies()">
                        Accept
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" title="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    @stack('scripts')

    <!-- Base JavaScript -->
    <script>
        // Loading screen
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            loadingScreen.style.opacity = '0';
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500);
        });

        // Back to top button
        const backToTopButton = document.getElementById('backToTop');

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });

        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Cookie consent
        function checkCookieConsent() {
            if (!localStorage.getItem('cookieConsent')) {
                setTimeout(() => {
                    document.getElementById('cookieConsent').style.display = 'block';
                }, 2000);
            }
        }

        function acceptCookies() {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookieConsent').style.display = 'none';
        }

        function showCookieSettings() {
            // Implement cookie settings modal
            alert('Cookie settings would be implemented here');
        }

        // Initialize cookie consent check
        checkCookieConsent();

        // Accessibility improvements
        document.addEventListener('keydown', function(e) {
            // ESC key to close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                });
            }
        });

        // Focus management for modals
        document.addEventListener('shown.bs.modal', function(e) {
            const modal = e.target;
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        });

        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(() => {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    // console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                }, 0);
            });
        }

        // Error handling
        window.addEventListener('error', function(e) {
            // console.error('JavaScript error:', e.error);
            // You could send this to an error tracking service
        });

        // Service Worker registration (for PWA features)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        // console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        // console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>

    <style>
        /* Loading Screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .loading-text {
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Skip Link */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            z-index: 10000;
        }

        .skip-link:focus {
            top: 6px;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Cookie Consent */
        .cookie-consent {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 1rem 0;
            z-index: 1000;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }

        /* Accessibility improvements */
        .visually-hidden-focusable:not(:focus):not(:focus-within) {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .btn {
                border: 2px solid currentColor;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print styles */
        @media print {
            .loading-screen,
            .back-to-top,
            .cookie-consent,
            .floating-cta,
            .navbar {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
            }
        }
    </style>
</body>
</html>
