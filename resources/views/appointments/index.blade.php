@extends('master')

@section('title', 'My Appointments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
/* Professional Dashboard Header */
.dashboard-header {
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.15);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #E8A0A0 0%, #DE6262 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 600;
    font-size: 2.2rem;
    margin-bottom: 0.5rem;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    font-weight: 400;
    margin-bottom: 0;
}

/* Professional Cards */
.professional-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.professional-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* Enhanced Filter Section */
.filter-section {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.filter-section .form-select,
.filter-section .form-control {
    border-radius: 10px;
    border: 1px solid #e0e6ed;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-section .form-select:focus,
.filter-section .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Professional Buttons */
.btn-primary-professional {
    background-color: #DE6262;
    border-color: #DE6262;
    color: white;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary-professional:hover {
    background-color: #D64A4A;
    border-color: #D64A4A;
    color: white;
    transform: translateY(-1px);
}

.btn-outline-secondary {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
    background-color: #e9ecef;
    color: #495057;
}

.btn-danger-professional {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-danger-professional:hover {
    background-color: #bb2d3b;
    border-color: #b02a37;
    color: white;
}

/* Appointment Card */
.appointment-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.appointment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* Doctor Avatar */
.doctor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.25rem;
}

/* Status Badges */
.badge-professional {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-pending {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.badge-confirmed {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.badge-completed {
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.badge-cancelled {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Info Items */
.info-item {
    display: flex;
    align-items: center;
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.info-item i {
    width: 20px;
    margin-right: 0.5rem;
    color: #94a3b8;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-state h5 {
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #adb5bd;
    margin-bottom: 2rem;
}

/* Pagination */
.pagination-wrapper .pagination {
    border-radius: 8px;
    overflow: hidden;
}

.pagination-wrapper .page-link {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    color: #495057;
    background: #ffffff;
}

.pagination-wrapper .page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #495057;
}

.pagination-wrapper .page-item.active .page-link {
    background-color: #DE6262;
    border-color: #DE6262;
    color: white;
}

/* Modal Styles */
.modal-header {
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    color: white;
    border-radius: 16px 16px 0 0;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

.modal-content {
    border-radius: 16px;
    border: none;
}

/* Background */
.page-background {
    background-color: #f8f9fa;
    min-height: 100vh;
}
</style>
@endpush

@section('content')
<div class="page-background">
    <div class="container py-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-alt me-2"></i>My Appointments</h2>
                    <p class="text-muted mb-0">View and manage your appointments</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="{{ route('appointments.index') }}">
                <div class="row g-3">
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium text-dark">Status</label>
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
                        <label class="form-label fw-medium text-dark">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-medium text-dark">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary-professional">
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
                        <div class="appointment-card">
                            <div class="row p-4">
                                <!-- Doctor Info -->
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-3">
                                        <!-- Doctor Image -->
                                        <div class="doctor-avatar me-3">
                                            @if($appointment->doctor->profile_image)
                                                <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                                     alt="{{ $appointment->doctor->user->name }}"
                                                     class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                            @else
                                                <i class="fas fa-user-md"></i>
                                            @endif
                                        </div>

                                        <!-- Doctor Details -->
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 text-dark fw-semibold">{{ $appointment->doctor->user->name }}</h5>
                                            <p class="text-primary mb-0">{{ $appointment->doctor->specialty->name }}</p>
                                        </div>
                                    </div>

                                    <!-- Appointment Details -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="info-item">
                                                <i class="fas fa-calendar"></i>
                                                <span>{{ $appointment->appointment_date->format('M j, Y') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-item">
                                                <i class="fas fa-clock"></i>
                                                <span>{{ $appointment->appointment_date->format('g:i A') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-item">
                                                <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }}"></i>
                                                <span>{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reason -->
                                    <div class="info-item">
                                        <i class="fas fa-stethoscope"></i>
                                        <span class="text-dark">Reason: {{ $appointment->reason }}</span>
                                    </div>
                                </div>

                                <!-- Status & Actions -->
                                <div class="col-md-4 text-end d-flex flex-column justify-content-between">
                                    <div>
                                        @php
                                            $statusClasses = [
                                                'pending' => 'badge-pending',
                                                'confirmed' => 'badge-confirmed',
                                                'completed' => 'badge-completed',
                                                'cancelled' => 'badge-cancelled',
                                                'no_show' => 'badge-cancelled'
                                            ];
                                        @endphp
                                        <span class="badge badge-professional {{ $statusClasses[$appointment->status] ?? 'badge-pending' }}">
                                            {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                        </span>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                                        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-primary-professional btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>

                                        @if($appointment->canBeCancelled())
                                            <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-danger-professional btn-sm">
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
                    <div class="pagination-wrapper">
                        {{ $appointments->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h5>No Appointments Found</h5>
                <p class="text-muted">You haven't booked any appointments yet.</p>
                <a href="{{ route('doctors.index') }}" class="btn btn-primary-professional">
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
                <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to cancel this appointment?</p>
                <form id="cancelForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark">Reason for cancellation (optional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Please provide a reason..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                <button type="button" class="btn btn-danger-professional" onclick="submitCancellation()">
                    <i class="fas fa-times me-1"></i>Cancel Appointment
                </button>
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
