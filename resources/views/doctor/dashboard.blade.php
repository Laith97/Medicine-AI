@extends('master')

@section('title', 'Doctor Dashboard')

@push('styles')
@vite(['resources/css/dashboard-enhancements.css'])
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '🏥';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Chain Impersonation Notice -->
        @if(session('impersonating_admin_id') && session('impersonating_hospital_admin_id') && session('hospital_admin_impersonation_started_at') && !empty(session('hospital_admin_impersonation_started_at')))
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users fa-lg mr-3"></i>
                        <div>
                            <strong>Chain Impersonation Mode</strong>
                            <p class="mb-0 small">
                                <strong>{{ session('impersonating_admin_name', 'Admin') }}</strong> →
                                <strong>{{ session('impersonating_hospital_admin_name') }}</strong> →
                                <strong>Dr. {{ auth()->user()->name }}</strong>
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('return-to-hospital-admin') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i>Return to Hospital Admin
                            </button>
                        </form>
                        <form method="POST" action="{{ route('return-to-admin') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-up mr-1"></i>Return to Admin
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(session('impersonating_hospital_admin_id') && empty(session('impersonating_admin_id')))
            <!-- Direct Hospital Admin Impersonation -->
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-shield fa-lg mr-3"></i>
                        <div>
                            <strong>Hospital Admin Mode</strong>
                            <p class="mb-0 small">You are viewing this dashboard as <strong>{{ session('impersonating_hospital_admin_name') }}</strong> (Hospital Admin)</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('return-to-hospital-admin') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Return to Hospital Admin
                        </button>
                    </form>
                </div>
            </div>
        @endif

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
                <div class="stats-card" role="region" aria-label="Today's appointments">
                    @if($stats['today_appointments'] > 0)
                        <span class="notification-pulse"></span>
                    @endif
                    <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ $stats['today_appointments'] }}</p>
                    <p class="stats-label">Today's Appointments</p>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card" role="region" aria-label="Pending appointments">
                    @if($stats['pending_appointments'] > 0)
                        <span class="notification-pulse"></span>
                    @endif
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

        <!-- Second Row of Stats -->
        <div class="row">
            <!-- Total Notes -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <p class="stats-number">{{ $stats['total_notes'] }}</p>
                    <p class="stats-label">Total Notes</p>
                </div>
            </div>

            <!-- Voice Notes -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #fd7e14 0%, #e55a4e 100%);">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <p class="stats-number">{{ $stats['voice_notes'] }}</p>
                    <p class="stats-label">Voice Notes</p>
                </div>
            </div>

            <!-- Notes This Month -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <p class="stats-number">{{ $stats['this_month_notes'] }}</p>
                    <p class="stats-label">Notes This Month</p>
                </div>
            </div>

            <!-- Quick Add Note -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card clickable-card" onclick="window.location.href='{{ route('doctor.notes.create') }}'">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-plus"></i>
                    </div>
                    <p class="stats-number">+</p>
                    <p class="stats-label">Add New Note</p>
                </div>
            </div>
        </div>

        <!-- Recently Completed Appointments -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="table-card">
                    <h6 class="mb-3"><i class="fas fa-check-circle me-2"></i>Recently Completed Appointments</h6>
                    @if(isset($recentCompletedAppointments) && $recentCompletedAppointments->count() > 0)
                        @foreach($recentCompletedAppointments->take(5) as $appointment)
                            <div class="d-flex align-items-center p-3 border rounded mb-3 bg-light-success">
                                <!-- Completion Info -->
                                <div class="me-3" style="min-width: 100px;">
                                    <div class="fw-medium text-success">{{ $appointment->completed_at ? $appointment->completed_at->format('M j, g:i A') : $appointment->appointment_end->format('M j, g:i A') }}</div>
                                    <small class="text-muted">Completed</small>
                                </div>
                                <!-- Patient Info -->
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $appointment->patient_name }}</h6>
                                    <p class="text-muted small mb-1">{{ $appointment->reason }}</p>
                                    <small class="text-muted">{{ $appointment->appointment_date->format('M j, Y') }}</small>
                                </div>
                                <!-- Actions -->
                                <div class="d-flex gap-2">
                                    <a href="{{ route('doctor.appointments.show', $appointment) }}#analytics" class="btn btn-sm btn-success">
                                        <i class="fas fa-brain me-1"></i>AI Analytics
                                    </a>
                                    <a href="{{ route('doctor.appointments.show', $appointment) }}#prescription" class="btn btn-sm btn-primary">
                                        <i class="fas fa-prescription me-1"></i>Prescription
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>No completed appointments yet</p>
                        </div>
                    @endif
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
                            <div class="d-flex align-items-center p-3 border rounded mb-3 appointment-card">
                                <!-- Time -->
                                <div class="me-3" style="min-width: 80px;">
                                    <div class="fw-medium">{{ $appointment->appointment_date->format('g:i A') }}</div>
                                    <small class="text-muted">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}min</small>
                                </div>

                                <!-- Patient Info -->
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 me-2">{{ $appointment->patient_name }}</h6>
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
                                    <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-sm btn-primary-custom">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state-enhanced">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h5>No appointments today</h5>
                            <p>Your schedule is clear. Time to catch up on other tasks!</p>
                            <a href="{{ route('doctor.appointments.create') }}" class="btn btn-primary-custom">
                                <i class="fas fa-plus me-2"></i>Schedule Appointment
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="table-card mb-4">
                    <h6 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('doctor.appointments.index') }}" class="quick-action-enhanced">
                            <div class="action-icon"><i class="fas fa-calendar"></i></div>
                            <div class="action-content">
                                <h6>View Appointments</h6>
                                <p>Manage your schedule</p>
                            </div>
                            <div class="action-arrow"><i class="fas fa-chevron-right"></i></div>
                        </a>
                        <a href="{{ route('doctor.availability.index') }}" class="quick-action-enhanced">
                            <div class="action-icon" style="background: linear-gradient(135deg, #28a745, #20c997);"><i class="fas fa-clock"></i></div>
                            <div class="action-content">
                                <h6>Manage Availability</h6>
                                <p>Set working hours</p>
                            </div>
                            <div class="action-arrow"><i class="fas fa-chevron-right"></i></div>
                        </a>
                        <a href="{{ route('doctor.notes.create') }}" class="quick-action-enhanced">
                            <div class="action-icon" style="background: linear-gradient(135deg, #28a745, #20c997);"><i class="fas fa-plus"></i></div>
                            <div class="action-content">
                                <h6>Add Note</h6>
                                <p>Create patient note</p>
                            </div>
                            <div class="action-arrow"><i class="fas fa-chevron-right"></i></div>
                        </a>
                        <a href="{{ route('doctor.reviews.index') }}" class="quick-action-enhanced">
                            <div class="action-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);"><i class="fas fa-star"></i></div>
                            <div class="action-content">
                                <h6>View Reviews</h6>
                                <p>Patient feedback</p>
                            </div>
                            <div class="action-arrow"><i class="fas fa-chevron-right"></i></div>
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
                                        <div class="fw-medium">{{ $appointment->patient_name }}</div>
                                        <small class="text-muted">{{ $appointment->appointment_date->format('M j, g:i A') }}</small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Confirm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-sm btn-primary-custom" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="text-center">
                            <a href="{{ route('doctor.appointments.index', ['status' => 'pending']) }}" class="btn btn-sm btn-primary-custom">
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
                                    by {{ $review->is_anonymous ? 'Anonymous' : $review->patient_name }}
                                </small>
                            </div>
                        @endforeach
                        <div class="text-center">
                            <a href="{{ route('doctor.reviews.index') }}" class="btn btn-sm btn-primary-custom">
                                View all reviews →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Notes -->
                @if($recentNotes->count() > 0)
                    <div class="table-card mb-4">
                        <h6 class="mb-3"><i class="fas fa-sticky-note me-2"></i>Recent Notes</h6>
                        @foreach($recentNotes as $note)
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="badge {{ $note->getTypeBadgeClass() }} me-2">
                                            <i class="{{ $note->getTypeIcon() }} me-1"></i>
                                            {{ ucfirst($note->note_type) }}
                                        </span>
                                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                                    </div>
                                    <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <div class="mb-1">
                                    <strong>{{ $note->getDisplayTitle() }}</strong>
                                </div>
                                <p class="small mb-1 text-muted">{{ $note->getPreview(60) }}</p>
                                @if($note->patient)
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>{{ $note->patient->name }}
                                    </small>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-file me-1"></i>General Note
                                    </small>
                                @endif
                            </div>
                        @endforeach
                        <div class="text-center">
                            <a href="{{ route('doctor.notes.index') }}" class="btn btn-sm btn-primary-custom">
                                View all notes →
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
                                    <div class="fw-medium">{{ $appointment->patient_name }}</div>
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
<script>
const chartLabels = @json($chartLabels ?? []);
const chartData = @json($chartData ?? []);
const records = @json($records ?? []);
</script>
@vite(['resources/js/dashboard-enhancements.js'])
@endsection

@push('styles')
<style>
.clickable-card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clickable-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.badge {
    font-size: 0.7rem;
}

.bg-light-success {
    background-color: rgba(40, 167, 69, 0.1) !important;
    border-left: 4px solid #28a745 !important;
}
</style>
@endpush
