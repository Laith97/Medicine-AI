@extends('master')

@section('title', 'Appointment Completed')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('doctor.dashboard') }}" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('doctor.appointments.index') }}" class="text-decoration-none">
                        Appointments
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Appointment Completed</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 10px 10px 0 0;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="{{ route('doctor.appointments.index') }}" class="btn btn-light me-3 shadow-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                    </a>
                    <div>
                        <h1 class="h2 mb-1 fw-bold" style="color: white;">
                            <i class="fas fa-check-circle me-2"></i>Appointment Completed Successfully
                        </h1>
                        <p class="mb-0 opacity-75">ID: #{{ $appointment->id }} • {{ $appointment->appointment_date->format('M j, Y \a\t g:i A') }}</p>
                    </div>
                </div>
                <div class="text-end">
                    <div class="bg-white bg-opacity-20 rounded p-3">
                        <div class="h3 mb-0 fw-bold">{{ $appointment->appointment_duration ?? 30 }}</div>
                        <small class="opacity-90">minutes completed</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Success Confirmation -->
                <div class="table-card mb-4 shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
                    <div class="bg-gradient-success text-white p-4" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-check-circle fa-2x text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold">Appointment Successfully Completed</h3>
                                <p class="mb-0 opacity-90">The voice assistant consultation has been completed and all data has been saved.</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <div class="bg-success bg-opacity-15 rounded p-3 me-3">
                                        <i class="fas fa-calendar-check text-success fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">Completion Time</h6>
                                        <span class="badge bg-success">{{ $appointment->updated_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <div class="bg-info bg-opacity-15 rounded p-3 me-3">
                                        <i class="fas fa-microphone text-info fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">Consultation Method</h6>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="bg-gradient-primary text-white p-4 rounded-top" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);">
                        <h4 class="mb-0 fw-bold">
                            <i class="fas fa-forward me-2"></i>Recommended Next Steps
                        </h4>
                        <p class="mb-0 opacity-90 small">Complete these actions to provide comprehensive patient care</p>
                    </div>
                    <div class="p-4">
                        <div class="row g-3">
                            <!-- View AI Predictive Analytics -->
                            <div class="col-md-4">
                                <div class="card border-primary shadow-sm h-100" style="border-radius: 12px; transition: transform 0.2s;">
                                    <div class="card-body text-center p-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-brain text-primary fa-2x"></i>
                                        </div>
                                        <h5 class="card-title text-primary fw-bold">AI Predictive Analytics</h5>
                                        <p class="text-muted small mb-3">Review risk assessments and patient insights</p>
                                        <a href="{{ route('doctor.appointments.show', $appointment) }}#analytics" class="btn btn-primary btn-lg fw-semibold">
                                            <i class="fas fa-chart-line me-2"></i>View Analytics
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Create/Manage Prescriptions -->
                            <div class="col-md-4">
                                <div class="card border-success shadow-sm h-100" style="border-radius: 12px; transition: transform 0.2s;">
                                    <div class="card-body text-center p-4">
                                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-prescription-bottle text-success fa-2x"></i>
                                        </div>
                                        <h5 class="card-title text-success fw-bold">Prescriptions</h5>
                                        <p class="text-muted small mb-3">Create or manage medication prescriptions</p>
                                        <a href="{{ route('doctor.appointments.show', $appointment) }}#prescriptions" class="btn btn-success btn-lg fw-semibold">
                                            <i class="fas fa-plus me-2"></i>Manage Rx
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Follow-up -->
                            <div class="col-md-4">
                                <div class="card border-warning shadow-sm h-100" style="border-radius: 12px; transition: transform 0.2s;">
                                    <div class="card-body text-center p-4">
                                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-calendar-plus text-warning fa-2x"></i>
                                        </div>
                                        <h5 class="card-title text-warning fw-bold">Follow-up Appointment</h5>
                                        <p class="text-muted small mb-3">Schedule next consultation if needed</p>
                                        <button onclick="scheduleFollowUp()" class="btn btn-warning btn-lg fw-semibold">
                                            <i class="fas fa-calendar-check me-2"></i>Schedule
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clinical Data Summary -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="p-4">
                        <h5 class="mb-4 text-primary fw-bold">
                            <i class="fas fa-database me-2"></i>Clinical Data Summary
                        </h5>
                        <div class="row g-3">
                            <!-- Patient Information -->
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="text-primary fw-bold mb-3">
                                        <i class="fas fa-user-injured me-2"></i>Patient Details
                                    </h6>
                                    <div class="mb-2">
                                        <strong>Name:</strong> {{ $appointment->patient_name }}
                                    </div>
                                    <div class="mb-2">
                                        <strong>Email:</strong> {{ $appointment->patient_email }}
                                    </div>
                                    @if($appointment->patient_phone)
                                    <div class="mb-2">
                                        <strong>Phone:</strong> {{ $appointment->patient_phone }}
                                    </div>
                                    @endif
                                    <div class="mb-0">
                                        <strong>Appointment Type:</strong> {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                    </div>
                                </div>
                            </div>

                            <!-- AI Risk Assessment -->
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="text-primary fw-bold mb-3">
                                        <i class="fas fa-brain me-2"></i>AI Risk Assessment
                                    </h6>
                                    @php
                                        $riskScore = $appointment->patient->patientRiskScores->where('appointment_id', $appointment->id)->first();
                                    @endphp
                                    @if($riskScore)
                                        @php
                                            $noShowRisk = $riskScore->no_show_risk;
                                            $hospitalizationRisk = $riskScore->hospitalization_risk;
                                            $maxRisk = max($noShowRisk, $hospitalizationRisk);
                                        @endphp
                                        <div class="mb-2">
                                            <strong>Overall Risk:</strong>
                                            @if($maxRisk < 0.3)
                                                <span class="badge bg-success">Low Risk</span>
                                            @elseif($maxRisk < 0.7)
                                                <span class="badge bg-warning text-dark">Medium Risk</span>
                                            @else
                                                <span class="badge bg-danger">High Risk</span>
                                            @endif
                                        </div>
                                        <div class="mb-2">
                                            <strong>No-show Risk:</strong> {{ number_format($noShowRisk * 100, 1) }}%
                                        </div>
                                        <div class="mb-0">
                                            <strong>Hospitalization Risk:</strong> {{ number_format($hospitalizationRisk * 100, 1) }}%
                                        </div>
                                    @else
                                        <div class="text-muted">Risk assessment not available</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Appointment Details -->
                            <div class="col-md-12">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="text-primary fw-bold mb-3">
                                        <i class="fas fa-clipboard-list me-2"></i>Consultation Summary
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>Reason for Visit:</strong>
                                            </div>
                                            <p class="text-muted mb-3">{{ $appointment->reason }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            @if($appointment->doctor_notes)
                                            <div class="mb-2">
                                                <strong>Doctor's Notes:</strong>
                                            </div>
                                            <p class="text-muted mb-0">{{ $appointment->doctor_notes }}</p>
                                            @else
                                            <div class="text-muted">No additional notes recorded</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnosis Details -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="p-4">
                        <h5 class="mb-4 text-primary fw-bold">
                            <i class="fas fa-stethoscope me-2"></i>Diagnosis & Treatment Summary
                        </h5>

                        @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                            <div class="mb-4">
                                <h6 class="text-success fw-bold mb-3">
                                    <i class="fas fa-prescription-bottle me-2"></i>Prescriptions Issued ({{ $appointment->prescriptions->count() }})
                                </h6>
                                <div class="row g-3">
                                    @foreach($appointment->prescriptions as $prescription)
                                    <div class="col-md-6">
                                        <div class="card border-success" style="border-radius: 10px;">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-success fw-bold">{{ $prescription->medication_name }}</h6>
                                                <div class="small text-muted mb-2">
                                                    <strong>Dosage:</strong> {{ $prescription->dosage }} •
                                                    <strong>Frequency:</strong> {{ $prescription->frequency }}
                                                </div>
                                                <div class="small text-muted">
                                                    <strong>Duration:</strong> {{ $prescription->duration }} •
                                                    <strong>Quantity:</strong> {{ $prescription->quantity }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>No prescriptions were issued</strong> during this consultation.
                            </div>
                        @endif

                        <!-- Follow-up Recommendation -->
                        @if($appointment->follow_up_required)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Follow-up Recommended:</strong> This patient has been flagged for follow-up care.
                            Consider scheduling a follow-up appointment within the next 1-2 weeks.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="bg-gradient-primary text-white p-4 rounded-top" style="background: linear-gradient(135deg, #DE6262 0%, #c54545 100%);">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="d-grid gap-3">
                            <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-primary btn-lg fw-semibold shadow-sm">
                                <i class="fas fa-eye me-2"></i>View Full Details
                            </a>

                            <a href="{{ route('doctor.appointments.index') }}" class="btn btn-secondary btn-lg fw-semibold shadow-sm">
                                <i class="fas fa-list me-2"></i>All Appointments
                            </a>

                            <button onclick="printSummary()" class="btn btn-info btn-lg fw-semibold shadow-sm">
                                <i class="fas fa-print me-2"></i>Print Summary
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Appointment Timeline -->
                <div class="table-card shadow-sm">
                    <div class="p-4">
                        <h5 class="mb-4 text-primary fw-bold">
                            <i class="fas fa-history me-2"></i>Appointment Timeline
                        </h5>
                        <div class="timeline position-relative">
                            <div class="timeline-item d-flex mb-4">
                                <div class="timeline-marker bg-primary rounded-circle shadow-sm me-3" style="width: 16px; height: 16px; margin-top: 6px;"></div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Appointment Booked</h6>
                                    <small class="text-muted">{{ $appointment->created_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                            </div>

                            <div class="timeline-item d-flex mb-4">
                                <div class="timeline-marker bg-success rounded-circle shadow-sm me-3" style="width: 16px; height: 16px; margin-top: 6px;"></div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Appointment Completed</h6>
                                    <small class="text-muted">{{ $appointment->updated_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                            </div>

                            @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                            <div class="timeline-item d-flex">
                                <div class="timeline-marker bg-info rounded-circle shadow-sm me-3" style="width: 16px; height: 16px; margin-top: 6px;"></div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Prescriptions Issued</h6>
                                    <small class="text-muted">{{ $appointment->prescriptions->count() }} prescription(s) created</small>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Follow-up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="followUpModalLabel">
                    <i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up Appointment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followUpForm" method="POST" action="{{ route('doctor.appointments.store') }}">
                @csrf
                <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                <input type="hidden" name="is_follow_up" value="1">
                <input type="hidden" name="parent_appointment_id" value="{{ $appointment->id }}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Follow-up for:</strong> {{ $appointment->patient_name }}
                    </div>

                    <div class="mb-3">
                        <label for="appointment_date" class="form-label fw-semibold">Follow-up Date & Time</label>
                        <input type="datetime-local" class="form-control" id="appointment_date" name="appointment_date"
                               min="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="appointment_type" class="form-label fw-semibold">Appointment Type</label>
                        <select class="form-select" id="appointment_type" name="appointment_type" required>
                            <option value="video_call">Video Call</option>
                            <option value="phone_call">Phone Call</option>
                            <option value="in_person">In Person</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label fw-semibold">Reason for Follow-up</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"
                                  placeholder="Brief description of follow-up purpose..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-semibold">Schedule Follow-up</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function scheduleFollowUp() {
    const modal = new bootstrap.Modal(document.getElementById('followUpModal'));
    modal.show();
}

function printSummary() {
    window.print();
}

// Add hover effects to action cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<style>
@media print {
    .btn, .modal, nav, .dashboard-header .btn {
        display: none !important;
    }
    .table-card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 23px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -30px;
}
</style>
@endsection