@extends('master')

@section('title', 'Appointment Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
.copilot-tab {
    cursor: pointer;
    padding: 0.75rem 1.5rem;
    border: none;
    background-color: transparent;
    color: #6c757d;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.copilot-tab.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    font-weight: 500;
}

.copilot-tab-content {
    display: none;
    padding: 1.5rem 0;
}

.copilot-tab-content.active {
    display: block;
}

.copilot-section {
    margin-bottom: 2rem;
}

.copilot-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.copilot-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.copilot-badge {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.copilot-content {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.25rem;
    border-left: 4px solid #0d6efd;
}

.copilot-list {
    list-style-type: none;
    padding-left: 0;
}

.copilot-list li {
    padding: 0.5rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.copilot-list li:before {
    content: "•";
    color: #0d6efd;
    position: absolute;
    left: 0;
    font-weight: bold;
}

.copilot-warning {
    background-color: #fff3cd;
    border-left-color: #ffc107;
}

.copilot-danger {
    background-color: #f8d7da;
    border-left-color: #dc3545;
}

.copilot-success {
    background-color: #d1e7dd;
    border-left-color: #198754;
}

.copilot-info {
    background-color: #cff4fc;
    border-left-color: #0dcaf0;
}

.copilot-disclaimer {
    font-size: 0.875rem;
    color: #6c757d;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-top: 1rem;
    border: 1px solid #e9ecef;
}

.copilot-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.copilot-checkbox {
    margin-right: 0.5rem;
}

.edit-copilot-btn {
    cursor: pointer;
    color: #0d6efd;
    font-size: 0.875rem;
}

.edit-copilot-btn:hover {
    text-decoration: underline;
}

.copilot-loading {
    display: none;
    text-align: center;
    padding: 2rem;
}

.copilot-loading.active {
    display: block;
}

.copilot-loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 0.25rem solid #f3f3f3;
    border-top: 0.25rem solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.copilot-error {
    color: #dc3545;
    background-color: #f8d7da;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
    border: 1px solid #f5c2c7;
}

.copilot-compliance-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
    margin-top: 1rem;
    font-style: italic;
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container appointment-details">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <a href="{{ route('doctor.appointments.index') }}" class="btn btn-light me-3 shadow-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                    </a>
                    <div>
                        <h1 class="h2 mb-1 fw-bold">Appointment Details</h1>
                        <p class="mb-0 opacity-75">ID: #{{ $appointment->id }} • {{ $appointment->appointment_date->format('M j, Y \a\t g:i A') }}</p>
                    </div>
                </div>

                <div class="text-end">
                    <div class="d-flex flex-column align-items-end gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <!-- Quick Action Buttons -->
                            @if(auth()->check() && auth()->user()->isDoctor())
                                <div class="btn-group" role="group">
                                    @if($appointment->status == 'pending')
                                        <button onclick="confirmAppointment({{ $appointment->id }})" class="btn btn-success btn-sm" title="Confirm Appointment">
                                            <i class="fas fa-check me-1"></i>Confirm
                                        </button>
                                    @endif

                                    @if($appointment->status == 'confirmed')
                                        @if(!$appointment->appointment_date || !$appointment->appointment_date->isFuture())
                                            <button onclick="completeAppointment({{ $appointment->id }})" class="btn btn-primary btn-sm" title="Complete Appointment">
                                                <i class="fas fa-check-circle me-1"></i>Complete
                                            </button>
                                            <button onclick="markNoShow({{ $appointment->id }})" class="btn btn-secondary btn-sm" title="Mark as No Show">
                                                <i class="fas fa-user-times me-1"></i>No Show
                                            </button>
                                        @else
                                            <span class="btn btn-primary btn-sm" title="Video call available when consultation starts">
                                                <i class="fas fa-video me-1"></i>Video consultation available
                                            </span>
                                        @endif
                                    @endif

                                    @if(in_array($appointment->status, ['pending', 'confirmed']))
                                        <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-danger btn-sm" title="Cancel Appointment">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    @endif
                                </div>
                            @endif
                            
                            <span class="status-badge status-{{ $appointment->status }}">
                                <i class="fas fa-{{ $appointment->status == 'pending' ? 'clock' : ($appointment->status == 'confirmed' ? 'check-circle' : ($appointment->status == 'completed' ? 'check-double' : ($appointment->status == 'cancelled' ? 'times-circle' : 'user-times'))) }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                            </span>
                        </div>
                        @if($appointment->status == 'completed')
                        <div class="bg-success bg-opacity-25 px-3 py-1 rounded-pill">
                            <small class="text-white fw-semibold">
                                <i class="fas fa-trophy me-1"></i>Successfully Completed
                            </small>
                        </div>
                        @endif
                        <small class="text-white-50">
                            <i class="fas fa-calendar-alt me-1"></i>{{ $appointment->appointment_date->format('l, F j, Y') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-12">
                <!-- Information Cards Grid -->
                <div class="info-cards-grid">
                    <!-- Call/Video Buttons -->
                @if($appointment->status === 'confirmed')
                    @include('components.appointment-call-buttons', ['appointment' => $appointment])
                @endif

                <!-- Appointment Overview Card -->
                    <div class="table-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1 fw-bold text-primary">
                                    <i class="fas fa-calendar-check me-2"></i>Appointment Overview
                                </h5>
                                <small class="text-muted">{{ $appointment->appointment_date->format('l, F j, Y') }}</small>
                            </div>
                            <div class="text-end">
                                <div class="h4 mb-0 fw-bold text-primary">{{ $appointment->appointment_duration ?? 30 }}</div>
                                <small class="text-muted">minutes</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-15 rounded p-2 me-3">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Type</small>
                                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information Card -->
                    <div class="table-card">
                        <h5 class="mb-3 fw-bold text-primary">
                            <i class="fas fa-user-injured me-2"></i>Patient Information
                        </h5>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <span class="fw-semibold">{{ e($appointment->patient_name) }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-envelope text-muted me-2"></i>
                                    <span>{{ e($appointment->patient_email) }}</span>
                                </div>
                            </div>
                            @if($appointment->patient_phone)
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    <span>{{ e($appointment->patient_phone) }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Next Steps Section for Completed Appointments -->
                @if($appointment->status == 'completed')
                <div class="table-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-success">
                                <i class="fas fa-check-double me-2"></i>Appointment Completed Successfully
                            </h4>
                            <p class="mb-0 text-muted">What would you like to do next?</p>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-rocket fa-3x text-success opacity-75"></i>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <button onclick="toggleAIMedicalCopilotForm()" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" style="min-height: 120px;">
                                <i class="fas fa-brain fa-2x mb-2 text-primary"></i>
                                AI Copilot
                                <small class="text-muted">Clinical Decision Support</small>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button onclick="viewPatientAIAnalyses({{ $appointment->patient_id }})" class="btn btn-outline-info btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" style="min-height: 120px;">
                                <i class="fas fa-history fa-2x mb-2 text-info"></i>
                                View AI History
                                <small class="text-muted">Patient's Saved Analyses</small>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="#ai-analytics" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4" style="text-decoration: none; min-height: 120px;">
                                <i class="fas fa-brain fa-2x mb-2 text-primary"></i>
                                <span class="fw-bold">AI Analytics</span>
                                <small class="text-muted">View risk predictions & insights</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button onclick="toggleDiagnosisForm()" class="btn btn-outline-warning btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4" style="text-decoration: none; min-height: 120px;">
                                <i class="fas fa-stethoscope fa-2x mb-2 text-warning"></i>
                                <span class="fw-bold">Diagnosis</span>
                                <small class="text-muted">Create medical diagnosis</small>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="#prescriptions" class="btn btn-outline-success btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4" style="text-decoration: none; min-height: 120px;">
                                <i class="fas fa-prescription-bottle fa-2x mb-2 text-success"></i>
                                <span class="fw-bold">Prescriptions</span>
                                <small class="text-muted">Manage medications</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('doctor.follow-ups.create', $appointment) }}" class="btn btn-outline-info btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4" style="text-decoration: none; min-height: 120px;">
                                <i class="fas fa-calendar-plus fa-2x mb-2 text-info"></i>
                                <span class="fw-bold">Follow-ups</span>
                                <small class="text-muted">Schedule next appointment</small>
                            </a>
                        </div>
                    </div>
                </div>
                @endif


                <!-- AI Predictive Analytics Section -->
                <div id="ai-analytics" class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-brain me-2"></i>AI Predictive Analytics
                            </h4>
                            <p class="mb-0 text-muted small">Machine Learning Risk Assessment</p>
                        </div>
                        @if($appointment->status == 'completed')
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Analysis Complete
                        </span>
                        @endif
                    </div>

                    @php
                        $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id', $appointment->id)?->first();
                    @endphp
                    @if($riskScore)
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="text-center p-4 bg-light rounded">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-user-times text-warning fa-2x"></i>
                                    </div>
                                    <h5 class="text-warning fw-bold mb-2">No-Show Risk</h5>
                                    <div class="h2 fw-bold text-warning mb-2">{{ number_format($riskScore->no_show_risk * 100, 1) }}<span class="h4">%</span></div>
                                    <p class="text-muted small mb-0">Probability of patient missing appointment</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center p-4 bg-light rounded">
                                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-hospital text-danger fa-2x"></i>
                                    </div>
                                    <h5 class="text-danger fw-bold mb-2">Hospitalization Risk</h5>
                                    <div class="h2 fw-bold text-danger mb-2">{{ number_format($riskScore->hospitalization_risk * 100, 1) }}<span class="h4">%</span></div>
                                    <p class="text-muted small mb-0">Probability of requiring hospitalization</p>
                                </div>
                            </div>
                        </div>

                        <!-- Risk Level Summary -->
                        <div class="bg-light p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    @php
                                        $maxRisk = max($riskScore->no_show_risk, $riskScore->hospitalization_risk);
                                    @endphp
                                    @if($maxRisk < 0.3)
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fas fa-shield-alt me-1"></i>Low Risk Patient
                                        </span>
                                        <small class="text-muted d-block mt-1">Strong compliance patterns detected</small>
                                    @elseif($maxRisk < 0.7)
                                        <span class="badge bg-warning fs-6 px-3 py-2 text-dark">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Medium Risk Patient
                                        </span>
                                        <small class="text-muted d-block mt-1">Consider follow-up reminders</small>
                                    @else
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="fas fa-exclamation-circle me-1"></i>High Risk Patient
                                        </span>
                                        <small class="text-muted d-block mt-1">Immediate attention recommended</small>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mlExplanationModal">
                                    <i class="fas fa-info-circle me-1"></i>How is this calculated?
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-brain text-info fa-2x"></i>
                            </div>
                            <h5 class="text-muted mb-2">AI Analysis in Progress</h5>
                            <p class="text-muted">Risk predictions are being calculated...</p>
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Reason for Visit -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="p-4">
                        <h5 class="mb-4 text-primary fw-bold">
                            <i class="fas fa-clipboard-list me-2"></i>Reason for Visit
                        </h5>
                        <div class="bg-light p-4 rounded" style="border-left: 4px solid #007bff;">
                            <p class="mb-0 fs-6 lh-base">{{ e($appointment->reason) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="prescriptions" class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-prescription-bottle me-2"></i>Prescriptions
                            </h4>
                            @if($appointment->status == 'completed')
                            <p class="mb-0 text-muted small">Manage patient medications and treatments</p>
                            @endif
                        </div>
                        @if($appointment->status == 'completed')
                        <span class="badge bg-success">
                            <i class="fas fa-plus-circle me-1"></i>Ready to Prescribe
                        </span>
                        @endif
                    </div>

                    @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                        <h5 class="section-header">Existing Prescriptions</h5>
                        @foreach($appointment->prescriptions as $prescription)
                            <div class="bg-light p-3 rounded mb-3" data-prescription-id="{{ $prescription->id }}">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="mb-0 fw-bold">{{ $prescription->medication_name }}</h6>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('prescriptions.show', $prescription->id) }}?pdf=1" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download me-1"></i>PDF
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deletePrescription({{ $prescription->id }}, '{{ $prescription->medication_name }}')">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2 text-small">
                                    <div class="col-md-3">
                                        <strong>Dosage:</strong><br>
                                        <span class="text-muted">{{ $prescription->dosage }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Form:</strong><br>
                                        <span class="text-muted">{{ ucfirst($prescription->form ?? 'N/A') }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Frequency:</strong><br>
                                        <span class="text-muted">{{ $prescription->frequency }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Duration:</strong><br>
                                        <span class="text-muted">{{ $prescription->duration }}</span>
                                    </div>
                                </div>
                                @if($prescription->instructions)
                                    <hr class="my-2">
                                    <p class="mb-0 text-muted small"><strong>Instructions:</strong> {{ $prescription->instructions }}</p>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-prescription-bottle-alt text-muted mb-3 fa-3x"></i>
                            <p class="text-muted">No prescriptions have been added for this appointment yet.</p>
                        </div>
                    @endif

                    <!-- Prescription Workflow Header -->
                    <div class="prescription-workflow">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="fas fa-prescription-bottle me-2"></i>Add New Prescription
                            </h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#prescriptionHelpModal">
                                    <i class="fas fa-question-circle me-1"></i>How to Use
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#aiDataSourcesModal">
                                    <i class="fas fa-database me-1"></i>What Data Does AI Use?
                                </button>
                            </div>
                        </div>

                        <!-- Quick Workflow Selector -->
                        <div class="workflow-buttons">
                            <button type="button" class="workflow-btn active" data-workflow="manual">
                                <i class="fas fa-user-md me-1"></i>Manual Entry
                            </button>
                            <button type="button" class="workflow-btn" data-workflow="ai-first">
                                <i class="fas fa-brain me-1"></i>AI First
                            </button>
                            <button type="button" class="workflow-btn" data-workflow="ai-assisted">
                                <i class="fas fa-handshake me-1"></i>AI Assisted
                            </button>
                            <button type="button" class="workflow-btn" data-workflow="explore">
                                <i class="fas fa-search me-1"></i>Explore AI
                            </button>
                        </div>

                        <!-- Workflow Description -->
                        <div id="workflow-description" class="mt-3 small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <span id="workflow-text">Manual Entry: Fill the form directly with your prescription details.</span>
                        </div>
                    </div>

                        <form id="prescriptionForm" method="POST" action="{{ route('doctor.prescriptions.store', $appointment->id) }}">
                            @csrf

                            <!-- Essential Information Section -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-pills me-2"></i>Medication Details
                                    </h6>
                                    <span class="form-section-badge bg-danger">Required</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="medication_name" class="form-label fw-semibold">
                                            Medication Name <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Enter the exact medication name as it appears on the drug label"></i>
                                        </label>
                                        <input type="text" class="form-control" id="medication_name" name="medication_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dosage" class="form-label fw-semibold">
                                            Dosage <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="e.g., 500mg, 10mg/ml, 0.5% cream"></i>
                                        </label>
                                        <input type="text" class="form-control" id="dosage" name="dosage" placeholder="e.g., 500mg" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="form" class="form-label fw-semibold">
                                            Form <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Physical form of the medication"></i>
                                        </label>
                                        <select class="form-select" id="form" name="form" required>
                                            <option value="">Select form</option>
                                            <option value="tablet">Tablet</option>
                                            <option value="capsule">Capsule</option>
                                            <option value="liquid">Liquid/Syrup</option>
                                            <option value="injection">Injection</option>
                                            <option value="cream">Cream/Ointment</option>
                                            <option value="inhaler">Inhaler</option>
                                            <option value="patch">Patch</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="route" class="form-label fw-semibold">
                                            Route <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="How the medication is administered"></i>
                                        </label>
                                        <select class="form-select" id="route" name="route" required>
                                            <option value="">Select route</option>
                                            <option value="oral">Oral (by mouth)</option>
                                            <option value="topical">Topical (skin)</option>
                                            <option value="intravenous">Intravenous</option>
                                            <option value="intramuscular">Intramuscular</option>
                                            <option value="subcutaneous">Subcutaneous</option>
                                            <option value="inhalation">Inhalation</option>
                                            <option value="rectal">Rectal</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity" class="form-label fw-semibold">
                                            Quantity <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Total number of units to dispense"></i>
                                        </label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" placeholder="e.g., 30" min="1" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Administration Section -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-clock me-2"></i>Administration Schedule
                                    </h6>
                                    <span class="form-section-badge bg-danger">Required</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="frequency" class="form-label fw-semibold">
                                            Frequency <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="How often the medication should be taken"></i>
                                        </label>
                                        <select class="form-select" id="frequency" name="frequency" required>
                                            <option value="">Select frequency</option>
                                            <option value="once daily">Once daily</option>
                                            <option value="twice daily">Twice daily</option>
                                            <option value="three times daily">Three times daily</option>
                                            <option value="four times daily">Four times daily</option>
                                            <option value="every 6 hours">Every 6 hours</option>
                                            <option value="every 8 hours">Every 8 hours</option>
                                            <option value="every 12 hours">Every 12 hours</option>
                                            <option value="as needed">As needed (PRN)</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="duration" class="form-label fw-semibold">
                                            Duration <span class="text-danger">*</span>
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="How long the medication should be taken"></i>
                                        </label>
                                        <select class="form-select" id="duration" name="duration" required>
                                            <option value="">Select duration</option>
                                            <option value="3 days">3 days</option>
                                            <option value="7 days">7 days</option>
                                            <option value="10 days">10 days</option>
                                            <option value="14 days">14 days</option>
                                            <option value="1 month">1 month</option>
                                            <option value="2 months">2 months</option>
                                            <option value="3 months">3 months</option>
                                            <option value="6 months">6 months</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- AI Clinical Support Section - Enhanced for completed appointments -->
                            @if(config('ai.prescription_suggestions.enabled', true))
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <h6 class="form-section-title">
                                            <i class="fas fa-brain me-2"></i>AI Clinical Support
                                        </h6>
                                        <span class="form-section-badge bg-warning text-dark">Optional</span>
                                    </div>
                                    @if($appointment->status == 'completed')
                                    <small class="text-muted d-block mb-3">
                                        <i class="fas fa-lightbulb text-warning me-1"></i>AI can suggest medications based on appointment data
                                    </small>
                                    @endif
                                    @include('ai.prescription_suggestion')

                                </div>
                            @endif

                            <!-- Additional Options Section -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-cogs me-2"></i>Additional Options
                                    </h6>
                                    <span class="form-section-badge bg-info">Optional</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="refills" class="form-label fw-semibold">
                                            Refills
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Number of times the prescription can be refilled"></i>
                                        </label>
                                        <input type="number" class="form-control" id="refills" name="refills" placeholder="0" min="0" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="start_date" class="form-label fw-semibold">
                                            Start Date
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="When the medication should begin (leave empty for immediate)"></i>
                                        </label>
                                        <input type="date" class="form-control" id="start_date" name="start_date">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="indication" class="form-label fw-semibold">
                                            Indication
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Medical condition being treated"></i>
                                        </label>
                                        <input type="text" class="form-control" id="indication" name="indication" placeholder="e.g., Hypertension">
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="generic_allowed" name="generic_allowed" value="1" checked>
                                            <label class="form-check-label fw-semibold" for="generic_allowed">
                                                <i class="fas fa-info-circle text-muted me-1" data-bs-toggle="tooltip" title="Allow pharmacist to substitute with generic equivalent"></i>
                                                Allow generic substitution
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructions & Notes Section -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-sticky-note me-2"></i>Instructions & Notes
                                    </h6>
                                    <span class="form-section-badge bg-info">Recommended</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="instructions" class="form-label fw-semibold">
                                            Specific Instructions
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Patient-specific directions (e.g., take with food, timing)"></i>
                                        </label>
                                        <textarea class="form-control" id="instructions" name="instructions" rows="2" placeholder="e.g., Take with food, avoid alcohol, take at bedtime"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notes" class="form-label fw-semibold">
                                            Additional Notes
                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Clinical notes, monitoring requirements, or special considerations"></i>
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional instructions or special considerations..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-3 justify-content-between align-items-center mt-4 pt-3 border-top">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary-custom btn-lg fw-semibold">
                                        <i class="fas fa-save me-2"></i>Save Prescription
                                    </button>
                                    <button type="button" class="btn btn-secondary-custom fw-semibold" onclick="resetPrescriptionForm()">
                                        <i class="fas fa-undo me-2"></i>Reset Form
                                    </button>
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    All prescriptions require clinical review and approval
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Diagnosis Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="diagnosis-section" class="table-card" style="@if($errors->has('diagnosis_text') || $errors->has('voice_files') || $errors->any()) display: block; @else display: none; @endif">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 fw-bold text-warning">
                                <i class="fas fa-stethoscope me-2"></i>Create Diagnosis
                            </h4>
                            <p class="mb-0 text-muted small">Document medical findings and diagnosis for this appointment</p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleDiagnosisForm()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>

                    <!-- Show validation errors if any -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Context Information -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Appointment Context:</strong> {{ $appointment->patient_name }} - {{ $appointment->reason }}
                        @if($appointment->doctor_notes)
                        <br><small class="text-muted"><strong>Doctor Notes:</strong> {{ Str::limit($appointment->doctor_notes, 100) }}</small>
                        @endif
                    </div>

                    <form id="diagnosisForm" method="POST" action="{{ route('doctor.appointments.create-diagnosis', $appointment) }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Diagnosis Input Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title">
                                    <i class="fas fa-stethoscope me-2"></i>Diagnosis Details
                                </h6>
                                <span class="form-section-badge bg-warning text-dark">Required</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="diagnosis_text" class="form-label fw-semibold">
                                        Diagnosis Text <span class="text-danger">*</span>
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Enter your medical diagnosis, findings, and treatment plan"></i>
                                    </label>
                                    <textarea class="form-control" id="diagnosis_text" name="diagnosis_text" rows="6" placeholder="Enter your medical diagnosis, clinical findings, and treatment recommendations..." required></textarea>
                                    <div class="form-text">
                                        Include symptoms assessment, clinical findings, diagnosis, and treatment recommendations.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Voice Recording Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title">
                                    <i class="fas fa-microphone me-2"></i>Voice Recording (Optional)
                                </h6>
                                <span class="form-section-badge bg-info">Optional</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="voice-recording-container">
                                        <button type="button" id="startRecording" class="btn btn-outline-primary">
                                            <i class="fas fa-microphone me-2"></i>Start Voice Recording
                                        </button>
                                        <button type="button" id="stopRecording" class="btn btn-outline-danger" style="display: none;">
                                            <i class="fas fa-stop me-2"></i>Stop Recording
                                        </button>
                                        <button type="button" id="playRecording" class="btn btn-outline-success" style="display: none;">
                                            <i class="fas fa-play me-2"></i>Play Back
                                        </button>
                                        <span id="recordingStatus" class="ms-3 text-muted"></span>
                                        <audio id="audioPlayback" controls style="display: none; max-width: 300px;"></audio>
                                    </div>
                                    <input type="file" id="voice_files" name="voice_files[]" multiple accept="audio/*" style="display: none;">
                                    <div class="form-text">
                                        Alternatively, you can upload audio files directly.
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="document.getElementById('voice_files').click()">
                                            Upload Files
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Data Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title">
                                    <i class="fas fa-user-md me-2"></i>Additional Patient Information
                                </h6>
                                <span class="form-section-badge bg-info">Optional</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="patient_data_height" class="form-label fw-semibold">
                                        Height (cm)
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Patient's height in centimeters"></i>
                                    </label>
                                    <input type="number" class="form-control" id="patient_data_height" name="patient_data[height]" placeholder="170">
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_data_weight" class="form-label fw-semibold">
                                        Weight (kg)
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Patient's weight in kilograms"></i>
                                    </label>
                                    <input type="number" step="0.1" class="form-control" id="patient_data_weight" name="patient_data[weight]" placeholder="70.5">
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_data_blood_pressure" class="form-label fw-semibold">
                                        Blood Pressure
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Systolic/Diastolic (e.g., 120/80)"></i>
                                    </label>
                                    <input type="text" class="form-control" id="patient_data_blood_pressure" name="patient_data[blood_pressure]" placeholder="120/80">
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_data_temperature" class="form-label fw-semibold">
                                        Temperature (°C)
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Body temperature in Celsius"></i>
                                    </label>
                                    <input type="number" step="0.1" class="form-control" id="patient_data_temperature" name="patient_data[temperature]" placeholder="36.6">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning btn-lg fw-semibold" onclick="submitDiagnosisForm()">
                                    <i class="fas fa-save me-2"></i>Create Diagnosis
                                </button>
                                <button type="button" class="btn btn-secondary fw-semibold" onclick="toggleDiagnosisForm()">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-shield-alt me-1"></i>
                                Diagnosis will be saved and patient will be notified
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- AI Medical Copilot Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="ai-medical-copilot-section" class="table-card" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-brain me-2"></i>AI Medical Copilot
                            </h4>
                            <p class="mb-0 text-muted small">AI-powered clinical decision support for this appointment</p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAIMedicalCopilotForm()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div class="copilot-loading" id="copilotLoadingSection">
                        <div class="copilot-loading-spinner mx-auto"></div>
                        <h5 class="text-primary text-center">AI Medical Copilot is analyzing...</h5>
                        <p class="text-muted text-center">Processing clinical data and generating decision support insights</p>
                    </div>

                    <!-- Error State -->
                    <div class="copilot-error alert alert-danger" id="copilotErrorSection" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="copilotErrorMessageSection"></span>
                    </div>

                    <!-- Content Area -->
                    <div id="copilotContentSection" style="display: none;">
                        <!-- Tab Navigation -->
                        <div class="d-flex justify-content-start mb-3 border-bottom">
                            <button class="copilot-tab active" data-tab="summary">
                                <i class="fas fa-file-medical me-1"></i>Summary
                            </button>
                            <button class="copilot-tab" data-tab="considerations">
                                <i class="fas fa-list-check me-1"></i>Considerations
                            </button>
                            <button class="copilot-tab" data-tab="questions">
                                <i class="fas fa-question-circle me-1"></i>Questions
                            </button>
                            <button class="copilot-tab" data-tab="red-flags">
                                <i class="fas fa-flag me-1"></i>Red Flags
                            </button>
                            <button class="copilot-tab" data-tab="history">
                                <i class="fas fa-history me-1"></i>Patient History
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div id="copilotTabsSection">
                            <!-- Summary Tab -->
                            <div class="copilot-tab-content active" data-tab-content="summary">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-file-medical me-2"></i>Medical Case Summary
                                        </h6>
                                        <span class="badge copilot-badge bg-primary">
                                            <i class="fas fa-check-circle me-1"></i>AI-Generated
                                        </span>
                                    </div>
                                    <div class="copilot-content" id="copilotSummarySection">
                                        <p class="text-muted">Loading medical case summary...</p>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeSummaryInNoteSection">
                                        <label class="form-check-label" for="includeSummaryInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Considerations Tab -->
                            <div class="copilot-tab-content" data-tab-content="considerations">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-list-check me-2"></i>Differential Considerations
                                        </h6>
                                        <span class="badge copilot-badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Not Diagnoses
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-warning" id="copilotConsiderationsSection">
                                        <p class="text-muted">Loading differential considerations...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>⚠️ For clinical consideration only. Physician judgment required.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeConsiderationsInNoteSection">
                                        <label class="form-check-label" for="includeConsiderationsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions Tab -->
                            <div class="copilot-tab-content" data-tab-content="questions">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-question-circle me-2"></i>Suggested Follow-up Questions
                                        </h6>
                                        <span class="badge copilot-badge bg-info">
                                            <i class="fas fa-lightbulb me-1"></i>Clinical Insights
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-info" id="copilotQuestionsSection">
                                        <p class="text-muted">Loading follow-up questions...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>💡 These questions help raise diagnostic quality and reduce oversight.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeQuestionsInNoteSection">
                                        <label class="form-check-label" for="includeQuestionsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Red Flags Tab -->
                            <div class="copilot-tab-content" data-tab-content="red-flags">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-flag me-2"></i>Red Flags Detection
                                        </h6>
                                        <span class="badge copilot-badge bg-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>Urgent Attention
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-danger" id="copilotRedFlagsSection">
                                        <p class="text-muted">Loading red flags analysis...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>⚠️ Consider urgent evaluation if clinically indicated.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeRedFlagsInNoteSection">
                                        <label class="form-check-label" for="includeRedFlagsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Patient History Tab -->
                            <div class="copilot-tab-content" data-tab-content="history">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-history me-2"></i>Patient Medical History
                                        </h6>
                                        <span class="badge copilot-badge bg-info">
                                            <i class="fas fa-database me-1"></i>Historical Data
                                        </span>
                                    </div>
                                    <div class="copilot-content" id="copilotHistorySection">
                                        <p class="text-muted">Loading patient history...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>📋 Patient history was used in AI analysis to provide context-aware recommendations.</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compliance Information -->
                        <div class="copilot-compliance-label">
                            <i class="fas fa-shield-alt me-1"></i>
                            <span id="copilotComplianceLabelSection">AI-generated draft. Physician verified.</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="saveCopilotAnalysisSection">
                                    <i class="fas fa-save me-2"></i>Save Analysis
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleAIMedicalCopilotForm()">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-shield-alt me-1"></i>
                                AI analysis will be saved and available for review
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>


        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <form id="cancelForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Reason for cancellation (optional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                <button type="button" class="btn btn-danger" onclick="submitCancellation()">Cancel Appointment</button>
            </div>
        </div>
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
                    <button type="submit" class="btn btn-primary">Complete Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Prescription Modal -->
<div class="modal fade" id="deletePrescriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the prescription for <strong id="deletePrescriptionName"></strong>?</p>
                <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeletePrescription()">Delete Prescription</button>
            </div>
        </div>
    </div>
</div>

<!-- Prescription Help Modal -->
<div class="modal fade" id="prescriptionHelpModal" tabindex="-1" aria-labelledby="prescriptionHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="prescriptionHelpModalLabel">
                    <i class="fas fa-prescription-bottle me-2"></i>How to Use the Prescription Feature
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Overview:</strong> This feature allows you to create medication prescriptions for patients. You can work manually or use AI assistance for clinical decision support.
                </div>

                <h6 class="text-success mb-3"><i class="fas fa-list-ol me-2"></i>Four Ways to Create Prescriptions:</h6>

                <!-- Scenario 1 -->
                <div class="card border-success mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-user-md me-2"></i>Scenario 1: Manual Entry (For Experienced Doctors)</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>When to use:</strong> When you already know exactly what medication to prescribe.</p>
                        <div class="bg-light p-3 rounded">
                            <strong>Steps:</strong>
                            <ol class="mb-0">
                                <li>Fill out the prescription form manually with medication details</li>
                                <li><strong>Do NOT press the AI button</strong></li>
                                <li>Click "Save Prescription"</li>
                            </ol>
                        </div>
                        <small class="text-muted">Example: Prescribing regular blood pressure medication for a known patient.</small>
                    </div>
                </div>

                <!-- Scenario 2 -->
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-brain me-2"></i>Scenario 2: AI-First Approach (For Complex Cases)</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>When to use:</strong> When you need AI suggestions before filling any form fields.</p>
                        <div class="bg-light p-3 rounded">
                            <strong>Steps:</strong>
                            <ol class="mb-0">
                                <li><strong>Click "AI Clinical Support" button first</strong> (form can be empty)</li>
                                <li>AI analyzes patient data and shows medication suggestions</li>
                                <li>Review suggestions and click "Use Suggestion" to auto-fill the form</li>
                                <li>Modify the auto-filled form if needed</li>
                                <li>Click "Save Prescription"</li>
                            </ol>
                        </div>
                        <small class="text-muted">Example: Patient with "severe headache, nausea, light sensitivity" - AI suggests migraine treatment.</small>
                    </div>
                </div>

                <!-- Scenario 3 -->
                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-handshake me-2"></i>Scenario 3: AI-Assisted Entry (For Guidance)</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>When to use:</strong> When you start manually but want AI to check for issues.</p>
                        <div class="bg-light p-3 rounded">
                            <strong>Steps:</strong>
                            <ol class="mb-0">
                                <li>Fill some fields in the prescription form manually</li>
                                <li><strong>Click "AI Clinical Support" button</strong></li>
                                <li>AI provides suggestions, warnings, or alternative options</li>
                                <li>Accept AI suggestions to modify your form, or continue manually</li>
                                <li>Click "Save Prescription"</li>
                            </ol>
                        </div>
                        <small class="text-muted">Example: You enter "Amoxicillin" and AI warns about penicillin allergy risk.</small>
                    </div>
                </div>

                <!-- Scenario 4 -->
                <div class="card border-warning mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-search me-2"></i>Scenario 4: AI Exploration (Research Only)</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>When to use:</strong> When you want to see AI suggestions but plan to prescribe differently.</p>
                        <div class="bg-light p-3 rounded">
                            <strong>Steps:</strong>
                            <ol class="mb-0">
                                <li><strong>Click "AI Clinical Support" button</strong></li>
                                <li>Review AI suggestions for educational purposes</li>
                                <li><strong>Click "Dismiss" on all suggestions</strong></li>
                                <li>Fill the prescription form manually with your chosen medication</li>
                                <li>Click "Save Prescription"</li>
                            </ol>
                        </div>
                        <small class="text-muted">Example: AI suggests antibiotics for viral infection, but you prescribe symptom relief instead.</small>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-primary mb-3"><i class="fas fa-database me-2"></i>What Data Does the AI Use?</h6>
                <div class="alert alert-light border">
                    <p class="mb-2"><strong>The AI analyzes clinical data that has already been documented, independent of the prescription form:</strong></p>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><i class="fas fa-check-circle text-success me-2"></i><strong>Appointment Symptoms:</strong> What patient reported</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i><strong>Doctor Notes:</strong> Your clinical observations</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i><strong>Patient Allergies:</strong> Known sensitivities</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i><strong>Past Medications:</strong> Previous prescriptions</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><i class="fas fa-check-circle text-success me-2"></i><strong>Recent Diagnosis:</strong> Latest medical findings</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i><strong>Medical History:</strong> Chronic conditions</li>
                            </ul>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <strong>Note:</strong> If no clinical documentation exists, AI provides general preventive care recommendations.
                    </small>
                </div>

                <div class="alert alert-danger">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>⚠️ CRITICAL SAFETY INFORMATION:</strong>
                    <ul class="mb-0 mt-2">
                        <li>AI suggestions are <strong>clinical decision support only</strong> - not automatic prescriptions</li>
                        <li><strong>All final prescription decisions must be made by qualified healthcare professionals</strong></li>
                        <li>Always verify patient allergies and contraindications before prescribing</li>
                        <li>Check current medications for potential interactions</li>
                        <li>Consider patient age, weight, and organ function</li>
                        <li>AI confidence levels (High/Medium/Low) help guide but don't replace clinical judgment</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>💡 Pro Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Use "Reset Form" button to clear everything and start over</li>
                        <li>AI suggestions include dosage, frequency, and duration recommendations</li>
                        <li>You can modify any AI-suggested values before saving</li>
                        <li>Always review AI warnings and interactions carefully</li>
                        <li>The prescription form works independently - you can prescribe without AI</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- AI Data Sources Modal -->
<div class="modal fade" id="aiDataSourcesModal" tabindex="-1" aria-labelledby="aiDataSourcesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="aiDataSourcesModalLabel">
                    <i class="fas fa-database me-2"></i>AI Clinical Data Sources
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Understanding AI Data Sources:</strong> The AI analyzes clinical information to provide medication suggestions. Data sources are prioritized by importance:
                    <ul class="mb-0 mt-2">
                        <li><strong class="text-danger">CRITICAL:</strong> Required for AI suggestions - AI will be BLOCKED without these</li>
                        <li><strong class="text-warning">Important:</strong> Strongly recommended for accurate suggestions</li>
                        <li><strong class="text-info">Helpful:</strong> Provides additional context</li>
                        <li><strong class="text-secondary">Context:</strong> Used for background information only</li>
                    </ul>
                </div>

                <!-- Data Sources Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th><i class="fas fa-clipboard-list me-1"></i>Data Source & Why It's Needed</th>
                                <th><i class="fas fa-check-circle me-1"></i>Status</th>
                                <th><i class="fas fa-exclamation-triangle me-1"></i>Importance</th>
                                <th><i class="fas fa-shield-alt me-1"></i>Reliability</th>
                                <th><i class="fas fa-info-circle me-1"></i>Current Value</th>
                            </tr>
                        </thead>
                        <tbody id="dataSourcesTableBody">
                            <!-- Dynamic content will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Data Quality Indicators -->
                <div class="mt-4">
                    <h6 class="text-primary mb-3"><i class="fas fa-chart-line me-2"></i>Data Completeness</h6>
                    <div class="progress mb-2" style="height: 25px;" id="dataCompletenessProgress">
                        <div class="progress-bar bg-success" id="dataCompletenessBar" style="width: 0%">Calculating...</div>
                    </div>
                    <small class="text-muted" id="dataCompletenessText">Analyzing available clinical data...</small>
                </div>

                <!-- Action Items -->
                <div class="mt-4">
                    <h6 class="text-warning mb-3"><i class="fas fa-lightbulb me-2"></i>To Improve AI Suggestions:</h6>
                    <ul class="small text-muted" id="improvementSuggestions">
                        <li>Complete patient allergy information in Patient Management</li>
                        <li>Update current medications regularly</li>
                        <li>Add detailed symptoms during appointment booking</li>
                        <li>Create diagnosis records for better clinical context</li>
                    </ul>
                </div>

                <div class="alert alert-light border mt-4">
                    <h6 class="text-dark mb-2"><i class="fas fa-shield-alt me-2"></i>Privacy & Security</h6>
                    <small class="text-muted">All clinical data is encrypted and HIPAA-compliant. AI analysis occurs locally and no patient data leaves your secure environment.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="refreshDataSources()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ML Explanation Modal -->
<div class="modal fade" id="mlExplanationModal" tabindex="-1" aria-labelledby="mlExplanationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="mlExplanationModalLabel">
                    <i class="fas fa-brain me-2"></i>ML Risk Prediction Explanation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>How it works:</strong> Our machine learning model analyzes patient history, appointment patterns, and medical data to predict healthcare risks.
                </div>

                <h6 class="text-primary mb-3"><i class="fas fa-chart-line me-2"></i>Features Actually Analyzed:</h6>
                @php
                    if ($appointment->patient) {
                        $extractor = app(\App\Services\FeatureExtractor::class);
                        $features = $extractor->extractFeatures($appointment->patient, $appointment);
                        $hasHighRisk = $extractor->hasHighRiskCondition($appointment->patient);
                    } else {
                        $features = [0,0,0,0,0];
                        $hasHighRisk = false;
                    }
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Feature</th>
                                <th>Value</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>No-Show Count</strong></td>
                                <td class="text-center"><span class="badge bg-warning">{{ $features[0] ?? 0 }}</span></td>
                                <td>Number of previous missed appointments</td>
                            </tr>
                            <tr>
                                <td><strong>Cancellation Count</strong></td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $features[1] ?? 0 }}</span></td>
                                <td>Number of cancelled appointments</td>
                            </tr>
                            <tr>
                                <td><strong>Days Since Last Visit</strong></td>
                                <td class="text-center"><span class="badge bg-info">{{ $features[2] ?? 0 }}</span></td>
                                <td>Days since patient's last appointment</td>
                            </tr>
                            <tr>
                                <td><strong>Visit Frequency</strong></td>
                                <td class="text-center"><span class="badge bg-primary">{{ number_format($features[3] ?? 0, 1) }}</span></td>
                                <td>Average appointments per year</td>
                            </tr>
                            <tr>
                                <td><strong>Patient Age</strong></td>
                                <td class="text-center"><span class="badge bg-primary">{{ $features[4] ?? 0 }}</span></td>
                                <td>Patient's age in years</td>
                            </tr>
                            <tr>
                                <td><strong>Gender</strong></td>
                                <td class="text-center">
                                    <span class="badge {{ ($features[5] ?? 0) == 1 ? 'bg-danger' : 'bg-secondary' }}">
                                        {{ ($features[5] ?? 0) == 1 ? 'Male' : 'Female/Other' }}
                                    </span>
                                </td>
                                <td>Gender encoding (1=Male, 0=Female/Other)</td>
                            </tr>
                            <tr>
                                <td><strong>Chronic Conditions</strong></td>
                                <td class="text-center">
                                    <span class="badge {{ ($features[6] ?? 0) > 0 ? 'bg-danger' : 'bg-success' }}">
                                        {{ $features[6] ?? 0 }}
                                    </span>
                                </td>
                                <td>Count of high-risk conditions from doctor diagnoses</td>
                            </tr>
                            <tr>
                                <td><strong>Current Medications</strong></td>
                                <td class="text-center"><span class="badge bg-info">{{ $features[7] ?? 0 }}</span></td>
                                <td>Number of current medications</td>
                            </tr>
                            <tr>
                                <td><strong>Appointment Lead Time</strong></td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $features[8] ?? 0 }}</span></td>
                                <td>Days between booking and appointment</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3">
                    <small><i class="fas fa-info-circle me-1"></i><strong>Enhanced ML Features:</strong> Now using 9 features including cancellations, visit frequency, medications, and appointment lead time for improved accuracy.</small>
                </div>

                <hr class="my-4">

                <h6 class="text-primary mb-3"><i class="fas fa-cogs me-2"></i>Prediction Method Used:</h6>
                @php
                    $service = app(\App\Services\PredictiveAnalyticsService::class);
                    $reflection = new ReflectionClass($service);
                    $method = $reflection->getMethod('checkTrainingDataAdequacy');
                    $method->setAccessible(true);
                    $adequacy = $method->invoke($service);
                    $usingML = $adequacy['adequate'];
                @endphp
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-{{ $usingML ? 'success' : 'warning' }} mb-3">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2">
                                    <i class="fas fa-{{ $usingML ? 'brain' : 'calculator' }} me-2"></i>
                                    {{ $usingML ? 'Machine Learning' : 'Rule-Based' }}
                                </h6>
                                <p class="card-text small mb-1">
                                    {{ $usingML ? 'Using trained ML models for predictions' : 'Using rule-based calculations (ML models not adequately trained)' }}
                                </p>
                                <small class="text-muted">
                                    Training Data: {{ $adequacy['total_appointments'] }} appointments
                                    ({{ $adequacy['no_show_count'] }} no-shows, {{ $adequacy['high_risk_count'] }} high-risk)
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-info mb-3">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2">
                                    <i class="fas fa-chart-bar me-2"></i>Model Status
                                </h6>
                                <p class="card-text small mb-1">
                                    @if($usingML)
                                        <span class="text-success">✓ ML Models Active</span>
                                    @else
                                        <span class="text-warning">⚠ Rule-Based Fallback</span>
                                    @endif
                                </p>
                                <small class="text-muted">
                                    Minimum required: 50 appointments, 2% no-show rate, 5% high-risk rate
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-primary mb-3"><i class="fas fa-calculator me-2"></i>Risk Calculations:</h6>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-user-times me-2"></i>No-Show Risk</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">Probability that the patient will miss this appointment.</p>
                                <small class="text-muted">
                                    <strong>Current Result:</strong>
                                    @if(isset($riskScore))
                                        {{ number_format($riskScore->no_show_risk * 100, 1) }}%
                                    @else
                                        N/A
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger mb-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-hospital me-2"></i>Hospitalization Risk</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">Probability that the patient may require hospitalization based on current health indicators.</p>
                                <small class="text-muted">
                                    <strong>Current Result:</strong>
                                    @if(isset($riskScore))
                                        {{ number_format($riskScore->hospitalization_risk * 100, 1) }}%
                                    @else
                                        N/A
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border">
                    <h6 class="text-dark mb-2"><i class="fas fa-lightbulb me-2"></i>Understanding the Results:</h6>
                    <ul class="mb-0 small">
                        <li><strong>Low Risk (< 30%):</strong> Patient shows strong compliance patterns and stable health indicators</li>
                        <li><strong>Medium Risk (30-70%):</strong> Moderate concern - consider follow-up reminders or additional monitoring</li>
                        <li><strong>High Risk (> 70%):</strong> Significant risk - immediate intervention may be needed</li>
                    </ul>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> These predictions are statistical estimates based on historical data and should be used as a clinical decision support tool, not as definitive medical advice.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Enhanced appointment action functions with proper timeout and error handling
function createTimeoutPromise(promise, timeoutMs = 30000) { // Increased to 30 seconds
    return Promise.race([
        promise,
        new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Request timeout - server may be slow, please try again')), timeoutMs)
        )
    ]);
}

function resetButtonState(button, originalHTML) {
    if (button) {
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

function confirmAppointment(appointmentId) {
    if (!confirm('Are you sure you want to confirm this appointment?')) {
        return;
    }

    const btn = event.target.closest('button');
    if (!btn) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/doctor/appointments/${appointmentId}/confirm`;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    document.body.appendChild(form);

    const fetchPromise = fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    createTimeoutPromise(fetchPromise)
    .then(response => {
        if (response.ok) {
            // Try to parse JSON response first
            return response.json().then(data => {
                if (data.success !== false) {
                    showNotification('Appointment confirmed successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to confirm appointment');
                }
            }).catch(() => {
                // Fallback if response is not JSON
                showNotification('Appointment confirmed successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            });
        } else {
            throw new Error('Server returned error status: ' + response.status);
        }
    })
    .catch(error => {
        resetButtonState(btn, originalHTML);
        showNotification('Failed to confirm appointment. Please try again.', 'error');
        console.error('Confirm appointment error:', error);
    })
    .finally(() => {
        if (document.body.contains(form)) {
            document.body.removeChild(form);
        }
    });
}

function cancelAppointment(appointmentId) {
    const form = document.getElementById('cancelForm');
    form.action = `/doctor/appointments/${appointmentId}/cancel`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function submitCancellation() {
    const form = document.getElementById('cancelForm');
    const submitBtn = document.querySelector('#cancelModal .btn-danger');
    if (!submitBtn) return;
    
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cancelling...';

    const fetchPromise = fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    createTimeoutPromise(fetchPromise)
    .then(response => {
        if (response.ok) {
            // Try to parse JSON response first
            return response.json().then(data => {
                if (data.success !== false) {
                    showNotification('Appointment cancelled successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to cancel appointment');
                }
            }).catch(() => {
                // Fallback if response is not JSON
                showNotification('Appointment cancelled successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            });
        } else {
            throw new Error('Server returned error status: ' + response.status);
        }
    })
    .catch(error => {
        resetButtonState(submitBtn, originalText);
        showNotification('Failed to cancel appointment. Please try again.', 'error');
        console.error('Cancel appointment error:', error);
    });
}

function completeAppointment(appointmentId) {
    const form = document.getElementById('completeForm');
    form.action = `/doctor/appointments/${appointmentId}/complete`;
    form.reset();

    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();

    // Remove existing handlers
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    const updatedForm = document.getElementById('completeForm');
    const submitBtn = updatedForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    updatedForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Completing...';

        const fetchPromise = fetch(updatedForm.action, {
            method: 'POST',
            body: new FormData(updatedForm),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        createTimeoutPromise(fetchPromise)
        .then(response => {
            if (response.ok) {
                // Try to parse JSON response first
                return response.json().then(data => {
                    if (data.success !== false) {
                        showNotification('Appointment completed successfully!', 'success');
                        modal.hide();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(data.message || 'Failed to complete appointment');
                    }
                }).catch(() => {
                    // Fallback if response is not JSON
                    showNotification('Appointment completed successfully!', 'success');
                    modal.hide();
                    setTimeout(() => window.location.reload(), 1000);
                });
            } else {
                throw new Error('Server returned error status: ' + response.status);
            }
        })
        .catch(error => {
            resetButtonState(submitBtn, originalText);
            showNotification('Failed to complete appointment. Please try again.', 'error');
            console.error('Complete appointment error:', error);
        });
    });
}

function markNoShow(appointmentId) {
    if (!confirm('Are you sure you want to mark this appointment as no show? This action cannot be undone.')) {
        return;
    }

    const btn = event.target.closest('button');
    if (!btn) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/doctor/appointments/${appointmentId}/no-show`;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    document.body.appendChild(form);

    const fetchPromise = fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    createTimeoutPromise(fetchPromise)
    .then(response => {
        if (response.ok) {
            // Try to parse JSON response first
            return response.json().then(data => {
                if (data.success !== false) {
                    showNotification('Appointment marked as no show successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to mark appointment as no show');
                }
            }).catch(() => {
                // Fallback if response is not JSON
                showNotification('Appointment marked as no show successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            });
        } else {
            throw new Error('Server returned error status: ' + response.status);
        }
    })
    .catch(error => {
        resetButtonState(btn, originalHTML);
        showNotification('Failed to mark appointment as no show. Please try again.', 'error');
        console.error('Mark no-show error:', error);
    })
    .finally(() => {
        if (document.body.contains(form)) {
            document.body.removeChild(form);
        }
    });
}

// Notification System
function showNotification(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        info: 'alert-info',
        warning: 'alert-warning',
        error: 'alert-danger'
    };

    const icons = {
        success: 'fas fa-check-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
    };

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; margin-top: 10px;';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'd-flex align-items-center';

    const icon = document.createElement('i');
    icon.className = `${icons[type]} me-2`;

    const span = document.createElement('span');
    span.textContent = message;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn-close';
    button.setAttribute('data-bs-dismiss', 'alert');
    button.setAttribute('aria-label', 'Close');

    contentDiv.appendChild(icon);
    contentDiv.appendChild(span);
    contentDiv.appendChild(button);
    notification.appendChild(contentDiv);

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Prescription delete functionality
let prescriptionToDelete = null;

function deletePrescription(prescriptionId, medicationName) {
    prescriptionToDelete = prescriptionId;
    // Sanitize the medication name to prevent XSS by using textContent instead of innerHTML
    const cleanName = medicationName.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    document.getElementById('deletePrescriptionName').textContent = cleanName;
    new bootstrap.Modal(document.getElementById('deletePrescriptionModal')).show();
}

function confirmDeletePrescription() {
    if (!prescriptionToDelete) return;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';

    fetch(`/prescriptions/${prescriptionToDelete}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('deletePrescriptionModal')).hide();

            // Remove prescription from DOM
            const prescriptionCard = document.querySelector(`[data-prescription-id="${prescriptionToDelete}"]`);
            if (prescriptionCard) {
                prescriptionCard.remove();
            } else {
                // Fallback: reload the page
                location.reload();
            }

            showNotification('Prescription deleted successfully!', 'success');
        } else {
            throw new Error(data.message || 'Failed to delete prescription');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showNotification(error.message || 'Failed to delete prescription. Please try again.', 'error');
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        prescriptionToDelete = null;
    });
}

// Notification System
function showNotification(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        info: 'alert-info',
        warning: 'alert-warning',
        error: 'alert-danger'
    };

    const icons = {
        success: 'fas fa-check-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
    };

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed`;
    // Position below the top navigation bar (assuming ~80px height) and to the right
    notification.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; margin-top: 10px;';

    // Create content safely to prevent XSS
    const contentDiv = document.createElement('div');
    contentDiv.className = 'd-flex align-items-center';

    const icon = document.createElement('i');
    icon.className = `${icons[type]} me-2`;

    const span = document.createElement('span');
    // Sanitize message to prevent XSS
    span.textContent = message.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn-close';
    button.setAttribute('data-bs-dismiss', 'alert');
    button.setAttribute('aria-label', 'Close');

    contentDiv.appendChild(icon);
    contentDiv.appendChild(span);
    contentDiv.appendChild(button);
    notification.appendChild(contentDiv);

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}


// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips if any
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize form transformation
    initializeFormTransformation();

    // Debug ML Risk Assessment Data
    debugMLRiskAssessment();
});

function debugMLRiskAssessment() {
    console.log('🔍 ML RISK ASSESSMENT DEBUG');
    console.log('===========================');

    // Check if risk scores exist
    @php
        $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id', $appointment->id)?->first();
    @endphp

    @if($riskScore)
        console.log('✅ Risk Scores Found:', {
            no_show_risk: '{{ number_format($riskScore->no_show_risk * 100, 1) }}%',
            hospitalization_risk: '{{ number_format($riskScore->hospitalization_risk * 100, 1) }}%'
        });
    @else
        console.log('❌ NO RISK SCORES FOUND - ML prediction has not run');
    @endif

    // Check training data adequacy
    @php
        $service = app(\App\Services\PredictiveAnalyticsService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('checkTrainingDataAdequacy');
        $method->setAccessible(true);
        $adequacy = $method->invoke($service);
    @endphp

    console.log('🎓 Training Data Status:', {
        adequate: {{ $adequacy['adequate'] ? 'true' : 'false' }},
        total_appointments: {{ $adequacy['total_appointments'] }},
        using_fallback: '{{ !$adequacy['adequate'] ? 'YES (Rule-based)' : 'NO (ML)' }}'
    });

    // Show what SHOULD be calculated
    @php
        if ($appointment->patient) {
            $result = $service->predictRisks($appointment->patient, $appointment);
            $expectedNoShow = number_format($result['no_show_risk'] * 100, 1);
            $expectedHospitalization = number_format($result['hospitalization_risk'] * 100, 1);
        } else {
            $expectedNoShow = 'N/A';
            $expectedHospitalization = 'N/A';
        }
    @endphp

    // Feature Extraction Debug
    @php
        if ($appointment->patient) {
            $extractor = app(\App\Services\FeatureExtractor::class);
            $features = $extractor->extractFeatures($appointment->patient, $appointment);
            $hasHighRisk = $extractor->hasHighRiskCondition($appointment->patient);
        } else {
            $features = [0,0,0,0,0];
            $hasHighRisk = false;
        }
    @endphp
    console.log('🔧 ML Features Extracted:', {
        features_array: {{ json_encode($features) }},
        breakdown: {
            no_show_count: {{ $features[0] ?? 0 }},
            last_visit_days: {{ $features[1] ?? 0 }},
            age: {{ $features[2] ?? 0 }},
            gender_encoded: {{ $features[3] ?? 0 }},
            chronic_conditions_from_appointments: {{ $features[4] ?? 0 }}
        },
        has_high_risk_conditions: {{ $hasHighRisk ? 'true' : 'false' }}
    });

    console.log('🎯 Expected Calculation:', {
        no_show_risk: '{{ $expectedNoShow }}%',
        hospitalization_risk: '{{ $expectedHospitalization }}%'
    });

    @if($riskScore)
        console.log('📊 Match Check:', {
            scores_match: '{{ ($expectedNoShow === number_format($riskScore->no_show_risk * 100, 1) && $expectedHospitalization === number_format($riskScore->hospitalization_risk * 100, 1)) ? 'YES' : 'NO' }}'
        });
    @endif
}

// Dynamic form transformation
function initializeFormTransformation() {
    // Handle form field transformation
    const formSelect = document.getElementById('form');
    if (formSelect) {
        formSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'form');
            } else {
                ensureSelectField(this, 'form');
            }
        });
    }

    // Handle route field transformation
    const routeSelect = document.getElementById('route');
    if (routeSelect) {
        routeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'route');
            } else {
                ensureSelectField(this, 'route');
            }
        });
    }

    // Handle frequency field transformation
    const frequencySelect = document.getElementById('frequency');
    if (frequencySelect) {
        frequencySelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'frequency');
            } else {
                ensureSelectField(this, 'frequency');
            }
        });
    }

    // Handle duration field transformation
    const durationSelect = document.getElementById('duration');
    if (durationSelect) {
        durationSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'duration');
            } else {
                ensureSelectField(this, 'duration');
            }
        });
    }
}

function transformToTextInput(selectElement, fieldType) {
    const parent = selectElement.parentElement;
    const currentValue = selectElement.value;

    // Create text input
    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.className = 'form-control';
    textInput.id = fieldType;
    textInput.name = fieldType;
    textInput.required = true;

    // Set field-specific placeholder
    const placeholders = {
        'form': 'Enter custom form (e.g., Suppository, Patch)',
        'route': 'Enter custom route (e.g., Topical, Sublingual)',
        'frequency': 'Enter custom frequency (e.g., Every 4 hours)',
        'duration': 'Enter custom duration (e.g., 3 weeks)'
    };
    textInput.placeholder = placeholders[fieldType] || 'Enter custom value';

    // Preserve any existing custom value or set default
    if (currentValue === 'other' || !currentValue) {
        textInput.value = '';
    } else {
        textInput.value = currentValue;
    }

    // Replace select with input
    parent.replaceChild(textInput, selectElement);

    // Focus on the new input
    textInput.focus();
}

function ensureSelectField(currentElement, fieldType) {
    if (currentElement.tagName === 'SELECT') return;

    const parent = currentElement.parentElement;
    const currentValue = currentElement.value;

    // Create select element
    const selectElement = document.createElement('select');
    selectElement.className = 'form-select';
    selectElement.id = fieldType;
    selectElement.name = fieldType;
    selectElement.required = true;

    // Define options for each field type
    const fieldOptions = {
        'form': [
            { value: '', text: 'Select form' },
            { value: 'tablet', text: 'Tablet' },
            { value: 'capsule', text: 'Capsule' },
            { value: 'liquid', text: 'Liquid' },
            { value: 'injection', text: 'Injection' },
            { value: 'cream', text: 'Cream/Ointment' },
            { value: 'inhaler', text: 'Inhaler' },
            { value: 'patch', text: 'Patch' },
            { value: 'other', text: 'Other' }
        ],
        'route': [
            { value: '', text: 'Select route' },
            { value: 'oral', text: 'Oral' },
            { value: 'topical', text: 'Topical' },
            { value: 'intravenous', text: 'Intravenous' },
            { value: 'intramuscular', text: 'Intramuscular' },
            { value: 'subcutaneous', text: 'Subcutaneous' },
            { value: 'inhalation', text: 'Inhalation' },
            { value: 'rectal', text: 'Rectal' },
            { value: 'other', text: 'Other' }
        ],
        'frequency': [
            { value: '', text: 'Select frequency' },
            { value: 'once daily', text: 'Once daily' },
            { value: 'twice daily', text: 'Twice daily' },
            { value: 'three times daily', text: 'Three times daily' },
            { value: 'four times daily', text: 'Four times daily' },
            { value: 'every 6 hours', text: 'Every 6 hours' },
            { value: 'every 8 hours', text: 'Every 8 hours' },
            { value: 'every 12 hours', text: 'Every 12 hours' },
            { value: 'as needed', text: 'As needed' },
            { value: 'other', text: 'Other' }
        ],
        'duration': [
            { value: '', text: 'Select duration' },
            { value: '3 days', text: '3 days' },
            { value: '7 days', text: '7 days' },
            { value: '10 days', text: '10 days' },
            { value: '14 days', text: '14 days' },
            { value: '1 month', text: '1 month' },
            { value: '2 months', text: '2 months' },
            { value: '3 months', text: '3 months' },
            { value: '6 months', text: '6 months' },
            { value: 'other', text: 'Other' }
        ]
    };

    const options = fieldOptions[fieldType] || [];
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        if (option.value === currentValue) {
            optionElement.selected = true;
        }
        selectElement.appendChild(optionElement);
    });

    // Replace input with select
    parent.replaceChild(selectElement, currentElement);
}

function resetFormField() {
    const fields = ['form', 'route', 'frequency', 'duration'];
    fields.forEach(fieldType => {
        const element = document.getElementById(fieldType);
        if (element && element.tagName !== 'SELECT') {
            ensureSelectField(element, fieldType);
        }
    });
}

// Diagnosis form functionality
function toggleDiagnosisForm() {
    const diagnosisSection = document.getElementById('diagnosis-section');
    const isVisible = diagnosisSection.style.display !== 'none';

    if (isVisible) {
        // Hide the form
        diagnosisSection.style.display = 'none';
        // Scroll to the Next Steps section
        document.querySelector('.table-card.mb-4').scrollIntoView({ behavior: 'smooth' });
    } else {
        // Show the form
        diagnosisSection.style.display = 'block';
        // Scroll to the diagnosis section
        diagnosisSection.scrollIntoView({ behavior: 'smooth' });
        // Focus on the diagnosis text area
        setTimeout(() => {
            document.getElementById('diagnosis_text').focus();
        }, 300);
    }
}

// Voice recording functionality for diagnosis
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

document.addEventListener('DOMContentLoaded', function() {
    // Check if there are validation errors and show the diagnosis form if needed
    const hasErrors = @json($errors->any());
    if (hasErrors) {
        const diagnosisSection = document.getElementById('diagnosis-section');
        diagnosisSection.style.display = 'block';

        // Scroll to the diagnosis section to make errors visible
        diagnosisSection.scrollIntoView({ behavior: 'smooth' });
    }

    // Initialize voice recording buttons
    const startRecordingBtn = document.getElementById('startRecording');
    const stopRecordingBtn = document.getElementById('stopRecording');
    const playRecordingBtn = document.getElementById('playRecording');
    const audioPlayback = document.getElementById('audioPlayback');
    const recordingStatus = document.getElementById('recordingStatus');

    if (startRecordingBtn) {
        startRecordingBtn.addEventListener('click', startVoiceRecording);
    }
    if (stopRecordingBtn) {
        stopRecordingBtn.addEventListener('click', stopVoiceRecording);
    }
    if (playRecordingBtn) {
        playRecordingBtn.addEventListener('click', function() {
            if (audioPlayback.src) {
                audioPlayback.play();
            }
        });
    }

    function startVoiceRecording() {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPlayback.src = audioUrl;
                    audioPlayback.style.display = 'block';
                    playRecordingBtn.style.display = 'inline-block';

                    // Create a file input for the recorded audio
                    const fileInput = document.getElementById('voice_files');
                    const file = new File([audioBlob], 'voice_recording.wav', { type: 'audio/wav' });

                    // Create a DataTransfer to set the file
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                };

                mediaRecorder.start();
                isRecording = true;

                startRecordingBtn.style.display = 'none';
                stopRecordingBtn.style.display = 'inline-block';
                recordingStatus.textContent = 'Recording... Click "Stop Recording" when finished.';
                recordingStatus.style.color = 'red';
            })
            .catch(error => {
                console.error('Error accessing microphone:', error);
                recordingStatus.textContent = 'Error: Could not access microphone. Please check permissions.';
                recordingStatus.style.color = 'red';
            });
    }

    function stopVoiceRecording() {
        if (mediaRecorder && isRecording) {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
            isRecording = false;

            stopRecordingBtn.style.display = 'none';
            startRecordingBtn.style.display = 'inline-block';
            recordingStatus.textContent = 'Recording saved. You can play it back or upload additional files.';
            recordingStatus.style.color = 'green';
        }
    }
});

// Workflow selector functionality
document.addEventListener('DOMContentLoaded', function() {
    const workflowButtons = document.querySelectorAll('.workflow-btn');
    const workflowText = document.getElementById('workflow-text');

    const workflowDescriptions = {
        'manual': 'Manual Entry: Fill the form directly with your prescription details.',
        'ai-first': 'AI First: Click AI button first, then review and use suggestions to fill the form.',
        'ai-assisted': 'AI Assisted: Fill some form fields, then use AI for additional guidance.',
        'explore': 'Explore AI: Review AI suggestions for learning, then fill form manually.'
    };

    workflowButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            workflowButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');

            // Update description
            const workflow = this.dataset.workflow;
            workflowText.textContent = workflowDescriptions[workflow];

            // Optional: Show/hide AI section based on workflow
            const aiSection = document.querySelector('.ai-section');
            if (aiSection) {
                if (workflow === 'manual') {
                    aiSection.style.display = 'none';
                } else {
                    aiSection.style.display = 'block';
                }
            }
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize AI Medical Copilot save button
    const saveButton = document.getElementById('saveCopilotAnalysisSection');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            const appointmentId = window.currentAppointmentId; // This should be set when opening the section

            if (!appointmentId) {
                showNotification('Error: Appointment ID not found', 'error');
                return;
            }

            // Collect current analysis data from the UI
            const analysisData = collectAnalysisData();

            // Include checkboxes for clinical note inclusion
            const includeInNote = {
                summary: document.getElementById('includeSummaryInNoteSection').checked,
                considerations: document.getElementById('includeConsiderationsInNoteSection').checked,
                questions: document.getElementById('includeQuestionsInNoteSection').checked,
                red_flags: document.getElementById('includeRedFlagsInNoteSection').checked
            };

            // Save the analysis
            saveAICopilotAnalysis(appointmentId, analysisData, includeInNote);
        });
    }
});

// Function to submit diagnosis form via AJAX
function submitDiagnosisForm() {
    const form = document.getElementById('diagnosisForm');
    const formData = new FormData(form);

    // Disable the submit button to prevent multiple submissions
    const submitButton = document.querySelector('#diagnosisForm button[type="button"][onclick="submitDiagnosisForm()"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Diagnosis...';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        // Clone the response to handle both JSON and text cases
        const responseClone = response.clone();

        // Check if the response is OK
        if (!response.ok) {
            // For 422 validation errors, Laravel returns JSON with validation errors
            if (response.status === 422) {
                const errorData = await response.json();
                // Return the error data in a consistent format
                return {
                    success: false,
                    message: 'Validation failed',
                    errors: errorData.errors || {}
                };
            } else {
                // For other error statuses, try to get error response as JSON first
                try {
                    const errorData = await response.json();
                    return {
                        success: false,
                        message: errorData.message || `HTTP error! status: ${response.status}`,
                        errors: errorData.errors || {}
                    };
                } catch (jsonError) {
                    // If JSON parsing fails, get response as text from the clone
                    try {
                        const errorText = await responseClone.text();
                        return {
                            success: false,
                            message: `HTTP error! status: ${response.status}, message: ${errorText.substring(0, 200)}...`,
                            errors: {}
                        };
                    } catch (textError) {
                        // If both fail, return a generic error
                        return {
                            success: false,
                            message: `HTTP error! status: ${response.status}`,
                            errors: {}
                        };
                    }
                }
            }
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success toast notification
            showNotification(data.message || 'Diagnosis created successfully!', 'success');

            // Reset and hide the form
            form.reset();
            document.getElementById('diagnosis-section').style.display = 'none';

            // Reload the page after a delay to show updated content
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Clear previous validation errors
            clearValidationErrors();

            // Show error notification
            let errorMessage = data.message || 'Failed to create diagnosis. Please try again.';

            // Handle Laravel's validation error format and display on form
            if (data.errors) {
                // Format validation errors and display on form fields
                for (const field in data.errors) {
                    displayValidationError(field, data.errors[field].join(', '));
                    errorMessage += ' ' + data.errors[field].join(', ');
                }
            }

            showNotification(errorMessage, 'error');
        }
    })
    .catch(error => {
        console.error('Error creating diagnosis:', error);
        // Extract error message from the error object
        let errorMessage = 'An error occurred while creating the diagnosis. Please try again.';
        if (error.message) {
            errorMessage = error.message;
        }
        showNotification(errorMessage, 'error');
    })
    .finally(() => {
        // Re-enable the submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
}

// Helper function to display validation error for a specific field
function displayValidationError(fieldName, errorMessage) {
    // Find the input field by name
    let field = document.querySelector(`[name="${fieldName}"]`);

    // Special handling for nested array fields like patient_data[height]
    if (!field && fieldName.includes('[')) {
        const normalizedFieldName = fieldName.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
        field = document.querySelector(`[name="${normalizedFieldName}"]`);
    }

    if (field) {
        // Add error styling to the field
        field.classList.add('is-invalid');

        // Check if error feedback element already exists
        let errorElement = field.parentNode.querySelector('.invalid-feedback');

        if (!errorElement) {
            // Create error feedback element
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentNode.appendChild(errorElement);
        }

        // Set the error message
        errorElement.textContent = errorMessage;
    }

    // Special handling for array fields like voice_files[]
    if (fieldName === 'voice_files') {
        const fields = document.querySelectorAll('[name="voice_files[]"]');
        fields.forEach(fileField => {
            fileField.classList.add('is-invalid');
        });
    }
}

// Helper function to clear validation errors
function clearValidationErrors() {
    // Remove error styling and messages from all fields
    const invalidFields = document.querySelectorAll('.is-invalid');
    invalidFields.forEach(field => {
        field.classList.remove('is-invalid');
    });

    // Remove all error message elements
    const errorMessages = document.querySelectorAll('.invalid-feedback');
    errorMessages.forEach(element => {
        element.remove();
    });
}

// AI Medical Copilot section functionality
function toggleAIMedicalCopilotForm() {
    const copilotSection = document.getElementById('ai-medical-copilot-section');
    const isVisible = copilotSection.style.display !== 'none';

    if (isVisible) {
        // Hide the form
        copilotSection.style.display = 'none';
        // Scroll to the Next Steps section
        document.querySelector('.table-card.mb-4').scrollIntoView({ behavior: 'smooth' });
    } else {
        // Show the form
        copilotSection.style.display = 'block';
        // Scroll to the AI copilot section
        copilotSection.scrollIntoView({ behavior: 'smooth' });

        // Initialize the AI Medical Copilot for this appointment
        const appointmentId = {{ $appointment->id }};
        initializeAIMedicalCopilot(appointmentId);
    }
}

// Function to initialize AI Medical Copilot
function initializeAIMedicalCopilot(appointmentId) {
    // Show loading state
    document.getElementById('copilotLoadingSection').style.display = 'block';
    document.getElementById('copilotContentSection').style.display = 'none';
    document.getElementById('copilotErrorSection').style.display = 'none';

    // Collect structured data from the appointment
    const structuredData = collectStructuredData(appointmentId);

    // Call AI Medical Copilot API
    callAIMedicalCopilotAPI(appointmentId, structuredData);
}

// Function to collect structured data from the appointment
function collectStructuredData(appointmentId) {
    // This would be populated with actual data from the appointment
    // For now, we'll use sample data that matches the required structure

    return {
        complaint: {
            chief_complaint: document.querySelector('[data-appointment-reason]')?.textContent || '{{ $appointment->reason }}',
            onset: 'recent',
            severity: 'moderate',
            associated_symptoms: []
        },
        vitals: {
            bp: '',
            hr: null,
            spo2: null,
            temperature: null
        },
        history: {
            chronic_conditions: [],
            medications: [],
            allergies: []
        },
        labs: {},
        previous_visits: {
            last_diagnoses: [],
            recent_er_visits: [],
            patterns: []
        }
    };
}

// Function to call AI Medical Copilot API
function callAIMedicalCopilotAPI(appointmentId, structuredData) {
    fetch(`/ai/appointments/${appointmentId}/medical-copilot`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            complaint: structuredData.complaint,
            vitals: structuredData.vitals,
            history: structuredData.history,
            labs: structuredData.labs,
            previous_visits: structuredData.previous_visits
        })
    })
    .then(response => response.json())
    .then(response => {
        // Hide loading, show content
        document.getElementById('copilotLoadingSection').style.display = 'none';
        document.getElementById('copilotContentSection').style.display = 'block';

        // Check for errors
        if (response.error) {
            showCopilotErrorSection(response.message || response.error);
            return;
        }

        if (response.disabled) {
            showCopilotErrorSection('AI Medical Copilot is currently disabled');
            return;
        }

        // Store the response for saving
        window.currentCopilotResponse = response;
        window.currentAppointmentId = appointmentId;

        // Populate the UI with AI analysis
        populateCopilotUISection(response);

        // Log success
        console.log('AI Medical Copilot analysis successful', response);
    })
    .catch(error => {
        // Hide loading, show error
        document.getElementById('copilotLoadingSection').style.display = 'none';

        const errorMessage = error.message || 'Failed to connect to AI Medical Copilot';
        showCopilotErrorSection(errorMessage);

        // Log error
        console.error('AI Medical Copilot error:', errorMessage);
    });
}

// Function to show error in section
function showCopilotErrorSection(message) {
    document.getElementById('copilotErrorMessageSection').textContent = message;
    document.getElementById('copilotErrorSection').style.display = 'block';
    document.getElementById('copilotContentSection').style.display = 'none';
}

// Function to populate UI with AI analysis in section
function populateCopilotUISection(response) {
    // Medical Case Summary
    const summaryContent = `
        <p class="mb-0">${response.medical_case_summary || 'No summary available'}</p>
        <div class="mt-2 small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Smart summary for quick case understanding
        </div>
    `;
    document.getElementById('copilotSummarySection').innerHTML = summaryContent;

    // Differential Considerations
    let considerationsHtml = '<p><strong>Possible considerations (not diagnoses):</strong></p>';
    if (Array.isArray(response.differential_considerations) && response.differential_considerations.length > 0) {
        considerationsHtml += '<ul class="copilot-list">';
        response.differential_considerations.forEach(item => {
            if (typeof item === 'object' && item.consideration) {
                considerationsHtml += `<li>
                    <strong>${item.consideration}</strong>
                    ${item.rationale ? `<br><small class="text-muted">${item.rationale}</small>` : ''}
                </li>`;
            } else {
                // Fallback for string format
                considerationsHtml += `<li>${item}</li>`;
            }
        });
        considerationsHtml += '</ul>';
    } else {
        considerationsHtml = '<p class="text-muted">No specific considerations identified based on current data.</p>';
    }
    document.getElementById('copilotConsiderationsSection').innerHTML = considerationsHtml;

    // Follow-up Questions
    let questionsHtml = '<p><strong>Questions to help complete the clinical picture:</strong></p>';
    if (Array.isArray(response.follow_up_questions) && response.follow_up_questions.length > 0) {
        questionsHtml += '<ul class="copilot-list">';
        response.follow_up_questions.forEach(question => {
            questionsHtml += `<li>${question}</li>`;
        });
        questionsHtml += '</ul>';
    } else {
        questionsHtml = '<p class="text-muted">No additional questions suggested based on current information.</p>';
    }
    document.getElementById('copilotQuestionsSection').innerHTML = questionsHtml;

    // Red Flags
    let redFlagsHtml = '<p><strong>Potential red flags detected:</strong></p>';
    if (Array.isArray(response.red_flags) && response.red_flags.length > 0) {
        redFlagsHtml += '<ul class="copilot-list">';
        response.red_flags.forEach(flag => {
            redFlagsHtml += `<li>${flag}</li>`;
        });
        redFlagsHtml += '</ul>';
    } else {
        redFlagsHtml = '<p class="text-success">No immediate red flags detected based on available data.</p>';
    }
    document.getElementById('copilotRedFlagsSection').innerHTML = redFlagsHtml;

    // Compliance label
    if (response.compliance && response.compliance.label) {
        document.getElementById('copilotComplianceLabelSection').textContent = response.compliance.label;
    }

    // Patient History (if available in response)
    if (response.patient_history) {
        const history = response.patient_history;
        let historyHtml = '';

        if (Array.isArray(history.previous_diagnoses) && history.previous_diagnoses.length > 0) {
            historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-stethoscope me-1"></i>Previous Diagnoses:</h6>';
            historyHtml += '<ul class="copilot-list mb-3">';
            history.previous_diagnoses.forEach(diagnosis => {
                historyHtml += `<li>${diagnosis}</li>`;
            });
            historyHtml += '</ul>';
        }

        if (Array.isArray(history.chronic_conditions) && history.chronic_conditions.length > 0) {
            historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-heartbeat me-1"></i>Chronic Conditions:</h6>';
            historyHtml += '<ul class="copilot-list mb-3">';
            history.chronic_conditions.forEach(condition => {
                historyHtml += `<li>${condition}</li>`;
            });
            historyHtml += '</ul>';
        }

        if (Array.isArray(history.previous_ai_analyses) && history.previous_ai_analyses.length > 0) {
            historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-brain me-1"></i>Previous AI Analyses:</h6>';
            history.previous_ai_analyses.forEach(analysis => {
                historyHtml += `<div class="border-start border-info border-3 ps-3 mb-3">
                    <small class="text-muted">${analysis.generated_at}</small>
                    <p class="mb-1">${analysis.summary}</p>
                    ${Array.isArray(analysis.red_flags) && analysis.red_flags.length > 0 ?
                        `<small class="text-danger">⚠️ Red flags: ${analysis.red_flags.join(', ')}</small>` :
                        '<small class="text-success">✓ No red flags</small>'}
                </div>`;
            });
        }

        if (!historyHtml) {
            historyHtml = '<p class="text-muted">No significant patient history available.</p>';
        }

        document.getElementById('copilotHistorySection').innerHTML = historyHtml;
    }

    // Add disclaimer if available
    if (response.legal_disclaimer) {
        const disclaimerDiv = document.createElement('div');
        disclaimerDiv.className = 'copilot-disclaimer mt-3';
        disclaimerDiv.innerHTML = `<i class="fas fa-shield-alt me-1"></i> ${response.legal_disclaimer}`;
        document.querySelector('.copilot-disclaimer').parentNode.appendChild(disclaimerDiv);
    }

    // Initialize tab functionality for the section
    initializeCopilotTabFunctionality();
}

// Function to initialize tab functionality for the section
function initializeCopilotTabFunctionality() {
    // Add event listeners to the tab buttons
    const tabButtons = document.querySelectorAll('#ai-medical-copilot-section .copilot-tab');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            // Update tab buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Update tab content
            const tabContents = document.querySelectorAll('#ai-medical-copilot-section .copilot-tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.getAttribute('data-tab-content') === tabId) {
                    content.classList.add('active');
                }
            });
        });
    });
}


// Function to collect analysis data from the current UI state
function collectAnalysisData() {
    // Extract data from the global response object if available
    if (window.currentCopilotResponse) {
        return window.currentCopilotResponse;
    }

    // Fallback: extract from current display (less reliable)
    return {
        medical_case_summary: document.querySelector('#copilotSummarySection p')?.textContent.trim() || 'No summary available',
        differential_considerations: extractListItems('#copilotConsiderationsSection li'),
        follow_up_questions: extractListItems('#copilotQuestionsSection li'),
        red_flags: extractListItems('#copilotRedFlagsSection li'),
        disclaimer: 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.',
        compliance: {
            ai_generated: true,
            physician_verification_required: true,
            label: 'AI-generated draft. Physician verified.',
            timestamp: new Date().toISOString(),
            generated_by: 'AI Medical Copilot',
            version: 'ai-copilot-clinical-v1.1'
        },
        legal_disclaimer: 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.'
    };
}

// Helper function to extract list items from HTML
function extractListItems(selector) {
    const items = [];
    const elements = document.querySelectorAll(selector);
    elements.forEach(element => {
        const text = element.cloneNode(true).textContent.trim();
        if (text && text !== 'Loading...' && !text.includes('Loading')) {
            items.push(text);
        }
    });
    return items;
}

// Function to save AI copilot analysis
function saveAICopilotAnalysis(appointmentId, analysisData, includeInNote) {
    fetch(`/ai/appointments/${appointmentId}/ai-analyses/save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            analysis_data: analysisData,
            include_in_note: includeInNote
        })
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            showNotification('AI Medical Copilot analysis saved successfully!', 'success');

            // Close the section after a short delay
            setTimeout(() => {
                document.getElementById('ai-medical-copilot-section').style.display = 'none';
            }, 1500);
        } else {
            showNotification(response.message || 'Failed to save analysis', 'error');
        }
    })
    .catch(error => {
        const errorMessage = error.message || 'Failed to save AI analysis';
        showNotification(errorMessage, 'error');
        console.error('Save AI analysis error:', errorMessage);
    });
}

// Function to view patient's AI analysis history
function viewPatientAIAnalyses(patientId) {
    // Show the AI history section
    const historySection = document.getElementById('ai-history-section');
    let shouldScroll = true;

    if (!historySection) {
        // Create the AI history section if it doesn't exist
        createAIHistorySection();
    } else {
        // Check if section is currently visible
        const isVisible = historySection.style.display !== 'none';
        if (!isVisible) {
            // If it's hidden, show it
            historySection.style.display = 'block';
        } else {
            // If it's already visible, we might want to scroll anyway to bring it into view
            shouldScroll = true;
        }
    }

    // Load patient AI analyses
    loadPatientAIAnalyses(patientId);

    // Small delay to ensure DOM is updated before scrolling
    setTimeout(() => {
        if (shouldScroll) {
            const section = document.getElementById('ai-history-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }, 100);
}

// Function to create AI history section
function createAIHistorySection() {
    // Check if section already exists
    if (document.getElementById('ai-history-section')) {
        return;
    }

    // Create the section element
    const section = document.createElement('div');
    section.id = 'ai-history-section';
    section.className = 'table-card';
    section.style.display = 'block'; // Start visible to show loading

    section.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold text-info">
                    <i class="fas fa-history me-2"></i>Patient AI Analysis History
                </h4>
                <p class="mb-0 text-muted small">Previous AI Medical Copilot analyses for this patient</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAIHistorySection()">
                <i class="fas fa-times me-1"></i>Close
            </button>
        </div>

        <div id="aiHistoryContentSection">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading AI analysis history...</p>
            </div>
        </div>
    `;

    // Insert the section after the AI Medical Copilot section or at the end of content
    const copilotSection = document.getElementById('ai-medical-copilot-section');
    const diagnosisSection = document.getElementById('diagnosis-section');

    if (copilotSection) {
        copilotSection.parentNode.insertBefore(section, copilotSection.nextSibling);
    } else if (diagnosisSection) {
        diagnosisSection.parentNode.insertBefore(section, diagnosisSection.nextSibling);
    } else {
        // If neither section exists, append to the main content area
        const mainContent = document.querySelector('.dashboard-container .container');
        if (mainContent) {
            mainContent.appendChild(section);
        }
    }
}

// Function to toggle AI history section
function toggleAIHistorySection() {
    const historySection = document.getElementById('ai-history-section');
    if (!historySection) return;

    const isVisible = historySection.style.display !== 'none';

    if (isVisible) {
        // Hide the section
        historySection.style.display = 'none';
        // Scroll to the Next Steps section
        document.querySelector('.table-card.mb-4').scrollIntoView({ behavior: 'smooth' });
    } else {
        // Show the section
        historySection.style.display = 'block';
        // Scroll to the AI history section
        historySection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Function to load patient AI analyses
function loadPatientAIAnalyses(patientId) {
    const contentElement = document.getElementById('aiHistoryContentSection');
    if (!contentElement) return;

    contentElement.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading AI analysis history...</p>
        </div>
    `;

    // Log for debugging
    console.log('Loading AI analyses for patient ID:', patientId);
    console.log('Fetching from URL:', `/ai/patients/${patientId}/ai-analyses`);

    fetch(`/ai/patients/${patientId}/ai-analyses`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(response => {
        console.log('API Response:', response);
        // The response is paginated, so we need to use response.data which contains the analyses
        if (response.data !== undefined) {
            displayAIAnalysesSection(response.data || []);
        } else {
            contentElement.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${response.message || 'Failed to load AI analysis history'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Detailed error:', error);
        contentElement.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load AI analysis history: ${error.message}. Please check browser console for details.
            </div>
        `;
        console.error('Load AI analysis error:', error);
    });
}

// Function to display AI analyses in the section
function displayAIAnalysesSection(analyses) {
    const contentElement = document.getElementById('aiHistoryContentSection');
    if (!contentElement) return;

    if (!analyses || analyses.length === 0) {
        contentElement.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No AI Analyses Found</h5>
                <p class="text-muted">This patient hasn't had any AI Medical Copilot analyses saved yet.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="ai-analyses-timeline">';

    analyses.forEach(analysis => {
        const analysisData = typeof analysis.analysis_data === 'string' ?
            JSON.parse(analysis.analysis_data) : analysis.analysis_data;

        html += `
            <div class="ai-analysis-card mb-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-brain me-2"></i>AI Medical Copilot Analysis
                            </h6>
                            <small>${new Date(analysis.generated_at).toLocaleDateString()} at ${new Date(analysis.generated_at).toLocaleTimeString()}</small>
                        </div>
                        <div class="d-flex gap-2">
                            ${analysis.status === 'reviewed' ?
                                '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Reviewed</span>' :
                                '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending Review</span>'}
                            <a href="/ai/ai-analyses/${analysis.id}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-file-medical me-1"></i>Summary</h6>
                                <p class="mb-3">${analysisData.medical_case_summary || 'No summary available'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning"><i class="fas fa-list-check me-1"></i>Key Considerations</h6>
                                <ul class="mb-3 small">
                                    ${displayConsiderationsSection(analysisData.differential_considerations || [])}
                                </ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-info"><i class="fas fa-question-circle me-1"></i>Follow-up Questions</h6>
                                <ul class="mb-3 small">
                                    ${displayQuestionsSection(analysisData.follow_up_questions || [])}
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger"><i class="fas fa-flag me-1"></i>Red Flags</h6>
                                <ul class="mb-3 small">
                                    ${displayRedFlagsSection(analysisData.red_flags || [])}
                                </ul>
                            </div>
                        </div>
                        ${analysis.reviewed_at ? `
                            <div class="border-top pt-3 mt-3">
                                <h6 class="text-success"><i class="fas fa-user-md me-1"></i>Physician Review</h6>
                                <p class="mb-1 small text-muted">Reviewed by Dr. ${analysis.reviewer?.name || 'Unknown'} on ${new Date(analysis.reviewed_at).toLocaleDateString()}</p>
                                ${analysis.doctor_notes ? `<p class="mb-0">${analysis.doctor_notes}</p>` : '<p class="text-muted small">No additional notes</p>'}
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    contentElement.innerHTML = html;
}

// Helper functions for displaying analysis components in the section
function displayConsiderationsSection(considerations) {
    if (!considerations || considerations.length === 0) return '<li class="text-muted">No considerations recorded</li>';

    return considerations.slice(0, 3).map(item => {
        if (typeof item === 'object' && item.consideration) {
            return `<li><strong>${item.consideration}</strong><br><small class="text-muted">${item.rationale || ''}</small></li>`;
        } else {
            return `<li>${item}</li>`;
        }
    }).join('');
}

function displayQuestionsSection(questions) {
    if (!questions || questions.length === 0) return '<li class="text-muted">No questions recorded</li>';
    return questions.slice(0, 3).map(question => `<li>${question}</li>`).join('');
}

function displayRedFlagsSection(flags) {
    if (!flags || flags.length === 0) return '<li class="text-success">No red flags detected</li>';
    return flags.slice(0, 3).map(flag => `<li class="text-danger">${flag}</li>`).join('');
}
</script>
@endpush