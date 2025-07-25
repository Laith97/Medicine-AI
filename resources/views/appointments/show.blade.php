@extends('master')

@section('title', 'Appointment Details')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">

        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                    <!-- Back Button & Title -->
                    <div class="d-flex align-items-center mb-3 mb-md-0">
                        <a href="{{ route('appointments.index') }}" class="btn btn-secondary-custom me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                        </a>
                        <div>
                            <h1 class="h2 mb-1 fw-bold">Appointment Details</h1>
                            <small class="text-muted">ID: #{{ $appointment->id }}</small>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    @php
                        $statusClasses = [
                            'pending' => 'bg-warning text-dark',
                            'confirmed' => 'bg-success text-white',
                            'completed' => 'bg-primary text-white',
                            'cancelled' => 'bg-danger text-white',
                            'no_show' => 'bg-secondary text-white'
                        ];
                        $statusClass = $statusClasses[$appointment->status] ?? 'bg-secondary text-white';
                    @endphp
                    <span class="badge {{ $statusClass }} px-3 py-2 rounded-pill fs-6">
                        <i class="fas fa-circle me-2" style="font-size: 0.5rem;"></i>
                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Left Column - Main Content -->
            <div class="col-lg-8">

                <!-- Appointment Overview Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-1 fw-bold">{{ $appointment->appointment_date->format('l, F j, Y') }}</h3>
                                <p class="mb-0 opacity-75">{{ $appointment->appointment_date->format('g:i A') }} - {{ $appointment->appointment_end->format('g:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <div class="h2 mb-0 fw-bold">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}</div>
                                <small class="opacity-75">minutes</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                        <i class="fas fa-calendar-alt text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-semibold mb-1">Appointment Type</h6>
                                        <p class="text-muted mb-0">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                                        <i class="fas fa-dollar-sign text-success fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-semibold mb-1">Consultation Fee</h6>
                                        <p class="text-muted mb-0">${{ number_format($appointment->consultation_fee / 100, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Information Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4 fw-bold">Your Doctor</h4>

                        <div class="d-flex align-items-start">
                            <!-- Doctor Avatar -->
                            <div class="me-4 flex-shrink-0">
                                @if($appointment->doctor->profile_image)
                                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                         alt="{{ $appointment->doctor->user->name }}"
                                         class="rounded-3 border" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <i class="fas fa-user-md text-white fs-2"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Doctor Details -->
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1">{{ $appointment->doctor->user->name }}</h5>
                                <p class="text-primary fw-semibold mb-2">{{ $appointment->doctor->specialty->name }}</p>

                                <!-- Rating -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="text-warning me-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($appointment->doctor->average_rating))
                                                <i class="fas fa-star"></i>
                                            @elseif($i - 0.5 <= $appointment->doctor->average_rating)
                                                <i class="fas fa-star-half-alt"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-muted">
                                        {{ number_format($appointment->doctor->average_rating, 1) }} ({{ $appointment->doctor->total_reviews }} reviews)
                                    </span>
                                </div>

                                <!-- Contact Actions -->
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-user me-1"></i>View Profile
                                    </a>
                                    @if($appointment->doctor->phone)
                                        <a href="tel:{{ $appointment->doctor->phone }}" class="btn btn-success btn-sm">
                                            <i class="fas fa-phone me-1"></i>Call Doctor
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointment Information Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4 fw-bold">Appointment Information</h4>

                        <!-- Reason for Visit -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-clipboard-list text-info"></i>
                                </div>
                                <h6 class="fw-semibold mb-0">Reason for Visit</h6>
                            </div>
                            <div class="bg-light rounded-3 p-3 ms-5">
                                <p class="mb-0">{{ $appointment->reason }}</p>
                            </div>
                        </div>

                        @if($appointment->symptoms)
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    </div>
                                    <h6 class="fw-semibold mb-0">Symptoms</h6>
                                </div>
                                <div class="bg-light rounded-3 p-3 ms-5">
                                    <p class="mb-0">{{ $appointment->symptoms }}</p>
                                </div>
                            </div>
                        @endif

                        @if($appointment->patient_notes)
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-secondary bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-sticky-note text-secondary"></i>
                                    </div>
                                    <h6 class="fw-semibold mb-0">Additional Notes</h6>
                                </div>
                                <div class="bg-light rounded-3 p-3 ms-5">
                                    <p class="mb-0">{{ $appointment->patient_notes }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Doctor's Assessment (if completed) -->
                @if($appointment->status == 'completed' && $appointment->doctor_notes)
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="fas fa-user-md text-primary fs-4"></i>
                                </div>
                                <h4 class="card-title mb-0 fw-bold">Doctor's Assessment</h4>
                            </div>

                            <div class="bg-primary bg-opacity-5 rounded-3 p-4">
                                <p class="mb-0 fs-5">{{ $appointment->doctor_notes }}</p>
                            </div>

                            @if($appointment->follow_up_required)
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Follow-up appointment recommended</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Review Section -->
                @if($appointment->status == 'completed')
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-warning bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="fas fa-star text-warning fs-4"></i>
                                </div>
                                <h4 class="card-title mb-0 fw-bold">Your Review</h4>
                            </div>

                            @if($appointment->review)
                                <div class="bg-success bg-opacity-5 rounded-3 p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="text-warning me-3">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $appointment->review->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <small class="text-muted">
                                            Reviewed on {{ $appointment->review->created_at->format('M j, Y') }}
                                        </small>
                                    </div>
                                    @if($appointment->review->comment)
                                        <p class="mb-3">{{ $appointment->review->comment }}</p>
                                    @endif
                                    <a href="{{ route('reviews.show', $appointment->review) }}" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-eye me-1"></i>View full review
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="fas fa-star text-warning fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">How was your appointment?</h5>
                                    <p class="text-muted mb-4">Share your experience to help other patients</p>
                                    <a href="{{ route('appointments.review', $appointment) }}" class="btn btn-warning">
                                        <i class="fas fa-star me-2"></i>Leave a Review
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Column - Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions Card -->
                <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bolt text-primary me-2"></i>
                            <h5 class="card-title mb-0 fw-bold">Quick Actions</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            @if(in_array($appointment->status, ['pending', 'confirmed']) && $appointment->appointment_type == 'video_call')
                                <button onclick="joinVideoCall()" class="btn btn-primary">
                                    <i class="fas fa-video me-2"></i>Join Video Call
                                </button>
                            @endif

                            @if($appointment->canBeRescheduled())
                                <button onclick="rescheduleAppointment()" class="btn btn-warning">
                                    <i class="fas fa-calendar-alt me-2"></i>Reschedule
                                </button>
                            @endif

                            @if($appointment->canBeCancelled())
                                <button onclick="showCancelModal()" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Cancel Appointment
                                </button>
                            @endif

                            <hr>

                            <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-user-md me-2"></i>View Doctor Profile
                            </a>

                            @if($appointment->doctor->phone)
                                <a href="tel:{{ $appointment->doctor->phone }}" class="btn btn-success">
                                    <i class="fas fa-phone me-2"></i>Call Doctor
                                </a>
                            @endif
                        </div>

                        <!-- Appointment Summary -->
                        <hr class="my-4">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-info-circle text-muted me-2"></i>
                                <h6 class="fw-semibold mb-0">Summary</h6>
                            </div>
                            <div class="small">
                                <div class="d-flex justify-content-between py-2 px-3 bg-light rounded mb-2">
                                    <span class="text-muted">Consultation Fee</span>
                                    <span class="fw-semibold">${{ number_format($appointment->consultation_fee / 100, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 px-3 bg-light rounded mb-2">
                                    <span class="text-muted">Booked on</span>
                                    <span class="fw-medium">{{ $appointment->created_at->format('M j, Y') }}</span>
                                </div>
                                @if($appointment->cancelled_at)
                                    <div class="d-flex justify-content-between py-2 px-3 bg-danger bg-opacity-10 rounded">
                                        <span class="text-danger">Cancelled on</span>
                                        <span class="fw-medium text-danger">{{ $appointment->cancelled_at->format('M j, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preparation Tips Card -->
                @if(in_array($appointment->status, ['pending', 'confirmed']))
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lightbulb me-2"></i>
                                <h6 class="card-title mb-0 fw-bold">Preparation Tips</h6>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @if($appointment->appointment_type == 'in_person')
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Arrive 15 minutes early</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Bring valid ID and insurance card</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Wear a mask if required</span>
                                    </li>
                                @elseif($appointment->appointment_type == 'video_call')
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Test your camera and microphone</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Ensure stable internet connection</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Join the call 5 minutes early</span>
                                    </li>
                                @else
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Ensure your phone is charged</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Be in a quiet location</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Have your medical history ready</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 rounded-3 p-2 me-3">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="cancelModalLabel">Cancel Appointment</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Warning Message -->
                <div class="alert alert-danger">
                    <strong>Are you sure you want to cancel this appointment?</strong><br>
                    This action cannot be undone and you may need to reschedule for a later date.
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" id="cancelForm">
                    @csrf
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label fw-semibold">
                            Reason for cancellation (optional)
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason" rows="4"
                                  class="form-control"
                                  placeholder="Please let us know why you're cancelling this appointment..."></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Keep Appointment
                </button>
                <button type="submit" form="cancelForm" class="btn btn-danger">
                    Cancel Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Functions
function showCancelModal() {
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function rescheduleAppointment() {
    showNotification('Reschedule feature coming soon!', 'info');
}

function joinVideoCall() {
    showNotification('Launching video call...', 'success');
}

// Notification System
function showNotification(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        info: 'alert-info',
        warning: 'alert-warning',
        error: 'alert-danger'
    };

    const icons = {
        success: 'fas fa-check-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
    };

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${icons[type]} me-2"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips if any
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
