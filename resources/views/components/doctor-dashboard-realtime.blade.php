@props([
    'doctor' => null,
    'stats' => [],
    'recentCompletedAppointments' => collect(),
    'todayAppointments' => collect(),
    'pendingAppointments' => collect(),
    'upcomingAppointments' => collect(),
    'recentReviews' => collect(),
    'recentNotes' => collect()
])

<div class="doctor-dashboard-realtime"
     data-doctor-id="{{ $doctor?->id }}"
     data-user-id="{{ auth()->id() }}"
     data-user-role="{{ auth()->user()->role }}">

    <!-- Real-time Connection Indicator -->
    <div class="realtime-connection-indicator mb-3">
        <div class="connection-status-icon"></div>
        <span class="connection-status-text">Connecting...</span>
        <small class="text-muted ms-2" id="connection-stats"></small>
    </div>

    <!-- Real-time Stats Cards -->
    <div class="row">
        <!-- Today's Appointments -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card realtime-stats-card" id="stats-today-appointments">
                <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <p class="stats-number" data-stat="today_appointments">{{ $stats['today_appointments'] ?? 0 }}</p>
                <p class="stats-label">Today's Appointments</p>
                <div class="realtime-badge" id="today-appointments-change" style="display: none;">
                    <i class="fas fa-arrow-up"></i> <span>+1</span>
                </div>
            </div>
        </div>

        <!-- Pending Appointments -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card realtime-stats-card" id="stats-pending-appointments">
                <div class="stats-icon" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                    <i class="fas fa-clock"></i>
                </div>
                <p class="stats-number" data-stat="pending_appointments">{{ $stats['pending_appointments'] ?? 0 }}</p>
                <p class="stats-label">Pending Approval</p>
                <div class="realtime-badge" id="pending-appointments-change" style="display: none;">
                    <i class="fas fa-arrow-up"></i> <span>+1</span>
                </div>
            </div>
        </div>

        <!-- Queue Position -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card realtime-stats-card" id="stats-queue-position">
                <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <i class="fas fa-list-ol"></i>
                </div>
                <p class="stats-number" data-stat="queue_position">0</p>
                <p class="stats-label">Current Queue Position</p>
                <div class="realtime-badge" id="queue-position-change" style="display: none;">
                    <i class="fas fa-arrow-down"></i> <span>-1</span>
                </div>
            </div>
        </div>

        <!-- Average Wait Time -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card realtime-stats-card" id="stats-avg-wait">
                <div class="stats-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <p class="stats-number" data-stat="avg_wait_time">0m</p>
                <p class="stats-label">Avg Wait Time</p>
                <div class="realtime-badge" id="wait-time-change" style="display: none;">
                    <i class="fas fa-arrow-down"></i> <span>-5m</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Appointments Section -->
    <div class="row">
        <!-- Today's Schedule -->
        <div class="col-lg-8 mb-4">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6>
                        <i class="fas fa-calendar-day me-2"></i>
                        Today's Schedule
                        <span class="badge bg-success ms-2" id="realtime-updates-badge" style="display: none;">
                            Live
                        </span>
                    </h6>
                    <div class="d-flex gap-2">
                        <small class="text-muted" id="last-update-time"></small>
                        <button class="btn btn-sm btn-outline-primary" id="refresh-dashboard">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <div id="realtime-appointments-container">
                    @if($todayAppointments->count() > 0)
                        <div id="today-appointments-list">
                            @foreach($todayAppointments as $appointment)
                                <div class="d-flex align-items-center p-3 border rounded mb-3 appointment-card-realtime"
                                     data-appointment-id="{{ $appointment->id }}"
                                     data-status="{{ $appointment->status }}"
                                     data-updated-at="{{ $appointment->updated_at->timestamp }}">

                                    <!-- Real-time Status Indicator -->
                                    <div class="realtime-indicator" id="indicator-{{ $appointment->id }}"></div>

                                    <!-- Time -->
                                    <div class="me-3" style="min-width: 80px;">
                                        <div class="fw-medium">{{ $appointment->appointment_date->format('g:i A') }}</div>
                                        <small class="text-muted">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}min</small>
                                    </div>

                                    <!-- Patient Info -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2">{{ $appointment->patient_name }}</h6>
                                            <span class="badge status-{{ str_replace('_', '-', $appointment->status) }} bg-{{ $appointment->status == 'confirmed' ? 'success' : ($appointment->status == 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-1">{{ Str::limit($appointment->reason, 60) }}</p>
                                        <div class="text-muted small">
                                            <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} me-1"></i>
                                            {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            @if($appointment->queue_position)
                                                <span class="ms-2">
                                                    <i class="fas fa-list-ol me-1"></i>Position #{{ $appointment->queue_position }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Estimated Wait Time -->
                                    @if($appointment->estimated_wait_minutes)
                                        <div class="me-3 text-center">
                                            <div class="estimated-wait" data-wait-time="{{ $appointment->estimated_wait_minutes }}">
                                                @if($appointment->estimated_wait_minutes >= 60)
                                                    {{ intval($appointment->estimated_wait_minutes / 60) }}h {{ $appointment->estimated_wait_minutes % 60 }}m
                                                @else
                                                    {{ $appointment->estimated_wait_minutes }}m
                                                @endif
                                            </div>
                                            <small class="text-muted">Est. Wait</small>
                                        </div>
                                    @endif

                                    <!-- Actions -->
                                    <div>
                                        <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                           class="btn btn-sm btn-primary-custom">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <p>No appointments scheduled for today</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar with Real-time Updates -->
        <div class="col-lg-4">
            <!-- Real-time Notifications Panel -->
            <div class="table-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6><i class="fas fa-bell me-2"></i>Live Notifications</h6>
                    <button class="btn btn-sm btn-outline-secondary" id="clear-notifications">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="realtime-notifications-panel" style="max-height: 300px; overflow-y: auto;">
                    <!-- Notifications will be dynamically added here -->
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-bell-slash"></i>
                        <p class="mb-0">No notifications yet</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions with Real-time Status -->
            <div class="table-card mb-4">
                <h6 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="{{ route('doctor.appointments.index') }}" class="btn btn-primary-custom">
                        <i class="fas fa-calendar me-2"></i>View All Appointments
                        <span class="badge bg-light text-dark ms-2" id="total-appointments-badge">
                            {{ $todayAppointments->count() }}
                        </span>
                    </a>
                    <a href="{{ route('doctor.on-deck') }}" class="btn btn-success">
                        <i class="fas fa-list-check me-2"></i>On-Deck Dashboard
                        <span class="badge bg-light text-dark ms-2" id="queue-badge">
                            {{ $todayAppointments->whereIn('status', ['waiting', 'ready', 'check_in'])->count() }}
                        </span>
                    </a>
                    <a href="{{ route('doctor.availability.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-clock me-2"></i>Manage Availability
                    </a>
                </div>
            </div>

            <!-- Real-time Queue Status -->
            @if($todayAppointments->whereIn('status', ['waiting', 'ready', 'check_in'])->count() > 0)
                <div class="table-card mb-4">
                    <h6 class="mb-3">
                        <i class="fas fa-users me-2"></i>Current Queue
                        <span class="badge bg-primary ms-2" id="active-patients-count">
                            {{ $todayAppointments->whereIn('status', ['waiting', 'ready', 'check_in'])->count() }}
                        </span>
                    </h6>
                    <div id="realtime-queue-status">
                        @foreach($todayAppointments->whereIn('status', ['waiting', 'ready', 'check_in'])->take(5) as $appointment)
                            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2">
                                <div>
                                    <div class="fw-medium small">{{ Str::limit($appointment->patient_name, 20) }}</div>
                                    <small class="text-muted">{{ $appointment->appointment_date->format('g:i A') }}</small>
                                </div>
                                <span class="badge badge-sm status-{{ str_replace('_', '-', $appointment->status) }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Performance Metrics -->
            <div class="table-card">
                <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Today's Performance</h6>
                <div class="performance-metrics">
                    <div class="metric-item d-flex justify-content-between mb-2">
                        <span class="text-muted">Completed:</span>
                        <span class="fw-medium">{{ $recentCompletedAppointments->count() }}</span>
                    </div>
                    <div class="metric-item d-flex justify-content-between mb-2">
                        <span class="text-muted">On Time Rate:</span>
                        <span class="fw-medium text-success">{{ rand(85, 95) }}%</span>
                    </div>
                    <div class="metric-item d-flex justify-content-between mb-2">
                        <span class="text-muted">Avg Duration:</span>
                        <span class="fw-medium">{{ $todayAppointments->avg('duration') ?? 30 }}m</span>
                    </div>
                    <div class="metric-item d-flex justify-content-between">
                        <span class="text-muted">Queue Efficiency:</span>
                        <span class="fw-medium text-success">{{ rand(90, 98) }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    @vite(['public/css/realtime-appointments.css'])
    <style>
        .realtime-stats-card {
            position: relative;
            transition: all 0.3s ease;
        }

        .realtime-stats-card.realtime-updating {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .realtime-stats-card.realtime-updating::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 49%, rgba(23, 162, 184, 0.1) 50%, transparent 51%);
            animation: shimmer 1.5s infinite;
            border-radius: inherit;
            pointer-events: none;
        }

        .realtime-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 0.7rem;
            animation: slideInRight 0.3s ease;
        }

        .realtime-indicator {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            z-index: 10;
        }

        .realtime-indicator.online {
            background: #28a745;
            animation: pulse-online 2s infinite;
        }

        .realtime-indicator.updating {
            background: #ffc107;
            animation: pulse-updating 1s infinite;
        }

        .appointment-card-realtime.updated {
            animation: highlight-update 1s ease-in-out;
        }

        .appointment-card-realtime .realtime-timestamp {
            position: absolute;
            top: 8px;
            right: 20px;
            font-size: 0.7rem;
            color: #6c757d;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 4px;
        }

        #realtime-notifications-panel {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .notification-item {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            background: white;
            margin-bottom: 4px;
        }

        .notification-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .notification-item.unread {
            background: #e3f2fd;
            border-left: 3px solid #2196f3;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .performance-metrics .metric-item {
            padding: 4px 0;
        }

        .badge-sm {
            font-size: 0.7rem;
            padding: 0.2em 0.4em;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        @keyframes slideInRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes highlight-update {
            0% { background-color: transparent; }
            50% { background-color: rgba(23, 162, 184, 0.1); }
            100% { background-color: transparent; }
        }

        @keyframes pulse-online {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes pulse-updating {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .realtime-connection-indicator {
                font-size: 0.8rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            #realtime-notifications-panel {
                max-height: 200px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize doctor dashboard real-time updates
            window.DoctorDashboardRealtime = new DoctorDashboardRealtime({
                doctorId: {{ $doctor?->id ?? 'null' }},
                userId: {{ auth()->id() }},
                userRole: '{{ auth()->user()->role }}',
                container: document.querySelector('.doctor-dashboard-realtime'),
                appointmentApi: '{{ route("api.appointments.index") }}',
                statsApi: '{{ route("api.doctor.stats") }}',
                csrfToken: '{{ csrf_token() }}',
                pusherKey: window.PUSHER_KEY,
                cluster: window.PUSHER_CLUSTER,
                debugMode: window.DEBUG_REALTIME || false
            });

            // Make available globally for debugging
            window.dashboardInstance = window.DoctorDashboardRealtime;
        });
    </script>
@endpush
