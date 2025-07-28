<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $landingPage->getSeoTitle() }}</title>
    <meta name="description" content="{{ $landingPage->getSeoDescription() }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $landingPage->getSeoTitle() }}">
    <meta property="og:description" content="{{ $landingPage->getSeoDescription() }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $landingPage->url }}">
    @if($landingPage->hero_image)
    <meta property="og:image" content="{{ Storage::url($landingPage->hero_image) }}">
    @endif

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--header-bg) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background-color: var(--button-color);
            border-color: var(--button-color);
            color: var(--button-text-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            opacity: 0.9;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .section-padding {
            padding: 80px 0;
        }

        .card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .review-card {
            background: #f8f9fa;
            border-left: 4px solid var(--accent-color);
        }

        .star-rating {
            color: #ffc107;
        }

        .appointment-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .footer {
            background-color: var(--footer-bg);
            color: var(--secondary-color);
        }

        .contact-info i {
            color: var(--primary-color);
            width: 20px;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }

            .hero-image {
                width: 150px;
                height: 150px;
            }

            .section-padding {
                padding: 50px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#home">
                <i class="fas fa-user-md text-primary me-2"></i>
                Dr. {{ $doctor->user->name }}
            </a>
            @if($doctor->is_verified)
                <div class="d-none d-lg-block">
                    <span class="badge bg-success">
                        <i class="fas fa-check-circle me-1"></i>
                        Verified by MedCuraAI
                    </span>
                </div>
            @endif
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    @if($landingPage->section_visibility['about'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['appointments'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#appointments">Book Appointment</a>
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
                        <a class="nav-link" href="#reviews">Reviews</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['contact'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    @if($landingPage->section_visibility['hero'] ?? true)
    <!-- Hero Section -->
    <section id="home" class="hero-section" @if($landingPage->hero_image) style="background-image: url('{{ Storage::url($landingPage->hero_image) }}'); background-size: cover; background-position: center;" @endif>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="text-center text-lg-start">
                        @if($doctor->profile_image)
                        <img src="{{ Storage::url($doctor->profile_image) }}" alt="Dr. {{ $doctor->user->name }}" class="hero-image mb-4">
                        @else
                        <div class="hero-image mb-4 d-flex align-items-center justify-content-center bg-light text-dark mx-auto mx-lg-0">
                            <i class="fas fa-user-md fa-4x"></i>
                        </div>
                        @endif

                        <h1 class="display-4 fw-bold mb-3">Dr. {{ $doctor->user->name }}</h1>
                        <h3 class="h4 mb-4 opacity-90">{{ $doctor->specialty->name ?? 'Medical Professional' }}</h3>

                        @if($landingPage->tagline)
                        <p class="lead mb-4">{{ $landingPage->tagline }}</p>
                        @endif

                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                            @if($landingPage->section_visibility['appointments'] ?? true)
                            <a href="#appointments" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                            </a>
                            @endif
                            @if($landingPage->section_visibility['contact'] ?? true)
                            <a href="#contact" class="btn btn-outline-light btn-lg px-4">
                                <i class="fas fa-phone me-2"></i>Contact Me
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <div class="row g-4 mt-4">
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded p-3">
                                    <h4 class="fw-bold">{{ $doctor->total_reviews ?? 0 }}</h4>
                                    <p class="mb-0">Happy Patients</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded p-3">
                                    <h4 class="fw-bold">{{ number_format($doctor->average_rating ?? 0, 1) }}</h4>
                                    <p class="mb-0">Average Rating</p>
                                </div>
                            </div>
                        </div>
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
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-5">About Dr. {{ $doctor->user->name }}</h2>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="pe-lg-5">
                        @if($landingPage->about_text)
                        <p class="lead">{{ $landingPage->about_text }}</p>
                        @else
                        <p class="lead">{{ $doctor->bio ?? 'Experienced medical professional dedicated to providing quality healthcare.' }}</p>
                        @endif

                        <div class="row g-4 mt-4">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-graduation-cap text-primary fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Specialty</h6>
                                        <p class="mb-0 text-muted">{{ $doctor->specialty->name ?? 'General Practice' }}</p>
                                    </div>
                                </div>
                            </div>
                            @if($doctor->languages)
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-language text-primary fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Languages</h6>
                                        <p class="mb-0 text-muted">{{ implode(', ', $doctor->languages) }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="card text-center p-4">
                                <i class="fas fa-clock text-primary fs-1 mb-3"></i>
                                <h5>{{ $doctor->appointment_duration ?? 30 }} Minutes</h5>
                                <p class="text-muted mb-0">Consultation Time</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card text-center p-4">
                                <i class="fas fa-dollar-sign text-primary fs-1 mb-3"></i>
                                <h5>${{ $doctor->consultation_fee_dollars ?? 'Contact' }}</h5>
                                <p class="text-muted mb-0">Consultation Fee</p>
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
                    <p class="lead">Stay informed with the latest health insights and medical advice from Dr. {{ $doctor->user->name }}.</p>
                </div>
            </div>
            <div class="row">
                @foreach($blogPosts as $post)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            @if($post->featured_image)
                                <img src="{{ Storage::url($post->featured_image) }}"
                                     class="card-img-top"
                                     alt="{{ $post->title }}"
                                     style="height: 200px; object-fit: cover;">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $post->title }}</h5>
                                <p class="card-text text-muted flex-grow-1">
                                    {{ Str::limit($post->short_description, 120) }}
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
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
                                       class="btn btn-primary btn-sm mt-3 w-100">
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
                    <h2 class="display-5 fw-bold mb-4">Book Your Appointment</h2>
                    <p class="lead">Schedule a consultation at your convenience. Choose from available time slots below.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="appointment-form p-5">
                        <form id="appointmentForm" action="{{ route('appointments.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
                            <input type="hidden" name="booking_type" value="guest">

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="guest_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="guest_name" name="guest_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="guest_email" name="guest_email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="guest_phone" name="guest_phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_date_of_birth" class="form-label">Date of Birth *</label>
                                    <input type="date" class="form-control" id="guest_date_of_birth" name="guest_date_of_birth" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_gender" class="form-label">Gender *</label>
                                    <select class="form-select" id="guest_gender" name="guest_gender" required>
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_date" class="form-label">Preferred Date *</label>
                                    <input type="hidden" id="selected_appointment_datetime" name="appointment_date">
                                    <select class="form-select" id="appointment_date_select" required>
                                        <option value="">Select a date</option>
                                        @foreach($availableSlots as $date => $slots)
                                        <option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label">Preferred Time *</label>
                                    <select class="form-select" id="appointment_time" required disabled>
                                        <option value="">Select a date first</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_type" class="form-label">Appointment Type *</label>
                                    <select class="form-select" id="appointment_type" name="appointment_type" required>
                                        <option value="">Select appointment type</option>
                                        <option value="in_person">In-Person Consultation</option>
                                        <option value="video_call">Video Call</option>
                                        <option value="phone_call">Phone Call</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="guest_address" class="form-label">Address</label>
                                    <textarea class="form-control" id="guest_address" name="guest_address" rows="2" placeholder="Your address (optional)"></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label">Reason for Visit *</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Please describe your symptoms or reason for the appointment..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="symptoms" class="form-label">Symptoms (Optional)</label>
                                    <textarea class="form-control" id="symptoms" name="symptoms" rows="2" placeholder="Please describe any symptoms you're experiencing..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="patient_notes" class="form-label">Additional Notes (Optional)</label>
                                    <textarea class="form-control" id="patient_notes" name="patient_notes" rows="2" placeholder="Any additional information you'd like to share..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-calendar-check me-2"></i>Book Appointment
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

    @if(($landingPage->section_visibility['reviews'] ?? true) && $reviews->count() > 0)
    <!-- Reviews Section -->
    <section id="reviews" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">What Patients Say</h2>
                    <p class="lead">Read testimonials from our satisfied patients.</p>
                </div>
            </div>
            <div class="row g-4">
                @foreach($reviews as $review)
                <div class="col-lg-4 col-md-6">
                    <div class="card review-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="star-rating me-3">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star{{ $i <= $review->rating ? '' : ' text-muted' }}"></i>
                                @endfor
                            </div>
                            <small class="text-muted">{{ $review->formatted_date }}</small>
                        </div>
                        <p class="mb-3">"{{ $review->comment }}"</p>
                        <div class="mt-auto">
                            <strong>{{ $review->patient_display_name }}</strong>
                        </div>
                    </div>
                </div>
                @endforeach
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
                    <h2 class="display-5 fw-bold mb-4">Get In Touch</h2>
                    <p class="lead">Have questions? We're here to help.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row g-4">
                        @if($doctor->phone)
                        <div class="col-md-6">
                            <div class="card text-center p-4">
                                <i class="fas fa-phone text-primary fs-1 mb-3"></i>
                                <h5>Phone</h5>
                                <p class="contact-info">
                                    <a href="tel:{{ $doctor->phone }}" class="text-decoration-none">{{ $doctor->phone }}</a>
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($doctor->user->email)
                        <div class="col-md-6">
                            <div class="card text-center p-4">
                                <i class="fas fa-envelope text-primary fs-1 mb-3"></i>
                                <h5>Email</h5>
                                <p class="contact-info">
                                    <a href="mailto:{{ $doctor->user->email }}" class="text-decoration-none">{{ $doctor->user->email }}</a>
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($doctor->full_address)
                        <div class="col-12">
                            <div class="card text-center p-4">
                                <i class="fas fa-map-marker-alt text-primary fs-1 mb-3"></i>
                                <h5>Address</h5>
                                <p class="contact-info">{{ $doctor->full_address }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="footer py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3">Dr. {{ $doctor->user->name }}</h5>
                    <p class="mb-3">{{ $doctor->specialty->name ?? 'Medical Professional' }}</p>
                    @if($doctor->phone || $doctor->user->email)
                    <div class="contact-info">
                        @if($doctor->phone)
                        <p><i class="fas fa-phone me-2"></i> {{ $doctor->phone }}</p>
                        @endif
                        @if($doctor->user->email)
                        <p><i class="fas fa-envelope me-2"></i> {{ $doctor->user->email }}</p>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="col-lg-6 text-lg-end">
                    <p class="mb-2">&copy; {{ date('Y') }} Dr. {{ $doctor->user->name }}. All rights reserved.</p>
                    <p class="small text-muted">Powered by MedCuraAI</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Chat Widget -->
    @include('components.chat-widget', [
        'doctorUsername' => $landingPage->username,
        'doctorName' => $doctor->user->name
    ])

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
                        scrollTop: target.offset().top - 80
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
</body>
</html>
