<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            background-color: var(--footer-bg);
            color: var(--secondary-color);
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
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    @if($landingPage->section_visibility['about'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    @endif
                    @if($landingPage->section_visibility['appointments'] ?? true)
                    <li class="nav-item">
                        <a class="nav-link" href="#appointments">Appointments</a>
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
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="pe-lg-5">
                        <h1 class="display-4 fw-bold mb-4">Dr. {{ $doctor->user->name }}</h1>
                        <h3 class="h4 text-primary mb-4">{{ $doctor->specialty->name ?? 'Medical Professional' }}</h3>

                        @if($landingPage->tagline)
                        <p class="lead mb-4 text-muted">{{ $landingPage->tagline }}</p>
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
                        <img src="{{ Storage::url($doctor->profile_image) }}" alt="Dr. {{ $doctor->user->name }}" class="hero-image">
                        @elseif($landingPage->hero_image)
                        <img src="{{ Storage::url($landingPage->hero_image) }}" alt="Dr. {{ $doctor->user->name }}" class="hero-image">
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
                    <h2 class="display-5 fw-bold mb-4">About Me</h2>
                    <p class="lead text-muted">Learn more about my background and approach to healthcare.</p>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto">
                    <div class="card p-5">
                        @if($landingPage->about_text)
                        <p class="lead mb-4">{{ $landingPage->about_text }}</p>
                        @else
                        <p class="lead mb-4">{{ $doctor->bio ?? 'Experienced medical professional dedicated to providing quality healthcare.' }}</p>
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

    @if(($landingPage->section_visibility['appointments'] ?? true) && !empty($availableSlots))
    <!-- Appointments Section -->
    <section id="appointments" class="section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4">Book Appointment</h2>
                    <p class="lead text-muted">Schedule your consultation with ease.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="appointment-form">
                        <form id="appointmentForm" action="{{ route('appointments.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="patient_name" class="form-label fw-medium">Full Name</label>
                                    <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_email" class="form-label fw-medium">Email Address</label>
                                    <input type="email" class="form-control" id="patient_email" name="patient_email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_phone" class="form-label fw-medium">Phone Number</label>
                                    <input type="tel" class="form-control" id="patient_phone" name="patient_phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_type" class="form-label fw-medium">Appointment Type</label>
                                    <select class="form-select" id="appointment_type" name="appointment_type">
                                        <option value="consultation">General Consultation</option>
                                        <option value="follow_up">Follow-up</option>
                                        <option value="emergency">Urgent Care</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_date" class="form-label fw-medium">Preferred Date</label>
                                    <select class="form-select" id="appointment_date" name="appointment_date" required>
                                        <option value="">Select a date</option>
                                        @foreach($availableSlots as $date => $slots)
                                        <option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label fw-medium">Preferred Time</label>
                                    <select class="form-select" id="appointment_time" name="appointment_time" required disabled>
                                        <option value="">Select a date first</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label fw-medium">Reason for Visit</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Please describe your symptoms or reason for the appointment..."></textarea>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
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
                    <h2 class="display-5 fw-bold mb-4">Patient Reviews</h2>
                    <p class="lead text-muted">What our patients have to say.</p>
                </div>
            </div>
            <div class="row g-4">
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Available slots data
            const availableSlots = @json($availableSlots);

            // Handle date selection
            $('#appointment_date').on('change', function() {
                const selectedDate = $(this).val();
                const $timeSelect = $('#appointment_time');

                $timeSelect.empty().prop('disabled', true);

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
