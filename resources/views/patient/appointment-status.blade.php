@extends('master')

@section('title', 'Appointment Status')

@section('content')
<div class="appointment-status-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Patient Status Display -->
                <div class="patient-status-display"
                     data-appointment-id="{{ $appointment->id ?? '' }}"
                     data-user-id="{{ auth()->id() }}">

                    <!-- Status Icon -->
                    <div class="status-icon status-{{ $currentStatus ?? 'pending' }}" id="status-icon">
                        <i class="fas fa-{{ $statusIcon ?? 'clock' }}"></i>
                    </div>

                    <!-- Status Title -->
                    <h1 class="status-title" id="status-title">
                        {{ $statusTitle ?? 'Appointment Pending' }}
                    </h1>

                    <!-- Status Description -->
                    <p class="status-description" id="status-description">
                        {{ $statusDescription ?? 'Your appointment is currently being processed. You will be notified of any updates.' }}
                    </p>

                    <!-- Next Steps -->
                    <div class="next-steps" id="next-steps">
                        @if(isset($nextSteps))
                            @foreach($nextSteps as $step)
                                <div class="step-item">
                                    <div class="step-icon">
                                        <i class="fas fa-{{ $step['icon'] ?? 'check' }}"></i>
                                    </div>
                                    <div class="step-content">
                                        <h6>{{ $step['title'] }}</h6>
                                        <p>{{ $step['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="step-item">
                                <div class="step-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="step-content">
                                    <h6>Waiting for Confirmation</h6>
                                    <p>Your doctor will review and confirm your appointment shortly.</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Timeline -->
                    <div class="status-timeline" id="status-timeline">
                        @if(isset($timeline) && count($timeline) > 0)
                            @foreach($timeline as $index => $timelineItem)
                                <div class="timeline-item {{ $timelineItem['status'] ?? 'pending' }}">
                                    <div class="timeline-icon">
                                        <i class="fas fa-{{ $timelineItem['icon'] ?? 'circle' }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">{{ $timelineItem['title'] }}</div>
                                        <div class="timeline-description">{{ $timelineItem['description'] }}</div>
                                        @if(isset($timelineItem['time']))
                                            <div class="timeline-time">{{ $timelineItem['time'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Default timeline -->
                            <div class="timeline-item completed">
                                <div class="timeline-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Appointment Requested</div>
                                    <div class="timeline-description">Your appointment request has been submitted</div>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($currentStatus ?? '', ['confirmed', 'check_in', 'in_progress']) ? 'active' : 'pending' }}">
                                <div class="timeline-icon">
                                    <i class="fas fa-{{ in_array($currentStatus ?? '', ['confirmed', 'check_in', 'in_progress']) ? 'spinner fa-spin' : 'clock' }}"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Awaiting Confirmation</div>
                                    <div class="timeline-description">Waiting for doctor confirmation</div>
                                </div>
                            </div>
                            <div class="timeline-item pending">
                                <div class="timeline-icon">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Doctor Review</div>
                                    <div class="timeline-description">Your appointment will be reviewed by the doctor</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Appointment Details -->
                    @if(isset($appointment) && $appointment)
                        <div class="appointment-details-card">
                            <h6><i class="fas fa-calendar-alt me-2"></i>Appointment Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Doctor:</label>
                                        <span>{{ $appointment->doctor?->user?->name ?? 'Unknown Doctor' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Date:</label>
                                        <span>{{ $appointment->appointment_date->format('l, F j, Y') }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Time:</label>
                                        <span>{{ $appointment->appointment_date->format('g:i A') }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Type:</label>
                                        <span>{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type ?? 'General')) }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Duration:</label>
                                        <span>{{ $appointment->duration ?? 30 }} minutes</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Status:</label>
                                        <span class="status-badge status-{{ str_replace('_', '-', $currentStatus ?? 'pending') }}">
                                            {{ ucfirst(str_replace('_', ' ', $currentStatus ?? 'pending')) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($appointment->reason)
                                <div class="detail-item full-width">
                                    <label>Reason:</label>
                                    <span>{{ $appointment->reason }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Estimated Wait Time -->
                    @if(isset($estimatedWaitTime) && $estimatedWaitTime > 0)
                        <div class="estimated-time-card">
                            <div class="time-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="time-content">
                                <h6>Estimated Wait Time</h6>
                                <div class="estimated-time">
                                    @if($estimatedWaitTime >= 60)
                                        {{ intval($estimatedWaitTime / 60) }} hour(s) {{ $estimatedWaitTime % 60 }} minutes
                                    @else
                                        {{ $estimatedWaitTime }} minutes
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        @if(isset($appointment) && $appointment)
                            <a href="{{ route('appointments.show', $appointment) }}"
                               class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>View Details
                            </a>
                            @if(in_array($currentStatus ?? '', ['confirmed', 'pending']))
                                <a href="{{ route('appointments.reschedule.form', $appointment) }}"
                                   class="btn btn-outline-warning">
                                    <i class="fas fa-calendar-alt me-2"></i>Reschedule
                                </a>
                            @endif
                            @if($currentStatus === 'pending')
                                <button type="button"
                                        class="btn btn-outline-danger"
                                        onclick="cancelAppointment()">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Updates Indicator -->
<div class="realtime-updates-indicator" id="realtime-updates">
    <div class="realtime-dot"></div>
    <span>Live Updates</span>
</div>

<!-- Cancel Appointment Modal -->
<div class="modal fade" id="cancelAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <div class="form-group">
                    <label for="cancellationReason">Reason for cancellation (optional):</label>
                    <textarea class="form-control" id="cancellationReason" rows="3"
                              placeholder="Please let us know why you're cancelling..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">Yes, Cancel Appointment</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    @vite(['public/css/realtime-appointments.css'])
    <style>
        .appointment-status-container {
            padding: 2rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .patient-status-display {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .patient-status-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .status-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
        }

        .status-icon::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            z-index: -1;
            animation: pulse-ring 2s infinite;
        }

        .status-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .status-description {
            font-size: 1.1rem;
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .next-steps {
            margin-bottom: 2rem;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
            flex-shrink: 0;
        }

        .step-content h6 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .step-content p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .status-timeline {
            border-top: 2px solid #ecf0f1;
            padding-top: 2rem;
            margin: 2rem 0;
            text-align: left;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 15px;
            top: 40px;
            width: 2px;
            height: calc(100% + 10px);
            background: #ecf0f1;
        }

        .timeline-item.completed::after {
            background: #27ae60;
        }

        .timeline-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
        }

        .timeline-item.completed .timeline-icon {
            background: #27ae60;
            color: white;
        }

        .timeline-item.active .timeline-icon {
            background: #3498db;
            color: white;
            animation: pulse 2s infinite;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .timeline-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            color: #95a5a6;
            font-size: 0.8rem;
        }

        .appointment-details-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }

        .appointment-details-card h6 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }

        .detail-item.full-width {
            flex-direction: column;
            align-items: flex-start;
        }

        .detail-item label {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            min-width: 80px;
        }

        .detail-item span {
            color: #7f8c8d;
        }

        .estimated-time-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .time-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .time-content h6 {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }

        .estimated-time {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .realtime-updates-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 25px;
            padding: 8px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .realtime-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
        }

        /* Animations */
        @keyframes pulse-ring {
            0% {
                transform: scale(1);
                opacity: 0.7;
            }
            100% {
                transform: scale(1.4);
                opacity: 0;
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .appointment-status-container {
                padding: 1rem 0;
            }

            .patient-status-display {
                padding: 1.5rem;
                margin: 0 1rem;
            }

            .status-icon {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }

            .status-title {
                font-size: 1.5rem;
            }

            .step-item,
            .timeline-item {
                text-align: left;
            }

            .quick-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .realtime-updates-indicator {
                bottom: 10px;
                right: 10px;
                left: 10px;
                justify-content: center;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize real-time patient status display
            window.PatientStatusDisplay = new PatientStatusDisplay({
                appointmentId: '{{ $appointment->id ?? '' }}',
                userId: '{{ auth()->id() }}',
                userRole: 'patient',
                pusherKey: window.PUSHER_KEY,
                cluster: window.PUSHER_CLUSTER,
                debugMode: window.DEBUG_REALTIME || false
            });
        });

        function cancelAppointment() {
            const modal = new bootstrap.Modal(document.getElementById('cancelAppointmentModal'));
            modal.show();
        }

        // Handle cancel confirmation
        document.getElementById('confirmCancel').addEventListener('click', function() {
            const reason = document.getElementById('cancellationReason').value;

            // Send cancellation request
            fetch('/appointments/{{ $appointment->id ?? "" }}/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    reason: reason,
                    cancelled_by: 'patient'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('cancelAppointmentModal')).hide();
                    window.location.reload();
                } else {
                    alert('Failed to cancel appointment: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                // console.error('Error cancelling appointment:', error);
                alert('Failed to cancel appointment');
            });
        });
    </script>
@endpush
