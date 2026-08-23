@extends('master')

@section('title', 'Manage Appointments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
    .auto-approve-compact {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 0.35rem 0.75rem 0.35rem 0.9rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
    }
    .auto-approve-compact .form-switch { margin: 0; display: flex; align-items: center; }
    .auto-approve-compact .form-check-input {
        width: 2.2rem; height: 1.15rem; margin: 0; cursor: pointer;
    }
    .auto-approve-compact .form-check-input:checked { background-color: #10b981; border-color: #10b981; }
    .cases-panel .doctor-table thead th { font-size: 0.76rem; letter-spacing: 0.03em; white-space: normal; }
    .cases-panel .doctor-table tbody td { white-space: normal; word-break: break-word; }
    .doctor-table { table-layout: auto; }
    .patient-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; }
    .patient-avatar-male { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
    .patient-avatar-female { background: linear-gradient(135deg, #e83e8c 0%, #c21e56 100%); }
    .patient-avatar-default { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
    .badge-professional { font-size: 0.72rem; padding: 0.3rem 0.6rem; border-radius: 999px; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-check me-2"></i>Appointments</h2>
                    <p>Manage and track your patient appointments</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('ai.ambient-listening.index') }}" class="doctor-btn doctor-btn-success doctor-btn-sm">
                        <i class="fas fa-microphone me-1"></i>Start Consultation
                    </a>
                    <a href="{{ route('doctor.appointments.create') }}" class="doctor-btn doctor-btn-primary doctor-btn-sm">
                        <i class="fas fa-plus me-1"></i>New Appointment
                    </a>
                </div>
            </div>
        </div>

        <!-- Compact stats like cases-overview -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);"><i class="fas fa-calendar"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $appointments->total() }}</p><p class="stats-label">Total</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-clock"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ collect($appointments->items())->where('status','pending')->count() }}</p><p class="stats-label">Pending</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-check-circle"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ collect($appointments->items())->where('status','confirmed')->count() }}</p><p class="stats-label">Confirmed</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);"><i class="fas fa-check-double"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ collect($appointments->items())->where('status','completed')->count() }}</p><p class="stats-label">Completed</p></div>
                </div>
            </div>
        </div>

        <!-- Filters + Auto-approve compact (not full-width) -->
        <div class="card border-0 shadow-sm cases-panel mb-3">
            <div class="cases-toolbar">
                <div class="cases-toolbar__title">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-filter me-2 text-primary"></i>Filter Appointments</h6>
                    @if(request()->hasAny(['status','risk_category','date_from','date_to']))
                        <a href="{{ route('doctor.appointments.index') }}" class="btn btn-outline-secondary btn-sm ms-2">Clear</a>
                    @endif
                </div>
                <div class="cases-toolbar__controls">
                    <!-- Compact auto-approve (not full-width toggle-section) -->
                    <div class="auto-approve-compact" title="When enabled, new requests are auto-confirmed">
                        <i class="fas fa-bolt text-warning"></i> Auto-approve
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto_approve_toggle" {{ Auth::user()->doctor->auto_approve_appointments ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                <form method="GET" action="{{ route('doctor.appointments.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-2 col-6">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;">Status</label>
                        <select name="status" class="form-select form-select-sm cases-sort">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>No Show</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;">Risk</label>
                        <select name="risk_category" class="form-select form-select-sm cases-sort">
                            <option value="">All Risk</option>
                            <option value="low" {{ request('risk_category') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('risk_category') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('risk_category') == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="doctor-btn doctor-btn-primary doctor-btn-sm flex-grow-1"><i class="fas fa-search me-1"></i>Apply</button>
                        <a href="{{ route('doctor.appointments.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm"><i class="fas fa-rotate-left"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Appointments List - same as patients -->
        @if($appointments->count() > 0)
            <div class="card border-0 shadow-sm cases-panel" style="overflow: hidden;">
                <div class="doctor-table-container" style="overflow: hidden;">
                    <div style="overflow: hidden;">
                        <table class="doctor-table mb-0 w-100" style="width:100%; table-layout:auto;">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th class="d-none d-md-table-cell">Type</th>
                                    <th>Status</th>
                                    <th class="d-none d-lg-table-cell">Risk</th>
                                    <th class="d-none d-xl-table-cell" style="max-width:220px;">Complaint</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($appointments as $appointment)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $avatarClass = 'patient-avatar-default';
                                                    $initials = '??';
                                                    $gender = strtolower($appointment->patient->gender ?? '');
                                                    if ($gender === 'male') $avatarClass = 'patient-avatar-male';
                                                    elseif ($gender === 'female') $avatarClass = 'patient-avatar-female';
                                                    $initials = collect(explode(' ', $appointment->patient_name))->map(fn($w) => substr($w, 0, 1))->take(2)->join('');
                                                    if (strlen($initials) < 2) $initials = substr($appointment->patient_name, 0, 2);
                                                    $initials = strtoupper($initials);
                                                @endphp
                                                <div class="patient-avatar {{ $avatarClass }} me-3">{{ $initials }}</div>
                                                <div class="min-w-0">
                                                    <div class="fw-medium text-dark text-truncate" style="max-width:160px;">{{ $appointment->patient_name }}</div>
                                                    <small class="text-muted text-truncate d-block" style="max-width:160px;">{{ $appointment->patient_email }}</small>
                                                    <small class="text-muted d-xl-none text-truncate d-block" style="max-width:160px; font-size:0.72rem;" title="{{ $appointment->reason }}">{{ Str::limit($appointment->reason, 30) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $appointment->appointment_date->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ $appointment->appointment_date->format('g:i A') }}</small>
                                        </td>
                                        <td class="d-none d-md-table-cell"><span class="doctor-badge doctor-badge-secondary text-capitalize" style="font-size:0.72rem;">{{ str_replace('_', ' ', $appointment->appointment_type) }}</span></td>
                                        <td>
                                            @php $statusClasses = ['pending'=>'warning','confirmed'=>'success','completed'=>'info','cancelled'=>'danger','no_show'=>'secondary']; @endphp
                                            <span class="doctor-badge doctor-badge-{{ $statusClasses[$appointment->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$appointment->status)) }}</span>
                                            <!-- show risk inline on small where Risk col hidden -->
                                            <span class="d-lg-none ms-1">
                                                @php $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id', $appointment->id)?->first(); @endphp
                                                @if($riskScore)
                                                    @php $maxRisk = max($riskScore->no_show_risk, $riskScore->hospitalization_risk); @endphp
                                                    @if($maxRisk < 0.3)<span class="doctor-badge doctor-badge-success" style="font-size:0.65rem;">Low</span>
                                                    @elseif($maxRisk < 0.7)<span class="doctor-badge doctor-badge-warning" style="font-size:0.65rem;">Med</span>
                                                    @else<span class="doctor-badge doctor-badge-danger" style="font-size:0.65rem;">High</span>
                                                    @endif
                                                @endif
                                            </span>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            @php $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id', $appointment->id)?->first(); @endphp
                                            @if($riskScore)
                                                @php $maxRisk = max($riskScore->no_show_risk, $riskScore->hospitalization_risk); @endphp
                                                @if($maxRisk < 0.3)<span class="doctor-badge doctor-badge-success">Low</span>
                                                @elseif($maxRisk < 0.7)<span class="doctor-badge doctor-badge-warning">Medium</span>
                                                @else<span class="doctor-badge doctor-badge-danger">High</span>
                                                @endif
                                            @else<span class="text-muted small">—</span>@endif
                                        </td>
                                        <td class="d-none d-xl-table-cell"><div class="text-truncate" style="max-width:180px;" title="{{ $appointment->reason }}">{{ Str::limit($appointment->reason, 40) }}</div></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('doctor.appointments.show', $appointment) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                @if($appointment->status == 'pending')
                                                    <button onclick="confirmAppointment({{ $appointment->id }})" class="doctor-btn doctor-btn-success doctor-btn-sm" title="Confirm"><i class="fas fa-check"></i></button>
                                                @endif
                                                @if($appointment->status == 'confirmed')
                                                    @if($appointment->appointment_type == 'video_call')
                                                        <a href="{{ route('video.room', $appointment->id) }}" target="_blank" class="doctor-btn doctor-btn-primary doctor-btn-sm" title="Video"><i class="fas fa-video"></i></a>
                                                    @endif
                                                    @if(!$appointment->appointment_date || !$appointment->appointment_date->isFuture())
                                                        <button onclick="completeAppointment({{ $appointment->id }})" class="doctor-btn doctor-btn-primary doctor-btn-sm" title="Complete"><i class="fas fa-check-circle"></i></button>
                                                        <button onclick="markNoShow({{ $appointment->id }})" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="No Show"><i class="fas fa-user-times"></i></button>
                                                    @endif
                                                @endif
                                                @if(in_array($appointment->status, ['pending','confirmed']))
                                                    <button onclick="cancelAppointment({{ $appointment->id }})" class="doctor-btn doctor-btn-danger doctor-btn-sm" title="Cancel"><i class="fas fa-times"></i></button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($appointments->hasPages())
                        <div class="table-footer d-flex justify-content-center p-3">
                            {{ $appointments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm cases-panel">
                <div class="doctor-empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h5>No Appointments Found</h5>
                    <p>No appointments match your filters.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('doctor.appointments.create') }}" class="doctor-btn doctor-btn-primary"><i class="fas fa-plus me-1"></i>Schedule New</a>
                        <a href="{{ route('doctor.appointments.index') }}" class="doctor-btn doctor-btn-outline"><i class="fas fa-rotate-left me-1"></i>Clear Filters</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Professional Complete Appointment Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content professional-card">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title" id="completeModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Complete Appointment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="doctor_notes" class="form-label fw-bold text-dark">
                            <i class="fas fa-notes-medical me-2 text-primary"></i>Doctor's Notes
                        </label>
                        <textarea name="doctor_notes" id="doctor_notes" rows="4" class="form-control"
                                  style="border-radius: 10px; border: 1px solid #e0e6ed;"
                                  placeholder="Add any notes about the appointment, treatment provided, or follow-up recommendations..."></textarea>
                        <small class="text-muted mt-1">Optional: Document important details about this consultation</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="follow_up_required" class="form-check-input" id="follow_up_required" style="border-radius: 4px;">
                        <label class="form-check-label fw-medium text-dark" for="follow_up_required">
                            <i class="fas fa-calendar-plus me-2 text-success"></i>
                            Follow-up appointment recommended
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-secondary-professional" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success-professional">
                        <i class="fas fa-check-circle me-2"></i>Complete Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Professional Cancel Appointment Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content professional-card">
            <div class="modal-header" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title" id="cancelModalLabel">
                    <i class="fas fa-times-circle me-2"></i>Cancel Appointment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0" style="background: rgba(247, 151, 30, 0.1); border-radius: 10px;">
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                        <strong>Important:</strong> Cancelling this appointment will notify the patient immediately.
                    </div>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label fw-bold text-dark">
                            <i class="fas fa-comment me-2 text-primary"></i>Reason for cancellation <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason" rows="3" required class="form-control"
                                  style="border-radius: 10px; border: 1px solid #e0e6ed;"
                                  placeholder="Please provide a clear reason for cancelling this appointment..."></textarea>
                        <small class="text-muted mt-1">This reason will be shared with the patient</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-secondary-professional" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-2"></i>Keep Appointment
                    </button>
                    <button type="submit" class="btn btn-danger-professional">
                        <i class="fas fa-times-circle me-2"></i>Cancel Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Real-time broadcasting setup
let broadcastingChannel = null;
let broadcastingConnected = false;
let connectionAttempts = 0;
const maxConnectionAttempts = 5;

// Initialize real-time broadcasting
function initializeBroadcasting() {
    if (typeof Echo === 'undefined') {
        console.warn('Laravel Echo not available, real-time updates disabled');
        return;
    }

    try {
        // Connect to private user channel
        broadcastingChannel = Echo.private(`user.{{ Auth::id() }}`)
            .listen('.appointments.updated', handleAppointmentListUpdate)
            .listen('.appointment.created', handleAppointmentCreated)
            .listen('.appointment.updated', handleAppointmentUpdated)
            .listen('.appointment.deleted', handleAppointmentDeleted)
            .error(handleBroadcastingError);

        broadcastingConnected = true;
        connectionAttempts = 0;
        updateConnectionStatus('connected');

        console.log('Real-time broadcasting initialized for appointments');

    } catch (error) {
        console.error('Failed to initialize broadcasting:', error);
        handleBroadcastingError(error);
    }
}

// Handle broadcasting connection errors
function handleBroadcastingError(error) {
    broadcastingConnected = false;
    updateConnectionStatus('disconnected');

    if (connectionAttempts < maxConnectionAttempts) {
        connectionAttempts++;
        console.log(`Broadcasting connection attempt ${connectionAttempts}/${maxConnectionAttempts}`);

        setTimeout(() => {
            initializeBroadcasting();
        }, Math.min(1000 * Math.pow(2, connectionAttempts), 30000)); // Exponential backoff
    } else {
        showNotification('Real-time updates unavailable. Please refresh the page.', 'warning');
    }
}

// Update connection status indicator
function updateConnectionStatus(status) {
    // Create or update connection status indicator
    let statusIndicator = document.getElementById('broadcasting-status');
    if (!statusIndicator) {
        statusIndicator = document.createElement('div');
        statusIndicator.id = 'broadcasting-status';
        statusIndicator.className = 'position-fixed bottom-0 end-0 m-3';
        statusIndicator.style.zIndex = '1000';
        document.body.appendChild(statusIndicator);
    }

    const statusConfig = {
        connected: { icon: 'wifi', text: 'Live', class: 'badge bg-success' },
        connecting: { icon: 'spinner fa-spin', text: 'Connecting', class: 'badge bg-warning' },
        disconnected: { icon: 'wifi-slash', text: 'Offline', class: 'badge bg-danger' }
    };

    const config = statusConfig[status] || statusConfig.disconnected;
    statusIndicator.innerHTML = `
        <span class="badge ${config.class}" title="Real-time connection status">
            <i class="fas fa-${config.icon} me-1"></i>${config.text}
        </span>
    `;
}

// Handle appointment list updates
function handleAppointmentListUpdate(event) {
    console.log('Appointment list update received:', event);

    // Show update notification
    showNotification('Appointment list updated', 'info');

    // Optionally refresh the page or update specific elements
    if (event.refresh_required) {
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
}

// Handle new appointment creation
function handleAppointmentCreated(event) {
    console.log('New appointment created:', event);

    if (event.appointment) {
        showNotification(`New appointment scheduled for ${event.appointment.patient_name}`, 'success');

        // Add to table if it matches current filters
        if (matchesCurrentFilters(event.appointment)) {
            addAppointmentToTable(event.appointment);
        }
    }
}

// Handle appointment updates
function handleAppointmentUpdated(event) {
    console.log('Appointment updated:', event);

    if (event.appointment && event.changed_attributes) {
        const appointmentId = event.appointment.id;
        const changedAttributes = event.changed_attributes;

        // Update the appointment row in the table
        updateAppointmentInTable(appointmentId, event.appointment, changedAttributes);

        // Show appropriate notification
        if (changedAttributes.includes('status')) {
            const statusText = event.appointment.status.replace('_', ' ');
            showNotification(`Appointment status changed to ${statusText}`, 'info');
        } else {
            showNotification('Appointment details updated', 'info');
        }
    }
}

// Handle appointment deletion
function handleAppointmentDeleted(event) {
    console.log('Appointment deleted:', event);

    if (event.appointment_id) {
        removeAppointmentFromTable(event.appointment_id);
        showNotification('Appointment cancelled', 'warning');
    }
}

// Check if appointment matches current filters
function matchesCurrentFilters(appointment) {
    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter = urlParams.get('status');

    if (statusFilter && appointment.status !== statusFilter) {
        return false;
    }

    // Add more filter checks as needed
    return true;
}

// Add appointment to table
function addAppointmentToTable(appointment) {
    // Implementation would depend on the table structure
    // For now, just refresh the page
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Update appointment in table
function updateAppointmentInTable(appointmentId, appointmentData, changedAttributes) {
    const row = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
    if (!row) return;

    // Update status badge if status changed
    if (changedAttributes.includes('status')) {
        const statusCell = row.querySelector('.badge');
        if (statusCell) {
            const statusColors = {
                'pending': 'bg-warning',
                'confirmed': 'bg-success',
                'completed': 'bg-success',
                'cancelled': 'bg-danger',
                'no_show': 'bg-secondary'
            };

            statusCell.className = `badge ${statusColors[appointmentData.status] || 'bg-secondary'}`;
            statusCell.textContent = appointmentData.status.replace('_', ' ').toUpperCase();
        }
    }

    // Add visual highlight for updated row
    row.style.transition = 'background-color 0.3s';
    row.style.backgroundColor = '#fff3cd';
    setTimeout(() => {
        row.style.backgroundColor = '';
    }, 2000);
}

// Remove appointment from table
function removeAppointmentFromTable(appointmentId) {
    const row = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
    if (row) {
        row.style.transition = 'opacity 0.3s';
        row.style.opacity = '0';
        setTimeout(() => {
            row.remove();
        }, 300);
    }
}

// Auto-approve toggle functionality with error handling
document.getElementById('auto_approve_toggle').addEventListener('change', function() {
    const isEnabled = this.checked;
    const toggleLabel = this.nextElementSibling.querySelector('.fw-medium');

    // Show loading state
    const originalText = toggleLabel.textContent;
    toggleLabel.textContent = 'Updating...';
    this.disabled = true;

    // Check if broadcasting is connected
    if (!broadcastingConnected) {
        showNotification('Connection lost. Changes may not be reflected in real-time.', 'warning');
    }

    // Make AJAX request with timeout and retry logic
    const makeRequest = (retries = 3) => {
        fetch('{{ route("doctor.appointments.toggle-auto-approve") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                auto_approve: isEnabled
            }),
            signal: AbortSignal.timeout(10000) // 10 second timeout
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
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
            console.error('Error updating auto-approve setting:', error);

            if (error.name === 'TimeoutError') {
                showNotification('Request timed out. Please check your connection and try again.', 'warning');
            } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                showNotification('Network error. Please check your connection.', 'error');
            } else if (retries > 0) {
                console.log(`Retrying request (${retries} attempts left)...`);
                setTimeout(() => makeRequest(retries - 1), 1000);
                return;
            } else {
                // Revert toggle on error
                this.checked = !isEnabled;
                showNotification('Failed to update auto-approve setting. Please try again.', 'error');
            }
        })
        .finally(() => {
            toggleLabel.textContent = originalText;
            this.disabled = false;
        });
    };

    makeRequest();
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

// Initialize broadcasting when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeBroadcasting();

    // Set up periodic connection health checks
    setInterval(() => {
        if (broadcastingConnected && broadcastingChannel) {
            // Ping the connection (Echo handles this automatically)
            updateConnectionStatus('connected');
        } else if (!broadcastingConnected) {
            updateConnectionStatus('connecting');
        }
    }, 30000); // Check every 30 seconds
});

// Handle page visibility changes for connection management
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, reduce connection activity
        updateConnectionStatus('disconnected');
    } else {
        // Page is visible again, reconnect if needed
        if (!broadcastingConnected) {
            initializeBroadcasting();
        }
        updateConnectionStatus(broadcastingConnected ? 'connected' : 'connecting');
    }
});

// Enhanced error handling for appointment actions
function completeAppointment(appointmentId) {
    const form = document.getElementById('completeForm');
    form.action = `/doctor/appointments/${appointmentId}/complete`;

    // Reset form and clear any previous values
    form.reset();

    // Add loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    // Remove any existing submit handlers to prevent duplicates
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    const updatedForm = document.getElementById('completeForm');
    const updatedSubmitBtn = updatedForm.querySelector('button[type="submit"]');

    // Handle form submission success and errors
    updatedForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        // Show loading state
        updatedSubmitBtn.disabled = true;
        updatedSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Completing...';

        // Submit form via AJAX to catch errors
        const formData = new FormData(updatedForm);
        fetch(updatedForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                // Success - reload the page to update the appointment status
                window.location.reload(); // Or redirect to success page
            } else {
                // Handle errors
                return response.json().then(data => {
                    console.error('Error completing appointment:', data);
                    showNotification(data.message || 'Failed to complete appointment. Please try again.', 'error');
                    // Reset button state
                    updatedSubmitBtn.disabled = false;
                    updatedSubmitBtn.innerHTML = originalText;
                    // Close modal so user can try again
                    const modal = bootstrap.Modal.getInstance(document.getElementById('completeModal'));
                    if (modal) modal.hide();
                }).catch(() => {
                    // If response isn't JSON, show generic error
                    showNotification('Failed to complete appointment. Please try again.', 'error');
                    updatedSubmitBtn.disabled = false;
                    updatedSubmitBtn.innerHTML = originalText;
                    const modal = bootstrap.Modal.getInstance(document.getElementById('completeModal'));
                    if (modal) modal.hide();
                });
            }
        })
        .catch(error => {
            console.error('Network error completing appointment:', error);
            showNotification('Network error. Please check your connection and try again.', 'error');
            updatedSubmitBtn.disabled = false;
            updatedSubmitBtn.innerHTML = originalText;
            const modal = bootstrap.Modal.getInstance(document.getElementById('completeModal'));
            if (modal) modal.hide();
        });
    });

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();

    // Reset button when modal is closed
    document.getElementById('completeModal').addEventListener('hidden.bs.modal', function resetModalState() {
        updatedSubmitBtn.disabled = false;
        updatedSubmitBtn.innerHTML = originalText;
        updatedForm.reset(); // Clear form data
        // Remove this event listener to prevent duplicates
        document.getElementById('completeModal').removeEventListener('hidden.bs.modal', resetModalState);
    });
}

function cancelAppointment(appointmentId) {
    const form = document.getElementById('cancelForm');
    form.action = `/doctor/appointments/${appointmentId}/cancel`;
    
    // Reset form and clear any previous values
    form.reset();

    // Add loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    // Remove any existing submit handlers to prevent duplicates
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    const updatedForm = document.getElementById('cancelForm');
    const updatedSubmitBtn = updatedForm.querySelector('button[type="submit"]');

    // Handle form submission success and errors
    updatedForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        // Show loading state
        updatedSubmitBtn.disabled = true;
        updatedSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

        // Submit form via AJAX to catch errors
        const formData = new FormData(updatedForm);
        fetch(updatedForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                // Success - redirect to appointments page
                window.location.reload(); // Or redirect to success page
            } else {
                // Handle errors
                return response.json().then(data => {
                    console.error('Error cancelling appointment:', data);
                    showNotification(data.message || 'Failed to cancel appointment. Please try again.', 'error');
                    // Reset button state
                    updatedSubmitBtn.disabled = false;
                    updatedSubmitBtn.innerHTML = originalText;
                    // Close modal so user can try again
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
                    if (modal) modal.hide();
                }).catch(() => {
                    // If response isn't JSON, show generic error
                    showNotification('Failed to cancel appointment. Please try again.', 'error');
                    updatedSubmitBtn.disabled = false;
                    updatedSubmitBtn.innerHTML = originalText;
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
                    if (modal) modal.hide();
                });
            }
        })
        .catch(error => {
            console.error('Network error cancelling appointment:', error);
            showNotification('Network error. Please check your connection and try again.', 'error');
            updatedSubmitBtn.disabled = false;
            updatedSubmitBtn.innerHTML = originalText;
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
            if (modal) modal.hide();
        });
    });

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();

    // Reset button when modal is closed
    document.getElementById('cancelModal').addEventListener('hidden.bs.modal', function resetModalState() {
        updatedSubmitBtn.disabled = false;
        updatedSubmitBtn.innerHTML = originalText;
        updatedForm.reset(); // Clear form data
        // Remove this event listener to prevent duplicates
        document.getElementById('cancelModal').removeEventListener('hidden.bs.modal', resetModalState);
    });
}

// Create confirm appointment function for direct button clicks
function confirmAppointment(appointmentId) {
    // Show confirmation dialog
    if (confirm('Are you sure you want to confirm this appointment?')) {
        // Find and disable the button
        const btn = event.target.closest('button');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        // Create a temporary form to submit the confirmation
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/doctor/appointments/${appointmentId}/confirm`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.appendChild(csrfToken);
        document.body.appendChild(form);

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                response.json().then(data => {
                    showNotification(data.message || 'Failed to confirm appointment. Please try again.', 'error');
                }).catch(() => {
                    showNotification('Failed to confirm appointment. Please try again.', 'error');
                });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                }
            }
        })
        .catch(error => {
            showNotification('Network error. Please check your connection and try again.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i>';
            }
        })
        .finally(() => {
            if (document.body.contains(form)) {
                document.body.removeChild(form);
            }
        });
    }
}

function markNoShow(appointmentId) {
    // Enhanced confirmation with better UX
    const confirmed = confirm('Are you sure you want to mark this appointment as no show? This action cannot be undone.');

    if (confirmed) {
        // Find the button that triggered this (using a different approach)
        const buttons = document.querySelectorAll(`button[onclick="markNoShow(${appointmentId})"]`);
        const triggerButton = buttons.length > 0 ? buttons[0] : null;

        let originalHTML = null;
        if (triggerButton) {
            originalHTML = triggerButton.innerHTML;
            triggerButton.disabled = true;
            triggerButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        }

        // Create a temporary form to submit the no-show action
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/doctor/appointments/${appointmentId}/no-show`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.appendChild(csrfToken);
        document.body.appendChild(form);

        // Submit via AJAX to properly handle errors
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                // Success - reload the page to update the appointment status
                window.location.reload();
            } else {
                // Handle errors
                response.json().then(data => {
                    console.error('Error marking appointment as no-show:', data);
                    showNotification(data.message || 'Failed to mark appointment as no-show. Please try again.', 'error');
                }).catch(() => {
                    showNotification('Failed to mark appointment as no-show. Please try again.', 'error');
                });
            }
        })
        .catch(error => {
            console.error('Network error marking appointment as no-show:', error);
            showNotification('Network error. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            // Reset button state if we found it
            if (triggerButton) {
                triggerButton.disabled = false;
                triggerButton.innerHTML = originalHTML;
            }
            // Remove the temporary form
            if (document.body.contains(form)) {
                document.body.removeChild(form);
            }
        });
    }
}

// Accessibility enhancements
document.addEventListener('keydown', function(e) {
    // Close modals with Escape key
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) modal.hide();
        }
    }
});

// Add ARIA labels and roles for better accessibility
document.addEventListener('DOMContentLoaded', function() {
    // Add ARIA labels to status badges
    document.querySelectorAll('.badge').forEach(badge => {
        const status = badge.textContent.toLowerCase().trim();
        badge.setAttribute('aria-label', `Appointment status: ${status}`);
        badge.setAttribute('role', 'status');
    });

    // Add ARIA labels to action buttons
    document.querySelectorAll('button[title]').forEach(button => {
        button.setAttribute('aria-label', button.getAttribute('title'));
    });

    // Add live region for notifications
    const liveRegion = document.createElement('div');
    liveRegion.id = 'live-region';
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'visually-hidden';
    document.body.appendChild(liveRegion);

    // Update live region when showing notifications
    const originalShowNotification = window.showNotification;
    window.showNotification = function(message, type) {
        originalShowNotification(message, type);
        liveRegion.textContent = message;
        setTimeout(() => {
            liveRegion.textContent = '';
        }, 1000);
    };
});

// Error boundary for JavaScript errors
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    showNotification('An unexpected error occurred. Please refresh the page.', 'error');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    showNotification('A background process failed. Some features may not work correctly.', 'warning');
});
</script>
@endsection
