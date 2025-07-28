@extends('master')

@section('title', 'Appointment Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('doctor.appointments.index') }}" class="btn btn-secondary-custom me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                </a>
                <div>
                    <h2 class="h1 mb-1">Appointment Details</h2>
                    <p>ID: #{{ $appointment->id }}</p>
                </div>
            </div>

            @php
                $statusColors = [
                    'pending' => 'bg-warning',
                    'confirmed' => 'bg-success',
                    'completed' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    'no_show' => 'bg-secondary'
                ];
            @endphp
            <span class="badge {{ $statusColors[$appointment->status] ?? 'bg-secondary' }} fs-6">
                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
            </span>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Appointment Overview -->
                <div class="table-card mb-4">
                    <div class="bg-primary text-white p-4 rounded-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">{{ $appointment->appointment_date->format('l, F j, Y') }}</h3>
                                <p class="mb-0 opacity-75">{{ $appointment->appointment_date->format('g:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <div class="h2 mb-0">30</div>
                                <small class="opacity-75">minutes</small>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-calendar text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Appointment Type</h6>
                                        <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-dollar-sign text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Consultation Fee</h6>
                                        <small class="text-muted">${{ number_format($appointment->consultation_fee / 100, 2) }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Information -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-4">Your Doctor</h5>
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                @if($appointment->doctor->profile_image)
                                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                         alt="{{ $appointment->doctor->user->name }}"
                                         class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                         style="width: 64px; height: 64px;">
                                        <i class="fas fa-user-md text-primary"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $appointment->doctor->user->name }}</h5>
                                <p class="text-primary mb-2">{{ $appointment->doctor->specialty->name }}</p>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="text-warning me-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($appointment->doctor->average_rating ?? 0))
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <small class="text-muted">{{ number_format($appointment->doctor->average_rating ?? 0, 1) }} ({{ $appointment->doctor->reviews_count ?? 0 }} reviews)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Patient Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Name</p>
                                <p class="mb-3">{{ $appointment->patient_name }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Email</p>
                                <p class="mb-3">{{ $appointment->patient_email }}</p>
                            </div>
                            @if($appointment->patient_phone)
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Phone</p>
                                    <p class="mb-3">{{ $appointment->patient_phone }}</p>
                                </div>
                            @endif
                            @if($appointment->isGuestAppointment() && $appointment->guest_date_of_birth)
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Date of Birth</p>
                                    <p class="mb-3">{{ \Carbon\Carbon::parse($appointment->guest_date_of_birth)->format('F j, Y') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Reason for Visit -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Reason for Visit</h5>
                        <p class="mb-0">{{ $appointment->reason }}</p>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Quick Actions</h5>
                        <div class="d-grid gap-2">
                            @if($appointment->canBeCancelled())
                                <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Cancel Appointment
                                </button>
                            @endif

                            @if($appointment->status == 'confirmed' && $appointment->appointment_type == 'video_call')
                                <button class="btn btn-success">
                                    <i class="fas fa-video me-2"></i>Join Video Call
                                </button>
                            @endif

                            @if($appointment->status == 'completed' && !Auth::user()->isDoctor())
                                <a href="{{ route('appointments.review', $appointment) }}" class="btn btn-warning">
                                    <i class="fas fa-star me-2"></i>Leave Review
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Appointment Timeline -->
                <div class="table-card">
                    <div class="p-4">
                        <h5 class="mb-3">Timeline</h5>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Appointment Booked</h6>
                                    <small class="text-muted">{{ $appointment->created_at->format('M j, Y g:i A') }}</small>
                                </div>
                            </div>

                            @if($appointment->status != 'pending')
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Status Updated</h6>
                                        <small class="text-muted">{{ ucfirst($appointment->status) }}</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <form id="cancelForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Reason for cancellation (optional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                <button type="button" class="btn btn-danger" onclick="submitCancellation()">Cancel Appointment</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cancelAppointment(appointmentId) {
    const form = document.getElementById('cancelForm');
    form.action = `/appointments/${appointmentId}/cancel`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function submitCancellation() {
    document.getElementById('cancelForm').submit();
}
</script>
@endpush
