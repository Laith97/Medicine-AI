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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Appointments</h2>
                    <p>Manage your appointments</p>
                </div>
                <div>
                    <a href="{{ route('doctor.appointments.create') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Book Appointment
                    </a>
                </div>
            </div>
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
                                                <button onclick="confirmAppointment({{ $appointment->id }})" class="btn btn-sm btn-outline-success" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
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
        broadcastingChannel = Echo.private(`user.${{ Auth::id() }}`)
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
