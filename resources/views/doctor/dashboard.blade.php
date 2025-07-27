@extends('master')

@section('title', 'Doctor Dashboard')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div>
                <h2>Welcome back, Dr. {{ explode(' ', $doctor->user->name)[1] ?? $doctor->user->name }}</h2>
                <p>Here's what's happening with your practice today</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <!-- Today's Appointments -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ $stats['today_appointments'] }}</p>
                    <p class="stats-label">Today's Appointments</p>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <p class="stats-number">{{ $stats['pending_appointments'] }}</p>
                    <p class="stats-label">Pending Approval</p>
                </div>
            </div>

            <!-- Average Rating -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="stats-number">{{ number_format($stats['average_rating'], 1) }}</p>
                    <p class="stats-label">Average Rating</p>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <p class="stats-number">${{ number_format($stats['revenue_this_month'], 0) }}</p>
                    <p class="stats-label">This Month</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Today's Schedule -->
            <div class="col-lg-8 mb-4">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6><i class="fas fa-calendar-day me-2"></i>Today's Schedule</h6>
                        <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
                    </div>

                    @if($todayAppointments->count() > 0)
                        @foreach($todayAppointments as $appointment)
                            <div class="d-flex align-items-center p-3 border rounded mb-3">
                                <!-- Time -->
                                <div class="me-3" style="min-width: 80px;">
                                    <div class="fw-medium">{{ $appointment->appointment_date->format('g:i A') }}</div>
                                    <small class="text-muted">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}min</small>
                                </div>

                                <!-- Patient Info -->
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 me-2">{{ $appointment->patient->name }}</h6>
                                        <span class="badge {{ $appointment->status == 'confirmed' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-1">{{ $appointment->reason }}</p>
                                    <div class="text-muted small">
                                        <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} me-1"></i>
                                        {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div>
                                    <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-smbtn-primary-custom">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <p>No appointments scheduled for today</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="table-card mb-4">
                    <h6 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('doctor.appointments.index') }}" class="btn btn-primary-custom">
                            <i class="fas fa-calendar me-2"></i>View All Appointments
                        </a>
                        <a href="{{ route('doctor.availability.index') }}" class="btn btn-success">
                            <i class="fas fa-clock me-2"></i>Manage Availability
                        </a>
                        <a href="{{ route('doctor.landing-page.index') }}" class="btn btn-warning">
                            <i class="fas fa-globe me-2"></i>Landing Page
                        </a>
                        <a href="{{ route('doctor.reviews.index') }}" class="btn btn-info">
                            <i class="fas fa-star me-2"></i>View Reviews
                        </a>
                    </div>
                </div>

                <!-- Pending Appointments -->
                @if($pendingAppointments->count() > 0)
                    <div class="table-card mb-4">
                        <h6 class="mb-3"><i class="fas fa-clock me-2"></i>Pending Appointments</h6>
                        @foreach($pendingAppointments as $appointment)
                            <div class="alert alert-warning p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium">{{ $appointment->patient->name }}</div>
                                        <small class="text-muted">{{ $appointment->appointment_date->format('M j, g:i A') }}</small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Confirm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-smbtn-primary-custom" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="text-center">
                            <a href="{{ route('doctor.appointments.index', ['status' => 'pending']) }}" class="btn btn-smbtn-primary-custom">
                                View all pending →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Reviews -->
                @if($recentReviews->count() > 0)
                    <div class="table-card mb-4">
                        <h6 class="mb-3"><i class="fas fa-star me-2"></i>Recent Reviews</h6>
                        @foreach($recentReviews as $review)
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="text-warning me-2">
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
                                    <p class="small mb-1">{{ Str::limit($review->comment, 80) }}</p>
                                @endif
                                <small class="text-muted">
                                    by {{ $review->is_anonymous ? 'Anonymous' : $review->patient->name }}
                                </small>
                            </div>
                        @endforeach
                        <div class="text-center">
                            <a href="{{ route('doctor.reviews.index') }}" class="btn btn-smbtn-primary-custom">
                                View all reviews →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Upcoming Appointments -->
                @if($upcomingAppointments->count() > 0)
                    <div class="table-card">
                        <h6 class="mb-3"><i class="fas fa-calendar-week me-2"></i>Upcoming This Week</h6>
                        @foreach($upcomingAppointments as $appointment)
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded mb-2">
                                <div>
                                    <div class="fw-medium">{{ $appointment->patient->name }}</div>
                                    <small class="text-muted">{{ $appointment->appointment_date->format('M j, g:i A') }}</small>
                                </div>
                                <span class="badge {{ $appointment->status == 'confirmed' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
