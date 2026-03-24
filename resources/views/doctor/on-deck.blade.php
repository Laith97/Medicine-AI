@extends('master')

@section('title', 'On-Deck Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/on-deck.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
@endpush

@section('content')
<div class="on-deck-container">
    <div class="container">
        <!-- On-Deck Header -->
        <div class="on-deck-header">
            <h1><i class="fas fa-list-check me-3"></i>On-Deck Dashboard</h1>
            <p>Real-time appointment tracking and management</p>
        </div>

        <!-- Filters and Search -->
        <div class="on-deck-filters">
            <div class="filter-controls">
                <div class="form-group">
                    <label class="form-label">Search Appointments</label>
                    <input type="text" id="appointment-search" class="form-control" placeholder="Search by patient name...">
                </div>
                <div class="form-group">
                    <label class="form-label">Status Filter</label>
                    <select id="status-filter" class="form-select">
                        <option value="all">All Statuses</option>
                        <option value="check_in">Check-in</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sort By</label>
                    <select id="sort-filter" class="form-select">
                        <option value="time">Appointment Time</option>
                        <option value="priority">Priority</option>
                        <option value="status">Status</option>
                    </select>
                </div>
                <button id="refresh-appointments" class="btn btn-primary-custom">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
        </div>

        <!-- Appointments List -->
        <div id="appointments-container">
            @if($appointments->count() > 0)
                <div id="appointments-list" class="sortable-list">
                    @foreach($appointments as $appointment)
                        <div class="appointment-card priority-{{ $appointment->priority ?? 'low' }}"
                             data-id="{{ $appointment->id }}"
                             data-status="{{ $appointment->status }}"
                             data-time="{{ $appointment->appointment_date->timestamp }}">
                            <div class="realtime-indicator" id="indicator-{{ $appointment->id }}"></div>

                            <div class="appointment-header">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                    <div>
                                        <div class="appointment-time">
                                            {{ $appointment->appointment_date->format('g:i A') }}
                                        </div>
                                        <div class="appointment-patient">
                                            {{ $appointment->patient_name }}
                                        </div>
                                    </div>
                                </div>
                                <div class="status-indicator status-{{ str_replace('_', '-', $appointment->status) }}">
                                    <i class="fas fa-{{ $appointment->status === 'check_in' ? 'user-check' : ($appointment->status === 'in_progress' ? 'spinner fa-spin' : ($appointment->status === 'completed' ? 'check-circle' : 'user-times')) }}"></i>
                                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                </div>
                            </div>

                            <div class="appointment-details">
                                <div><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</div>
                                <div><strong>Reason:</strong> {{ $appointment->reason }}</div>
                                @if($appointment->doctor)
                                    <div><strong>Doctor:</strong> {{ $appointment->doctor->user->name }}</div>
                                @endif
                            </div>

                            <div class="appointment-actions">
                                @if($appointment->status === 'check_in')
                                    <button class="btn btn-sm btn-primary-custom status-btn"
                                            data-action="start"
                                            data-id="{{ $appointment->id }}">
                                        <i class="fas fa-play me-1"></i>Start
                                    </button>
                                @elseif($appointment->status === 'in_progress')
                                    <button class="btn btn-sm btn-success status-btn"
                                            data-action="complete"
                                            data-id="{{ $appointment->id }}">
                                        <i class="fas fa-check me-1"></i>Complete
                                    </button>
                                @endif

                                @if(in_array($appointment->status, ['check_in', 'in_progress']))
                                    <button class="btn btn-sm btn-danger status-btn"
                                            data-action="no_show"
                                            data-id="{{ $appointment->id }}">
                                        <i class="fas fa-user-times me-1"></i>No Show
                                    </button>
                                @endif

                                <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="on-deck-empty">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No appointments on deck</h4>
                    <p>All appointments are completed or no appointments are scheduled.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Appointment Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to update this appointment status?</p>
                <div id="status-details"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-status-update">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize drag and drop
    initializeSortable();

    // Initialize real-time updates
    initializeRealtimeUpdates();

    // Initialize filters
    initializeFilters();

    // Initialize status update handlers
    initializeStatusHandlers();
});

function initializeSortable() {
    const container = document.getElementById('appointments-list');
    if (!container) return;

    new Sortable(container, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onStart: function(evt) {
            evt.item.classList.add('dragging');
        },
        onEnd: function(evt) {
            evt.item.classList.remove('dragging');
            updateAppointmentOrder();
        }
    });
}

function initializeRealtimeUpdates() {
    // Check if Laravel Echo is available
    if (typeof window.Echo !== 'undefined') {
        // Listen for appointment status updates
        window.Echo.private('appointments')
            .listen('.appointment.status.updated', (event) => {
                updateAppointmentStatus(event.appointmentId, event.status);
            })
            .listen('.appointment.created', (event) => {
                addNewAppointment(event.appointment);
            })
            .listen('.appointment.updated', (event) => {
                updateAppointmentData(event.appointment);
            });
    }
}

function initializeFilters() {
    const searchInput = document.getElementById('appointment-search');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');
    const refreshBtn = document.getElementById('refresh-appointments');

    // Search functionality
    searchInput.addEventListener('input', filterAppointments);

    // Status filter
    statusFilter.addEventListener('change', filterAppointments);

    // Sort functionality
    sortFilter.addEventListener('change', sortAppointments);

    // Refresh button
    refreshBtn.addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';
        this.disabled = true;

        // Simulate refresh
        setTimeout(() => {
            location.reload();
        }, 1000);
    });
}

function initializeStatusHandlers() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.status-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.status-btn');
            const action = btn.getAttribute('data-action');
            const appointmentId = btn.getAttribute('data-id');

            handleStatusUpdate(appointmentId, action, btn);
        }
    });
}

function filterAppointments() {
    const searchTerm = document.getElementById('appointment-search').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const cards = document.querySelectorAll('.appointment-card');

    cards.forEach(card => {
        const patientName = card.querySelector('.appointment-patient').textContent.toLowerCase();
        const status = card.getAttribute('data-status');

        const matchesSearch = patientName.includes(searchTerm);
        const matchesStatus = statusFilter === 'all' || status === statusFilter;

        card.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}

function sortAppointments() {
    const sortBy = document.getElementById('sort-filter').value;
    const container = document.getElementById('appointments-list');
    const cards = Array.from(container.children);

    cards.sort((a, b) => {
        switch (sortBy) {
            case 'time':
                return parseInt(a.getAttribute('data-time')) - parseInt(b.getAttribute('data-time'));
            case 'priority':
                const priorityOrder = { 'high': 3, 'medium': 2, 'low': 1 };
                const aPriority = a.classList.contains('priority-high') ? 'high' :
                                a.classList.contains('priority-medium') ? 'medium' : 'low';
                const bPriority = b.classList.contains('priority-high') ? 'high' :
                                b.classList.contains('priority-medium') ? 'medium' : 'low';
                return priorityOrder[bPriority] - priorityOrder[aPriority];
            case 'status':
                const statusOrder = { 'check_in': 1, 'in_progress': 2, 'completed': 3, 'no_show': 4 };
                return statusOrder[a.getAttribute('data-status')] - statusOrder[b.getAttribute('data-status')];
            default:
                return 0;
        }
    });

    cards.forEach(card => container.appendChild(card));
}

function handleStatusUpdate(appointmentId, action, btn) {
    // Show loading state
    btn.classList.add('status-updating');
    btn.disabled = true;

    // Make API call to update status
    fetch(`/doctor/appointments/${appointmentId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateAppointmentStatus(appointmentId, action);
        } else {
            alert('Failed to update appointment status');
        }
    })
    .catch(error => {
        // console.error('Error updating status:', error);
        alert('An error occurred while updating the appointment status');
    })
    .finally(() => {
        btn.classList.remove('status-updating');
        btn.disabled = false;
    });
}

function updateAppointmentStatus(appointmentId, newStatus) {
    const card = document.querySelector(`[data-id="${appointmentId}"]`);
    if (!card) return;

    // Update data attribute
    card.setAttribute('data-status', newStatus);

    // Update status indicator
    const statusIndicator = card.querySelector('.status-indicator');
    const statusClasses = ['status-check-in', 'status-in-progress', 'status-completed', 'status-no-show'];
    statusIndicator.classList.remove(...statusClasses);
    statusIndicator.classList.add(`status-${newStatus.replace('_', '-')}`);

    // Update status text and icon
    const statusText = newStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    const statusIcons = {
        'check_in': 'user-check',
        'in_progress': 'spinner fa-spin',
        'completed': 'check-circle',
        'no_show': 'user-times'
    };

    statusIndicator.innerHTML = `
        <i class="fas fa-${statusIcons[newStatus]}"></i>
        ${statusText}
    `;

    // Update action buttons
    updateActionButtons(card, newStatus);

    // Trigger visual feedback
    card.style.animation = 'none';
    setTimeout(() => {
        card.style.animation = 'pulse 0.5s ease-in-out';
    }, 10);
}

function updateActionButtons(card, status) {
    const actionsContainer = card.querySelector('.appointment-actions');
    let buttonsHtml = '';

    if (status === 'check_in') {
        buttonsHtml = `
            <button class="btn btn-sm btn-primary-custom status-btn" data-action="start" data-id="${card.getAttribute('data-id')}">
                <i class="fas fa-play me-1"></i>Start
            </button>
            <button class="btn btn-sm btn-danger status-btn" data-action="no_show" data-id="${card.getAttribute('data-id')}">
                <i class="fas fa-user-times me-1"></i>No Show
            </button>
        `;
    } else if (status === 'in_progress') {
        buttonsHtml = `
            <button class="btn btn-sm btn-success status-btn" data-action="complete" data-id="${card.getAttribute('data-id')}">
                <i class="fas fa-check me-1"></i>Complete
            </button>
            <button class="btn btn-sm btn-danger status-btn" data-action="no_show" data-id="${card.getAttribute('data-id')}">
                <i class="fas fa-user-times me-1"></i>No Show
            </button>
        `;
    }

    buttonsHtml += `
        <a href="/doctor/appointments/${card.getAttribute('data-id')}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-eye me-1"></i>Details
        </a>
    `;

    actionsContainer.innerHTML = buttonsHtml;
}

function updateAppointmentOrder() {
    const cards = document.querySelectorAll('.appointment-card');
    const order = Array.from(cards).map(card => card.getAttribute('data-id'));

    // Send order update to server
    fetch('/doctor/appointments/reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ order: order })
    }).catch(error => {
        // console.error('Error updating appointment order:', error);
    });
}

function addNewAppointment(appointment) {
    // This would be called when a new appointment is added via real-time updates
    // Implementation would depend on the appointment data structure
    // console.log('New appointment added:', appointment);
}

function updateAppointmentData(appointment) {
    // Update appointment data when changed
    // console.log('Appointment updated:', appointment);
}

// Touch event handlers for tablets
document.addEventListener('touchstart', function(e) {
    if (e.target.closest('.appointment-card')) {
        const card = e.target.closest('.appointment-card');
        card.classList.add('touch-active');
    }
});

document.addEventListener('touchend', function(e) {
    document.querySelectorAll('.appointment-card.touch-active').forEach(card => {
        card.classList.remove('touch-active');
    });
});
</script>
@endsection
