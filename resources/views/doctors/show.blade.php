@extends('master')

@section('title', $doctor->user->name . ' - Doctor Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
.profile-hero {
    background: linear-gradient(135deg, #2c3e50 0%, #c55252 100%);
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.profile-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
    overflow: hidden;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.rating-stars {
    color: #ffc107;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    height: 100%;
}

.info-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.availability-day {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #667eea;
}

.time-slot {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    color: #1976d2;
    border-radius: 6px;
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.booking-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    position: sticky;
    top: 2rem;
}

.btn-book {
    background: linear-gradient(135deg, #2c3e50 0%, #c55252 100%);
    border: none;
    border-radius: 12px;
    padding: 1rem 2rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-book:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.review-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border-left: 4px solid #667eea;
    margin-bottom: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.contact-item:last-child {
    border-bottom: none;
}

.quick-action {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    color: #6c757d;
    transition: all 0.2s ease;
    border: 1px solid #e9ecef;
    margin-bottom: 0.5rem;
}

.quick-action:hover {
    background: #f8f9fa;
    color: #495057;
    transform: translateX(4px);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #667eea;
    display: inline-block;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Back Navigation -->
    <div class="container mb-3">
    </div>

    <!-- Profile Hero Section -->
    <div class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center text-white">
                        <div class="me-4">
                            @if($doctor->profile_image)
                                <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                     alt="{{ $doctor->user->name }}"
                                     class="profile-avatar"
                                     style="object-fit: cover;">
                            @else
                                <div class="profile-avatar bg-white d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-md text-primary fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h1 class="mb-2">Dr. {{ $doctor->user->name }}</h1>
                            <h2 class="h4 mb-3 text-white-75">{{ $doctor->specialty->name }}</h2>

                            <!-- Rating -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="rating-stars me-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($doctor->average_rating))
                                            <i class="fas fa-star"></i>
                                        @elseif($i - 0.5 <= $doctor->average_rating)
                                            <i class="fas fa-star-half-alt"></i>
                                        @else
                                            <i class="far fa-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span class="text-white-75">
                                    {{ number_format($doctor->average_rating, 1) }} ({{ $doctor->total_reviews }} reviews)
                                </span>
                            </div>

                            @if($doctor->is_verified)
                                <span class="badge bg-success px-3 py-2">
                                    <i class="fas fa-check-circle me-1"></i>Verified Doctor
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Consultation Fee hidden for clinic SaaS --}}
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-icon bg-primary text-white mx-auto">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="h5 mb-1">{{ $doctor->appointment_duration }} min</h3>
                            <p class="text-muted small mb-0">Appointment Duration</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-icon bg-success text-white mx-auto">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3 class="h5 mb-1">{{ count($availableSlots) }}</h3>
                            <p class="text-muted small mb-0">Available Days</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-icon bg-info text-white mx-auto">
                                <i class="fas fa-language"></i>
                            </div>
                            <h3 class="h5 mb-1">{{ count($doctor->languages ?? ['English']) }}</h3>
                            <p class="text-muted small mb-0">Languages</p>
                        </div>
                    </div>
                </div>

                <!-- About Section -->
                <div class="profile-card mb-4">
                    <div class="p-4">
                        <h2 class="section-title">About Dr. {{ explode(' ', $doctor->user->name)[1] ?? $doctor->user->name }}</h2>
                        <p class="text-muted lh-lg">{{ $doctor->bio }}</p>

                        <div class="mt-4">
                            <h3 class="h6 text-muted mb-3">LANGUAGES SPOKEN</h3>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($doctor->languages ?? ['English'] as $language)
                                    <span class="badge bg-light text-dark px-3 py-2">{{ $language }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="profile-card mb-4">
                    <div class="p-4">
                        <h2 class="section-title">Contact Information</h2>
                        <div>
                            @if($doctor->phone)
                                <div class="contact-item">
                                    <i class="fas fa-phone text-primary me-3" style="width: 20px;"></i>
                                    <span>{{ $doctor->phone }}</span>
                                </div>
                            @endif
                            <div class="contact-item">
                                <i class="fas fa-envelope text-primary me-3" style="width: 20px;"></i>
                                <span>{{ $doctor->user->email }}</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt text-primary me-3" style="width: 20px;"></i>
                                <span>{{ $doctor->full_address }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Availability Schedule -->
                <div class="profile-card mb-4">
                    <div class="p-4">
                        <h2 class="section-title">Weekly Schedule</h2>
                        @php
                            $daysOfWeek = [
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            ];
                            $groupedSlots = $doctor->availabilitySlots->groupBy('day_of_week');
                        @endphp

                        <div class="row g-3">
                            @foreach($daysOfWeek as $day => $dayName)
                                <div class="col-md-6">
                                    <div class="availability-day">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold">{{ $dayName }}</span>
                                            <div class="text-end">
                                                @if($groupedSlots->has($day))
                                                    @foreach($groupedSlots[$day] as $timeSlot)
                                                        <div class="time-slot d-inline-block me-1 mb-1">
                                                            {{ date('g:i A', strtotime($timeSlot->start_time)) }} - {{ date('g:i A', strtotime($timeSlot->end_time)) }}
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted small">Unavailable</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="profile-card">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="section-title mb-0">Patient Reviews</h2>
                            <a href="{{ route('doctors.reviews', $doctor) }}" class="btn btn-outline-primary btn-sm">
                                View All Reviews
                            </a>
                        </div>

                        @if($doctor->approvedReviews->count() > 0)
                            @foreach($doctor->approvedReviews->take(3) as $review)
                                <div class="review-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="rating-stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    <i class="fas fa-star small"></i>
                                                @else
                                                    <i class="far fa-star small"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                    @if($review->comment)
                                        <p class="mb-2">{{ $review->comment }}</p>
                                    @endif
                                    <small class="text-muted">
                                        - {{ $review->is_anonymous ? 'Anonymous Patient' : ($review->patient->name ?? 'Unknown Patient') }}
                                    </small>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-comments fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted">No reviews available yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <div class="col-lg-4">
                <div class="booking-card">
                    @auth
                        @if(count($availableSlots) > 0)
                            <h3 class="h4 mb-4 text-center">Book Appointment</h3>

                            <div class="mb-4">
                                <p class="small text-muted mb-3">Next available slots:</p>
                                <div class="overflow-auto" style="max-height: 300px;">
                                    @foreach($availableSlots as $date => $slots)
                                        <div class="mb-3 p-3 border rounded">
                                            <div class="fw-semibold mb-2 text-primary">
                                                {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                                                <small class="text-muted">({{ \Carbon\Carbon::parse($date)->format('l') }})</small>
                                            </div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($slots->take(6) as $timeSlot)
                                                    <button class="btn btn-outline-primary btn-sm">
                                                        {{ \Carbon\Carbon::parse($timeSlot['start_time'])->format('g:i A') }}
                                                    </button>
                                                @endforeach
                                                @if($slots->count() > 6)
                                                    <small class="text-muted align-self-center ms-2">
                                                        +{{ $slots->count() - 6 }} more
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('appointments.create', $doctor) }}" class="btn btn-book text-white w-100 mb-4">
                                <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment
                            </a>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fs-1 text-muted mb-3 d-block"></i>
                                <h4 class="h5 mb-2">No Available Slots</h4>
                                <p class="text-muted small">Please check back later or contact the office directly.</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-user-lock fs-1 text-muted mb-3 d-block"></i>
                            <h4 class="h5 mb-3">Login Required</h4>
                            <p class="text-muted mb-4">Please sign in to book an appointment</p>
                            <a href="{{ route('login') }}" class="btn btn-book text-white w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In to Book
                            </a>
                        </div>
                    @endauth

                    <!-- Quick Actions -->
                    <div class="mt-4 pt-4 border-top">
                        <h4 class="h6 text-muted mb-3">QUICK ACTIONS</h4>
                        <div>
                            <a href="{{ route('doctors.reviews', $doctor) }}" class="quick-action">
                                <i class="fas fa-star me-3"></i>
                                <span>Read Reviews</span>
                            </a>
                            <a href="mailto:{{ $doctor->user->email }}" class="quick-action">
                                <i class="fas fa-envelope me-3"></i>
                                <span>Send Email</span>
                            </a>
                            @if($doctor->phone)
                                <a href="tel:{{ $doctor->phone }}" class="quick-action">
                                    <i class="fas fa-phone me-3"></i>
                                    <span>Call Office</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
