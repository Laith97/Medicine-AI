@extends('master')

@section('title', 'My Appointments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom">
            <h2 class="h1 mb-1" style="font-weight: 700;">Appointments</h2>
            <p>View all appointments</p>
        </div>

        <!-- Filters -->
        <div class="table-card mb-4">
            <form method="GET" action="{{ route('appointments.index') }}">
                <div class="row g-3">
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>No Show</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary">
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        @if($appointments->count() > 0)
            <div class="row">
                @foreach($appointments as $appointment)
                    <div class="col-12 mb-4">
                        <div class="table-card">
                            <div class="row align-items-center">
                                <!-- Doctor Info -->
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-3">
                                        <!-- Doctor Image -->
                                        <div class="me-3">
                                            @if($appointment->doctor->profile_image)
                                                <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                                     alt="{{ $appointment->doctor->user->name }}"
                                                     class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                            @else
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-user-md text-primary"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Doctor Details -->
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">{{ $appointment->doctor->user->name }}</h5>
                                            <p class="text-primary mb-0">{{ $appointment->doctor->specialty->name }}</p>
                                        </div>
                                    </div>

                                    <!-- Appointment Details -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="fas fa-calendar me-2"></i>
                                                {{ $appointment->appointment_date->format('M j, Y') }}
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="fas fa-clock me-2"></i>
                                                {{ $appointment->appointment_date->format('g:i A') }}
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} me-2"></i>
                                                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Reason -->
                                    <div class="mb-3">
                                        <small class="text-muted">Reason for visit:</small>
                                        <p class="mb-0">{{ $appointment->reason }}</p>
                                    </div>
                                </div>

                                <!-- Status & Actions -->
                                <div class="col-md-4 text-end">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-warning',
                                            'confirmed' => 'bg-success',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'no_show' => 'bg-secondary'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusColors[$appointment->status] ?? 'bg-secondary' }} mb-3">
                                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                    </span>

                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-primary-custom btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>

                                        @if($appointment->canBeCancelled())
                                            <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($appointments->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $appointments->links() }}
                </div>
            @endif
        @else
            <div class="table-card text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5>No appointments found</h5>
                <p class="text-muted">You haven't booked any appointments yet.</p>
                <a href="{{ route('doctors.index') }}" class="btn btn-primary-custom">
                    <i class="fas fa-plus me-2"></i>Book Your First Appointment
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Cancel Appointment Modal -->
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
