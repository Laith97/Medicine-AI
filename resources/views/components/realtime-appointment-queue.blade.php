@props([
    'appointments' => [],
    'showQueuePosition' => true,
    'showEstimatedWait' => true,
    'showStatusActions' => true,
    'enableDragDrop' => true,
    'title' => 'Appointment Queue',
    'subtitle' => 'Real-time appointment tracking'
])

<div class="realtime-appointment-queue"
     data-queue-id="{{ uniqid('queue_') }}"
     data-user-id="{{ auth()->id() }}"
     data-user-role="{{ auth()->user()->role }}"
     data-doctor-id="{{ auth()->user()->doctor?->id }}">

    <!-- Queue Header -->
    <div class="queue-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">
                    <i class="fas fa-list-check me-2"></i>
                    {{ $title }}
                </h5>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>

            <!-- Real-time Connection Status -->
            <div class="realtime-connection-indicator">
                <div class="connection-status-icon"></div>
                <span class="connection-status-text">Connecting...</span>
            </div>
        </div>

        <!-- Queue Statistics -->
        @if($showQueuePosition)
            <div class="queue-stats mb-4">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stat-item">
                            <div class="stat-number" id="queue-total">0</div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <div class="stat-number" id="queue-waiting">0</div>
                            <div class="stat-label">Waiting</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <div class="stat-number" id="queue-in-progress">0</div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-item">
                            <div class="stat-number" id="queue-avg-wait">0m</div>
                            <div class="stat-label">Avg Wait</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="queue-filters mb-3">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text"
                           id="queue-search"
                           class="form-control form-control-sm"
                           placeholder="Search by patient name..."
                           data-filter="search">
                </div>
                <div class="col-md-3">
                    <select id="status-filter"
                            class="form-select form-select-sm"
                            data-filter="status">
                        <option value="all">All Statuses</option>
                        <option value="waiting">Waiting</option>
                        <option value="ready">Ready</option>
                        <option value="check_in">Check-in</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="priority-filter"
                            class="form-select form-select-sm"
                            data-filter="priority">
                        <option value="all">All Priorities</option>
                        <option value="high">High Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="low">Low Priority</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="refresh-queue"
                            class="btn btn-outline-primary btn-sm w-100"
                            data-action="refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments List -->
    <div class="appointments-container">
        <div id="appointments-list"
             class="appointments-list {{ $enableDragDrop ? 'sortable-list' : '' }}"
             data-drag-enabled="{{ $enableDragDrop ? 'true' : 'false' }}">

            @forelse($appointments as $appointment)
                @include('components.appointment-card', [
                    'appointment' => $appointment,
                    'showQueuePosition' => $showQueuePosition,
                    'showEstimatedWait' => $showEstimatedWait,
                    'showStatusActions' => $showStatusActions,
                    'enableDragDrop' => $enableDragDrop
                ])
            @empty
                <div class="empty-queue">
                    <i class="fas fa-calendar-times"></i>
                    <h6>No appointments in queue</h6>
                    <p class="text-muted">Appointments will appear here when they're ready</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Status Update Modal -->
    @if($showStatusActions)
        <div class="modal fade" id="statusUpdateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Appointment Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="status-update-content">
                            <!-- Dynamic content will be loaded here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirm-status-update">Confirm Update</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
    @vite(['public/css/realtime-appointments.css'])
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the real-time appointment queue
            window.RealtimeAppointmentQueue = new RealtimeAppointmentQueue({
                container: document.querySelector('.realtime-appointment-queue'),
                enableDragDrop: {{ $enableDragDrop ? 'true' : 'false' }},
                showQueuePosition: {{ $showQueuePosition ? 'true' : 'false' }},
                showEstimatedWait: {{ $showEstimatedWait ? 'true' : 'false' }},
                userId: {{ auth()->id() }},
                userRole: '{{ auth()->user()->role }}',
                doctorId: {{ auth()->user()->doctor?->id ?? 'null' }},
                appointmentApi: '{{ route("api.appointments.index") }}',
                statusUpdateApi: '{{ route("api.appointments.status") }}',
                csrfToken: '{{ csrf_token() }}'
            });

            // Make available globally for debugging
            window.queueInstance = window.RealtimeAppointmentQueue;
        });
    </script>
@endpush
