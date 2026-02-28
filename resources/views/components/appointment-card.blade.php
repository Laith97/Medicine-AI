@props([
    'appointment' => null,
    'showQueuePosition' => true,
    'showEstimatedWait' => true,
    'showStatusActions' => true,
    'enableDragDrop' => false,
    'compact' => false
])

@php
    $appointmentId = $appointment->id ?? uniqid('temp_');
    $status = $appointment->status ?? 'pending';
    $patientName = $appointment->patient?->name ?? $appointment->patient_name ?? 'Unknown Patient';
    $appointmentTime = $appointment->appointment_date ?? now();
    $queuePosition = $appointment->queue_position ?? null;
    $estimatedWait = $appointment->estimated_wait_minutes ?? null;
    $priority = $appointment->priority ?? 'normal';
@endphp

<div class="appointment-card appointment-card-realtime {{ $compact ? 'appointment-card-compact' : '' }}"
     data-appointment-id="{{ $appointmentId }}"
     data-status="{{ $status }}"
     data-queue-position="{{ $queuePosition }}"
     data-priority="{{ $priority }}"
     data-appointment-time="{{ $appointmentTime->timestamp ?? now()->timestamp }}"
     data-draggable="{{ $enableDragDrop ? 'true' : 'false' }}">

    <!-- Real-time Status Indicator -->
    <div class="realtime-indicator {{ $status === 'completed' ? 'offline' : 'online' }}"
         id="realtime-indicator-{{ $appointmentId }}"
         data-appointment-id="{{ $appointmentId }}"></div>

    <!-- Drag Handle -->
    @if($enableDragDrop)
        <div class="drag-handle">
            <i class="fas fa-grip-vertical"></i>
        </div>
    @endif

    <!-- Appointment Header -->
    <div class="appointment-header">
        <div class="appointment-time-info">
            <div class="appointment-time">
                {{ $appointmentTime->format('g:i A') }}
            </div>
            <div class="appointment-duration">
                {{ $appointment->duration ?? 30 }}min
            </div>
        </div>

        <div class="appointment-status-info">
            <span class="status-badge status-{{ str_replace('_', '-', $status) }} bg-{{ $status === 'confirmed' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary') }}">
                <i class="fas fa-{{ $status === 'confirmed' ? 'check-circle' : ($status === 'pending' ? 'clock' : ($status === 'completed' ? 'check-double' : 'calendar')) }}"></i>
                {{ ucfirst(str_replace('_', ' ', $status)) }}
            </span>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="appointment-patient-info">
        <div class="patient-name">{{ $patientName }}</div>
        @if($appointment->appointment_type)
            <div class="appointment-type">
                <i class="fas fa-{{ $appointment->appointment_type === 'video_call' ? 'video' : ($appointment->appointment_type === 'phone_call' ? 'phone' : 'hospital') }}"></i>
                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
            </div>
        @endif
    </div>

    <!-- Queue Position and Wait Time -->
    <div class="appointment-meta">
        @if($showQueuePosition && $queuePosition)
            <div class="queue-position position-{{ $priority }}" data-position="{{ $queuePosition }}">
                #{{ $queuePosition }}
            </div>
        @endif

        @if($showEstimatedWait && $estimatedWait)
            @php
                $waitClass = $estimatedWait <= 15 ? 'wait-short' : ($estimatedWait <= 45 ? 'wait-medium' : 'wait-long');
            @endphp
            <div class="estimated-wait {{ $waitClass }}" data-wait-time="{{ $estimatedWait }}">
                @if($estimatedWait >= 60)
                    {{ intval($estimatedWait / 60) }}h {{ $estimatedWait % 60 }}m
                @else
                    {{ $estimatedWait }}m
                @endif
            </div>
        @endif

        @if($appointment->doctor)
            <div class="doctor-info">
                <i class="fas fa-user-md"></i>
                {{ $appointment->doctor->user->name ?? 'Unknown Doctor' }}
            </div>
        @endif
    </div>

    <!-- Appointment Reason/Notes -->
    @if($appointment->reason)
        <div class="appointment-reason">
            <small class="text-muted">{{ Str::limit($appointment->reason, 100) }}</small>
        </div>
    @endif

    <!-- Action Buttons -->
    @if($showStatusActions)
        <div class="appointment-actions">
            @if($status === 'check_in')
                <button class="btn btn-sm btn-primary status-action"
                        data-action="start"
                        data-appointment-id="{{ $appointmentId }}">
                    <i class="fas fa-play"></i>
                    Start
                </button>
                <button class="btn btn-sm btn-danger status-action"
                        data-action="no_show"
                        data-appointment-id="{{ $appointmentId }}">
                    <i class="fas fa-user-times"></i>
                    No Show
                </button>
            @elseif($status === 'in_progress')
                <button class="btn btn-sm btn-success status-action"
                        data-action="complete"
                        data-appointment-id="{{ $appointmentId }}">
                    <i class="fas fa-check"></i>
                    Complete
                </button>
                <button class="btn btn-sm btn-danger status-action"
                        data-action="no_show"
                        data-appointment-id="{{ $appointmentId }}">
                    <i class="fas fa-user-times"></i>
                    No Show
                </button>
            @elseif($status === 'ready')
                <button class="btn btn-sm btn-info status-action"
                        data-action="call_patient"
                        data-appointment-id="{{ $appointmentId }}">
                    <i class="fas fa-bell"></i>
                    Call Patient
                </button>
            @endif

            @if(in_array($status, ['check_in', 'in_progress', 'ready']))
                <button class="btn btn-sm btn-outline-warning status-action"
                        data-action="reschedule"
                        data-appointment-id="{{ $appointmentId }}">
                    <i class="fas fa-calendar-alt"></i>
                    Reschedule
                </button>
            @endif

            <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('doctor.appointments.show', $appointment) }}">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a>
                    </li>
                    @if($appointment)
                        <li>
                            <a class="dropdown-item" href="{{ route('doctor.appointments.edit', $appointment) }}">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#"
                           onclick="window.RealtimeAppointmentQueue?.showStatusUpdateModal('{{ $appointmentId }}', 'cancel')">
                            <i class="fas fa-times me-2"></i>Cancel Appointment
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    @endif

    <!-- Last Updated Timestamp -->
    <div class="realtime-timestamp">
        @if($appointment && $appointment->updated_at)
            <small class="text-muted">
                Updated {{ $appointment->updated_at->diffForHumans() }}
            </small>
        @endif
    </div>

    <!-- Priority Indicator -->
    @if($priority !== 'normal')
        <div class="priority-indicator priority-{{ $priority }}">
            <i class="fas fa-{{ $priority === 'high' ? 'exclamation-triangle' : ($priority === 'medium' ? 'exclamation-circle' : 'info-circle') }}"></i>
        </div>
    @endif
</div>

<style>
.appointment-card {
    position: relative;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.appointment-card-compact {
    padding: 12px;
}

.appointment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.appointment-card .realtime-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    z-index: 10;
}

.appointment-card .drag-handle {
    position: absolute;
    top: 8px;
    left: 8px;
    color: #6c757d;
    cursor: grab;
    padding: 4px;
}

.appointment-card .drag-handle:active {
    cursor: grabbing;
}

.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    padding-left: 20px; /* Space for drag handle */
}

.appointment-time-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.appointment-time {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.appointment-duration {
    font-size: 0.8rem;
    color: #6c757d;
}

.appointment-status-info {
    flex-shrink: 0;
}

.appointment-patient-info {
    margin-bottom: 12px;
    padding-left: 20px; /* Space for drag handle */
}

.patient-name {
    font-size: 1rem;
    font-weight: 500;
    color: #212529;
    margin-bottom: 4px;
}

.appointment-type {
    font-size: 0.85rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.appointment-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding-left: 20px; /* Space for drag handle */
    flex-wrap: wrap;
}

.appointment-reason {
    margin-bottom: 12px;
    padding-left: 20px; /* Space for drag handle */
}

.appointment-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 8px;
    padding-left: 20px; /* Space for drag handle */
}

.realtime-timestamp {
    text-align: right;
    padding-left: 20px; /* Space for drag handle */
}

.priority-indicator {
    position: absolute;
    top: 8px;
    left: 30px; /* Next to drag handle */
    color: #dc3545;
    font-size: 0.8rem;
}

.priority-indicator.priority-medium {
    color: #ffc107;
}

.priority-indicator.priority-low {
    color: #17a2b8;
}

/* Compact mode adjustments */
.appointment-card-compact .appointment-header {
    margin-bottom: 8px;
}

.appointment-card-compact .appointment-patient-info {
    margin-bottom: 8px;
}

.appointment-card-compact .appointment-meta {
    margin-bottom: 8px;
}

.appointment-card-compact .appointment-actions {
    margin-bottom: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .appointment-card {
        padding: 12px;
    }

    .appointment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding-left: 30px; /* More space for mobile drag handle */
    }

    .appointment-patient-info,
    .appointment-meta,
    .appointment-reason,
    .appointment-actions,
    .realtime-timestamp {
        padding-left: 30px;
    }

    .appointment-actions {
        justify-content: center;
    }
}
</style>
