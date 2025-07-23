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
        <div class="dashboard-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h1 mb-1">My Appointments</h2>
                <p>Manage your upcoming and past appointments</p>
            </div>
            <a href="{{ route('doctors.index') }}" class="btn btn-primary-custom">
                <i class="fas fa-plus me-2"></i>Book New Appointment
            </a>
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
                                                'completed' => 'bg-primary',
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

                                            @if($appointment->canBeRescheduled())
                                                <button onclick="rescheduleAppointment({{ $appointment->id }})" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-calendar-alt me-1"></i>Reschedule
                                                </button>
                                            @endif

                                            @if($appointment->status == 'completed' && !$appointment->review)
                                                <a href="{{ route('appointments.review', $appointment) }}" class="btn btn-success btn-sm">
                                                    <i class="fas fa-star me-1"></i>Leave Review
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Additional Info for Upcoming Appointments -->
                        @if(in_array($appointment->status, ['pending', 'confirmed']) && $appointment->appointment_date->isFuture())
                            <div class="bg-primary-50 px-6 py-3 border-t">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-primary-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <span class="text-sm">
                                            Appointment in {{ $appointment->appointment_date->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if($appointment->appointment_type == 'video_call')
                                        <button class="bg-primary-600 text-white px-3 py-1 rounded text-sm hover:bg-primary-700 transition-colors">
                                            <i class="fas fa-video mr-1"></i>Join Call
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($appointments->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $appointments->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="table-card text-center">
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No appointments found</h3>
                    <p class="text-gray-600 mb-6">You haven't booked any appointments yet.</p>
                    <a href="{{ route('doctors.index') }}" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-2"></i>
                        Book Your First Appointment
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden" style="backdrop-filter: blur(4px); display: none;">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4 shadow-lg">
        <h3 class="text-xl font-semibold text-gray-900 mb-4" style="font-weight: 700;">Cancel Appointment</h3>
        <form id="cancelForm" method="POST">
            @csrf
            <div class="mb-4">
                <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for cancellation (optional)
                </label>
                <textarea name="cancellation_reason" id="cancellation_reason" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition-colors"
                          placeholder="Please let us know why you're cancelling..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCancelModal()"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        style="border-color: #0088cc; color: #0088cc;">
                    Keep Appointment
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        style="background-color: #e74c3c; border-color: #e74c3c;">
                    Cancel Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function cancelAppointment(appointmentId) {
    const modal = document.getElementById('cancelModal');
    const form = document.getElementById('cancelForm');
    form.action = `/appointments/${appointmentId}/cancel`;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    const modal = document.getElementById('cancelModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function rescheduleAppointment(appointmentId) {
    // For now, redirect to the booking page
    // In a full implementation, you'd show a reschedule modal
    window.location.href = `/appointments/${appointmentId}/reschedule`;
}

// Close modal when clicking outside
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});
</script>
@endsection
