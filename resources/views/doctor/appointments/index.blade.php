@extends('master')

@section('title', 'Manage Appointments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">

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
    content: '📅';
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
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h2>Appointments</h2>
            <p>Manage your appointments</p>
        </div>

        <!-- Auto-Approve Settings -->
        <div class="table-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1"><i class="fas fa-cog me-2"></i>Appointment Approval Settings</h6>
                    <small class="text-muted">Control how new appointment requests are handled</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           id="auto_approve_toggle"
                           {{ Auth::user()->doctor->auto_approve_appointments ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_approve_toggle">
                        <span class="fw-medium">Auto-approve appointments</span>
                    </label>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    When enabled, new appointment requests are automatically confirmed.
                    When disabled, you'll need to manually approve each appointment.
                </small>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-card mb-4">
            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Appointments</h6>
            <form method="GET" action="{{ route('doctor.appointments.index') }}" class="row g-3">
                <!-- Status Filter -->
                <div class="col-md-2">
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

                <!-- Risk Category Filter -->
                <div class="col-md-2">
                    <label class="form-label">Risk Category</label>
                    <select name="risk_category" class="form-select">
                        <option value="">All</option>
                        <option value="low" {{ request('risk_category') == 'low' ? 'selected' : '' }}>Low Risk</option>
                        <option value="medium" {{ request('risk_category') == 'medium' ? 'selected' : '' }}>Medium Risk</option>
                        <option value="high" {{ request('risk_category') == 'high' ? 'selected' : '' }}>High Risk</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                <!-- Buttons -->
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary-custom btn-sm">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('doctor.appointments.index') }}" class="btn btn-secondary btn-sm">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        @if($appointments->count() > 0)
            <div class="table-card">
                <h6 class="mb-3"><i class="fas fa-calendar me-2"></i>Appointments</h6>
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Risk</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($appointments as $appointment)
                                <tr>
                                    <!-- Patient -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <span class="fw-medium text-primary">
                                                        {{ substr($appointment->patient_name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-medium">
                                                    {{ $appointment->patient_name }}
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $appointment->patient_email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Date & Time -->
                                    <td>
                                        <div class="fw-medium">
                                            {{ $appointment->appointment_date->format('M j, Y') }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $appointment->appointment_date->format('g:i A') }}
                                        </div>
                                    </td>

                                    <!-- Type -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} me-2 text-muted"></i>
                                            <span>
                                                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-warning',
                                                'confirmed' => 'bg-success',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'no_show' => 'bg-secondary'
                                            ];
                                        @endphp
                                        <span class="badge {{ $statusColors[$appointment->status] ?? 'bg-secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                        </span>
                                    </td>

                                    <!-- Risk -->
                                    <td>
                                        @php
                                            $riskScore = $appointment->patient->patientRiskScores->where('appointment_id', $appointment->id)->first();
                                        @endphp
                                        @if($riskScore)
                                            @php
                                                $noShowRisk = $riskScore->no_show_risk;
                                                $hospitalizationRisk = $riskScore->hospitalization_risk;
                                                $maxRisk = max($noShowRisk, $hospitalizationRisk);
                                            @endphp
                                            @if($maxRisk < 0.3)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Low
                                                </span>
                                            @elseif($maxRisk < 0.7)
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Medium
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>High
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <!-- Reason -->
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            {{ $appointment->reason }}
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="gap-1">
                                            <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if($appointment->status == 'pending')
                                                <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($appointment->status == 'confirmed')
                                                <button onclick="completeAppointment({{ $appointment->id }})" class="btn btn-sm btn-outline-primary" title="Complete">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <button onclick="markNoShow({{ $appointment->id }})" class="btn btn-sm btn-outline-secondary" title="No Show">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                            @endif

                                            @if(in_array($appointment->status, ['pending', 'confirmed']))
                                                <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-sm btn-outline-danger" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                <p class="text-muted">No appointments match your current filters.</p>
                <a href="{{ route('doctor.appointments.index') }}" class="btn btn-primary-custom">
                    Clear Filters
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Complete Appointment Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeModalLabel">Complete Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="doctor_notes" class="form-label">Doctor's Notes (optional)</label>
                        <textarea name="doctor_notes" id="doctor_notes" rows="4" class="form-control"
                                  placeholder="Add any notes about the appointment..."></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="follow_up_required" class="form-check-input" id="follow_up_required">
                        <label class="form-check-label" for="follow_up_required">
                            Follow-up appointment recommended
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Complete Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">
                            Reason for cancellation <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason" rows="3" required class="form-control"
                                  placeholder="Please provide a reason for cancelling..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                    <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-approve toggle functionality
document.getElementById('auto_approve_toggle').addEventListener('change', function() {
    const isEnabled = this.checked;
    const toggleLabel = this.nextElementSibling.querySelector('.fw-medium');

    // Show loading state
    const originalText = toggleLabel.textContent;
    toggleLabel.textContent = 'Updating...';
    this.disabled = true;

    // Make AJAX request
    fetch('{{ route("doctor.appointments.toggle-auto-approve") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            auto_approve: isEnabled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toggleLabel.textContent = isEnabled ? 'Auto-approve appointments' : 'Manual approval required';
            showNotification(data.message || 'Setting updated successfully!', 'success');
        } else {
            // Revert toggle on error
            this.checked = !isEnabled;
            throw new Error(data.message || 'Failed to update setting');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert toggle on error
        this.checked = !isEnabled;
        showNotification('Failed to update auto-approve setting. Please try again.', 'error');
    })
    .finally(() => {
        toggleLabel.textContent = originalText;
        this.disabled = false;
    });
});

function completeAppointment(appointmentId) {
    const form = document.getElementById('completeForm');
    form.action = `/doctor/appointments/${appointmentId}/complete`;
    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();
}

function cancelAppointment(appointmentId) {
    const form = document.getElementById('cancelForm');
    form.action = `/doctor/appointments/${appointmentId}/cancel`;
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function markNoShow(appointmentId) {
    if (confirm('Are you sure you want to mark this appointment as no show?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/doctor/appointments/${appointmentId}/no-show`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Notification helper function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.auto-approve-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} auto-approve-notification`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;

    const icon = type === 'success' ? 'check-circle' :
                 type === 'warning' ? 'exclamation-triangle' :
                 type === 'error' ? 'exclamation-circle' : 'info-circle';

    notification.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>${message}
        <button type="button" class="btn-close" aria-label="Close"></button>
    `;

    // Add close functionality
    notification.querySelector('.btn-close').addEventListener('click', function() {
        notification.remove();
    });

    // Add to page
    document.body.appendChild(notification);

    // Auto-hide after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

// Close modals when clicking outside
document.getElementById('completeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCompleteModal();
    }
});

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});
</script>
@endsection
