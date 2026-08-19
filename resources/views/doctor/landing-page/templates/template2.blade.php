<!DOCTYPE html>
<html lang="{{ $language ?? 'en' }}" dir="{{ ($language ?? 'en') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $translatedContent['page_title'] ?: $landingPage->getSeoTitle() }}</title>
    <meta name="description" content="{{ $translatedContent['page_description'] ?: $landingPage->getSeoDescription() }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $translatedContent['page_title'] ?: $landingPage->getSeoTitle() }}">
    <meta property="og:description" content="{{ $translatedContent['page_description'] ?: $landingPage->getSeoDescription() }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $landingPage->url }}">
    @if($landingPage->hero_image)
    <meta property="og:image" content="{{ Storage::disk('public')->url($landingPage->hero_image) }}">
    @endif

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: {{ $landingPage->colors['primary'] }};
            --secondary-color: {{ $landingPage->colors['secondary'] }};
            --accent-color: {{ $landingPage->colors['accent'] }};
            --button-color: {{ $landingPage->colors['button'] }};
            --button-text-color: {{ $landingPage->colors['button_text'] ?? '#ffffff' }};
            --header-bg: {{ $landingPage->colors['header_bg'] }};
            --footer-bg: {{ $landingPage->colors['footer_bg'] }};
            --text-color: {{ $landingPage->colors['text'] ?? '#1f2937' }};
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.7;
        }

        .navbar {
            background-color: var(--header-bg) !important;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .btn-primary {
            background-color: var(--button-color);
            border-color: var(--button-color);
            color: var(--button-text-color);
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 500;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .hero-section {
            padding: 120px 0 80px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
        }

        .hero-image {
            width: 180px;
            height: 180px;
            border-radius: 20px;
            object-fit: cover;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .section-padding {
            padding: 80px 0;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        .review-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            position: relative;
        }

        .review-card::before {
            content: '"';
            position: absolute;
            top: -10px;
            left: 20px;
            font-size: 4rem;
            color: var(--accent-color);
            opacity: 0.3;
        }

        .star-rating {
            color: #ffc107;
            margin-bottom: 1rem;
        }

        .appointment-form {
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 3rem;
        }

        .form-control, .form-select {
            border-radius: 15px;
            border: 2px solid #e5e7eb;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
        }

        .footer {
            background-color: var(--header-bg) !important;
            color: inherit;
            border-top: 1px solid #e5e7eb;
        }

        .contact-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            transform: translateY(-5px);
        }

        .contact-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.5rem;
        }

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 2px solid #f1f5f9;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        /* RTL Support */
        [dir="rtl"] {
            text-align: right;
        }

        [dir="rtl"] .navbar-nav {
            margin-left: 0;
            margin-right: auto;
        }

        [dir="rtl"] .me-2 {
            margin-left: 0.5rem !important;
            margin-right: 0 !important;
        }

        [dir="rtl"] .ms-auto {
            margin-left: 0 !important;
            margin-right: auto !important;
        }

        [dir="rtl"] .pe-lg-5 {
            padding-left: 3rem !important;
            padding-right: 0 !important;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0 60px;
            }

            .hero-image {
                width: 150px;
                height: 150px;
            }

            .section-padding {
                padding: 60px 0;
            }

            .appointment-form {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                Dr. {{ $doctor->user->name }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">{{ $translatedContent['nav_home'] ?: (($language ?? 'en') === 'ar' ? 'الرئيسية' : 'Home') }}</a>
                    </li>
                    @if($landingPage->section_visibility['about'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#about">{{ $translatedContent['nav_about'] ?: (($language ?? 'en') === 'ar' ? 'نبذة عني' : 'About') }}</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['appointments'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#appointments">{{ $translatedContent['nav_appointments'] ?: (($language ?? 'en') === 'ar' ? 'حجز موعد' : 'Appointments') }}</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['health_tips'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#health-tips">Health Tips</a>
                    </li>
                    @endif
                    @if($doctor->publishedBlogPosts()->count() > 0)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('doctor.blogs', $landingPage->username) }}">All Articles</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['reviews'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#reviews">{{ $translatedContent['nav_reviews'] ?: (($language ?? 'en') === 'ar' ? 'آراء المرضى' : 'Reviews') }}</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['contact'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">{{ $translatedContent['nav_contact'] ?: (($language ?? 'en') === 'ar' ? 'اتصل بنا' : 'Contact') }}</a>
                    </li>
                    @endif

                    <!-- Language Switcher -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe me-1"></i>
                            {{ ($language ?? 'en') === 'ar' ? 'العربية' : 'English' }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=en">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="?lang=ar">🇸🇦 العربية</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    @if($landingPage->section_visibility['hero'] ?? true)
    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="pe-lg-5">
                        <h1 class="display-4 fw-bold mb-4">Dr. {{ $doctor->user->name }}</h1>
                        <h3 class="h4 text-primary mb-4">{{ $doctor->specialty->name ?? 'Medical Professional' }}</h3>

                        @if($translatedContent['tagline'] ?: $landingPage->tagline)
                        <p class="lead mb-4 text-muted">{{ $translatedContent['tagline'] ?: $landingPage->tagline }}</p>
                        @endif

                        <div class="d-flex flex-column flex-sm-row gap-3 mb-5">
                            @if($landingPage->section_visibility['appointments'] ?? true)
                            <a href="#appointments" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                            </a>
                            @endif
                            @if($landingPage->section_visibility['contact'] ?? true)
                            <a href="#contact" class="btn btn-outline-primary">
                                <i class="fas fa-phone me-2"></i>Contact
                            </a>
                            @endif
                        </div>

                        <div class="row g-4">
                            <div class="col-6">
                                <div class="stats-card">
                                    <div class="stats-number">{{ $doctor->total_reviews ?? 0 }}</div>
                                    <p class="mb-0 text-muted">Happy Patients</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card">
                                    <div class="stats-number">{{ number_format($doctor->average_rating ?? 0, 1) }}</div>
                                    <p class="mb-0 text-muted">Rating</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        @if($doctor->profile_image)
                        <img src="{{ Storage::disk('public')->url($doctor->profile_image) }}" alt="Dr. {{ $doctor->user->name }}" class="hero-image">
                        @elseif($landingPage->hero_image)
                        <img src="{{ Storage::disk('public')->url($landingPage->hero_image) }}" alt="Dr. {{ $doctor->user->name }}" class="hero-image">
                        @else
                        <div class="hero-image d-flex align-items-center justify-content-center bg-light text-muted mx-auto">
                            <i class="fas fa-user-md fa-4x"></i>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($landingPage->section_visibility['about'] ?? true)
    <!-- About Section -->
    <section id="about" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">{{ $translatedContent['about_title'] ?: (($language ?? 'en') === 'ar' ? 'نبذة عني' : 'About Me') }}</h2>
                    <p class="lead text-muted">Learn more about my background and approach to healthcare.</p>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto">
                    <div class="card p-5">
                        @if($translatedContent['about_text'] ?: $landingPage->about_text)
                        <p class="lead mb-4">{{ $translatedContent['about_text'] ?: $landingPage->about_text }}</p>
                        @else
                        <p class="lead mb-4">{{ $doctor->bio ?? (($language ?? 'en') === 'ar' ? 'طبيب محترف ذو خبرة مكرس لتقديم رعاية صحية عالية الجودة.' : 'Experienced medical professional dedicated to providing quality healthcare.') }}</p>
                        @endif

                        <div class="row g-4 mt-3">
                            <div class="col-md-4 text-center">
                                <div class="feature-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h6 class="fw-bold">Specialty</h6>
                                <p class="text-muted mb-0">{{ $doctor->specialty->name ?? 'General Practice' }}</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="feature-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h6 class="fw-bold">Consultation</h6>
                                <p class="text-muted mb-0">{{ $doctor->appointment_duration ?? 30 }} Minutes</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="feature-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <h6 class="fw-bold">Fee</h6>
                                <p class="text-muted mb-0">${{ $doctor->consultation_fee_dollars ?? 'Contact' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if(($landingPage->section_visibility['health_tips'] ?? true) && $blogPosts->count() > 0)
    <!-- Health Tips Section -->
    <section id="health-tips" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">Health Tips & Articles</h2>
                    <p class="lead text-muted">Stay informed with the latest health insights and medical advice.</p>
                </div>
            </div>
            <div class="row g-4">
                @foreach($blogPosts as $post)
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            @if($post->featured_image)
                                <img src="{{ Storage::disk('public')->url($post->featured_image) }}"
                                     class="card-img-top"
                                     alt="{{ $post->title }}"
                                     style="height: 200px; object-fit: cover;">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold">{{ $post->title }}</h5>
                                <p class="card-text text-muted flex-grow-1">
                                    {{ Str::limit($post->short_description, 120) }}
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {{ $post->published_at->format('M j, Y') }}
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $post->reading_time }}
                                        </small>
                                    </div>
                                    <a href="{{ route('doctor.blog.post', [$landingPage->username, $post->slug]) }}"
                                       class="btn btn-primary btn-sm w-100">
                                        Read More <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($doctor->publishedBlogPosts()->count() > 3)
                <div class="row">
                    <div class="col-12 text-center mt-4">
                        <a href="{{ route('doctor.blogs', $landingPage->username) }}"
                           class="btn btn-outline-primary">
                            View All Articles <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>
    @endif

    @if(($landingPage->section_visibility['appointments'] ?? true) && !empty($availableSlots))
    <!-- Appointments Section -->
    <section id="appointments" class="section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">{{ $translatedContent['appointment_title'] ?: (($language ?? 'en') === 'ar' ? 'احجز موعد' : 'Book Appointment') }}</h2>
                    <p class="lead text-muted">{{ $translatedContent['appointment_subtitle'] ?: (($language ?? 'en') === 'ar' ? 'حدد موعد استشارة بسهولة.' : 'Schedule your consultation with ease.') }}</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="appointment-form">
                        <form id="appointmentForm" action="{{ route('appointments.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
                            <input type="hidden" name="booking_type" value="guest">

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="guest_name" class="form-label fw-medium">{{ $translatedContent['form_name_label'] ?: (($language ?? 'en') === 'ar' ? 'الاسم الكامل *' : 'Full Name *') }}</label>
                                    <input type="text" class="form-control" id="guest_name" name="guest_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_email" class="form-label fw-medium">{{ $translatedContent['form_email_label'] ?: (($language ?? 'en') === 'ar' ? 'البريد الإلكتروني *' : 'Email Address *') }}</label>
                                    <input type="email" class="form-control" id="guest_email" name="guest_email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_phone" class="form-label fw-medium">{{ $translatedContent['form_phone_label'] ?: (($language ?? 'en') === 'ar' ? 'رقم الهاتف *' : 'Phone Number *') }}</label>
                                    <input type="tel" class="form-control" id="guest_phone" name="guest_phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_date_of_birth" class="form-label fw-medium">Date of Birth *</label>
                                    <input type="date" class="form-control" id="guest_date_of_birth" name="guest_date_of_birth" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_gender" class="form-label fw-medium">Gender *</label>
                                    <select class="form-select" id="guest_gender" name="guest_gender" required>
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_type" class="form-label fw-medium">Appointment Type *</label>
                                    <select class="form-select" id="appointment_type" name="appointment_type" required>
                                        <option value="">Select appointment type</option>
                                        @php
                                            $appointmentTypeLabels = [
                                                'in_person' => 'In-Person Consultation',
                                                'video_call' => 'Video Call',
                                                'phone_call' => 'Phone Call'
                                            ];
                                        @endphp
                                        @foreach($doctor->getEnabledAppointmentTypes() as $type)
                                            <option value="{{ $type }}">{{ $appointmentTypeLabels[$type] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_date" class="form-label fw-medium">{{ $translatedContent['form_date_label'] ?: (($language ?? 'en') === 'ar' ? 'التاريخ المفضل *' : 'Preferred Date *') }}</label>
                                    <input type="hidden" id="selected_appointment_datetime" name="appointment_date">
                                    <select class="form-select" id="appointment_date_select" required>
                                        <option value="">{{ ($language ?? 'en') === 'ar' ? 'اختر تاريخاً' : 'Select a date' }}</option>
                                        @foreach($availableSlots as $date => $slots)
                                        <option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label fw-medium">{{ $translatedContent['form_time_label'] ?: (($language ?? 'en') === 'ar' ? 'الوقت المفضل *' : 'Preferred Time *') }}</label>
                                    <select class="form-select" id="appointment_time" required disabled>
                                        <option value="">{{ ($language ?? 'en') === 'ar' ? 'اختر تاريخاً أولاً' : 'Select a date first' }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="guest_address" class="form-label fw-medium">Address</label>
                                    <textarea class="form-control" id="guest_address" name="guest_address" rows="2" placeholder="Your address (optional)"></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label fw-medium">Reason for Visit *</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Please describe your symptoms or reason for the appointment..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="symptoms" class="form-label fw-medium">Symptoms (Optional)</label>
                                    <textarea class="form-control" id="symptoms" name="symptoms" rows="2" placeholder="Please describe any symptoms you're experiencing..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="patient_notes" class="form-label fw-medium">{{ $translatedContent['form_message_label'] ?: (($language ?? 'en') === 'ar' ? 'ملاحظات إضافية (اختيارية)' : 'Additional Notes (Optional)') }}</label>
                                    <textarea class="form-control" id="patient_notes" name="patient_notes" rows="2" placeholder="{{ ($language ?? 'en') === 'ar' ? 'أي معلومات إضافية تود مشاركتها...' : 'Any additional information you\'d like to share...' }}"></textarea>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-calendar-check me-2"></i>{{ $translatedContent['form_submit_button'] ?: (($language ?? 'en') === 'ar' ? 'احجز موعد' : 'Book Appointment') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($landingPage->section_visibility['reviews'] ?? true)
    <!-- Reviews Section -->
    <section id="reviews" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">{{ ($language ?? 'en') === 'ar' ? 'آراء المرضى' : 'Patient Reviews' }}</h2>
                    <p class="lead text-muted">{{ ($language ?? 'en') === 'ar' ? 'ما يقوله مرضانا.' : 'What our patients have to say.' }}</p>
                </div>
            </div>
            <div class="row g-4">
                @if($reviews->count() > 0)
                    @foreach($reviews as $review)
                    <div class="col-lg-4 col-md-6">
                        <div class="review-card h-100">
                            <div class="star-rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star{{ $i <= $review->rating ? '' : ' text-muted' }}"></i>
                                @endfor
                            </div>
                            <p class="mb-4">{{ $review->comment }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $review->patient_display_name }}</strong>
                                <small class="text-muted">{{ $review->formatted_date }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">{{ ($language ?? 'en') === 'ar' ? 'لا توجد مراجعات بعد' : 'No reviews yet' }}</h5>
                            <p class="text-muted">{{ ($language ?? 'en') === 'ar' ? 'كن أول من يترك مراجعة!' : 'Be the first to leave a review!' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if($landingPage->section_visibility['contact'] ?? true)
    <!-- Contact Section -->
    <section id="contact" class="section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">Contact Information</h2>
                    <p class="lead text-muted">Get in touch with us.</p>
                </div>
            </div>
            <div class="row justify-content-center g-4">
                @if($doctor->phone)
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Phone</h5>
                        <p class="mb-0">
                            <a href="tel:{{ $doctor->phone }}" class="text-decoration-none text-muted">{{ $doctor->phone }}</a>
                        </p>
                    </div>
                </div>
                @endif

                @if($doctor->user->email)
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Email</h5>
                        <p class="mb-0">
                            <a href="mailto:{{ $doctor->user->email }}" class="text-decoration-none text-muted">{{ $doctor->user->email }}</a>
                        </p>
                    </div>
                </div>
                @endif

                @if($doctor->full_address)
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Address</h5>
                        <p class="mb-0 text-muted">{{ $doctor->full_address }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="footer py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-2">Dr. {{ $doctor->user->name }}</h5>
                    <p class="mb-0 text-muted">{{ $doctor->specialty->name ?? 'Medical Professional' }}</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <p class="mb-1">&copy; {{ date('Y') }} Dr. {{ $doctor->user->name }}. All rights reserved.</p>
                    <p class="small text-muted mb-0">Powered by MedCuraAI</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Chat Widget -->
    @if($landingPage->section_visibility['chat_widget'] ?? true)
    @include('components.chat-widget', [
        'doctorUsername' => $landingPage->username,
        'doctorName' => $doctor->user->name
    ])
    @endif

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Available slots data
            const availableSlots = @json($availableSlots);

            // Handle date selection
            $('#appointment_date_select').on('change', function() {
                const selectedDate = $(this).val();
                const $timeSelect = $('#appointment_time');

                $timeSelect.empty().prop('disabled', true);
                $('#selected_appointment_datetime').val('');

                if (selectedDate && availableSlots[selectedDate]) {
                    $timeSelect.append('<option value="">Select a time</option>');

                    availableSlots[selectedDate].forEach(function(slot) {
                        $timeSelect.append(`<option value="${slot.datetime}">${slot.start_time} - ${slot.end_time}</option>`);
                    });

                    $timeSelect.prop('disabled', false);
                } else {
                    $timeSelect.append('<option value="">No slots available</option>');
                }
            });

            // Handle time selection
            $('#appointment_time').on('change', function() {
                const selectedDateTime = $(this).val();
                $('#selected_appointment_datetime').val(selectedDateTime);
            });

            // Smooth scrolling for navigation links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $($(this).attr('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            });

            // Form submission
            $('#appointmentForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();
                const $submitBtn = $(this).find('button[type="submit"]');
                const originalText = $submitBtn.html();

                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Booking...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        alert('Appointment booked successfully! You will receive a confirmation email shortly.');
                        $('#appointmentForm')[0].reset();
                        $('#appointment_time').empty().prop('disabled', true).append('<option value="">Select a date first</option>');
                        $('#selected_appointment_datetime').val('');
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while booking your appointment.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMessage = errors.join('\n');
                        }
                        alert(errorMessage);
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
