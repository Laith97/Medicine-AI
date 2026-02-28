@extends('master')

@section('title', 'Prescription Details')

@push('styles')
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
    content: '💊';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Button styles within header */
.dashboard-header .btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    transition: all 0.3s ease;
}

.dashboard-header .btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-1px);
}

.dashboard-header .btn-primary {
    background: #DE6262;
    border-color: #DE6262;
}

.dashboard-header .btn-primary:hover {
    background: #c44d4d;
    border-color: #c44d4d;
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
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('appointments.index') }}" class="btn btn-secondary-custom me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                </a>
                <div>
                    <h2 class="h1 mb-1">Prescription Details</h2>
                    <p>ID: #{{ $prescription->id }}</p>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('prescriptions.show', $prescription) }}?pdf=1" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i>Download PDF
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Prescription Overview -->
                <div class="table-card mb-4">
                    <div class="bg-primary text-white p-4 rounded-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">{{ $prescription->medication_name }}</h3>
                                <p class="mb-0 opacity-75">Prescribed on {{ $prescription->created_at->format('F j, Y') }}</p>
                            </div>
                            <div class="text-end">
                                <div class="h4 mb-0">{{ $prescription->dosage }}</div>
                                <small class="opacity-75">dosage</small>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-clock text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Frequency</h6>
                                        <small class="text-muted">{{ $prescription->frequency }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-calendar text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Duration</h6>
                                        <small class="text-muted">{{ $prescription->duration }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-pills text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Form & Route</h6>
                                        <small class="text-muted">{{ ucfirst($prescription->form ?? 'N/A') }} - {{ ucfirst($prescription->route ?? 'N/A') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-hashtag text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Quantity & Refills</h6>
                                        <small class="text-muted">{{ $prescription->quantity ?? 'N/A' }} units, {{ $prescription->refills ?? 0 }} refills</small>
                                    </div>
                                </div>
                            </div>
                            @if($prescription->indication)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-secondary bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-stethoscope text-secondary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Indication</h6>
                                        <small class="text-muted">{{ $prescription->indication }}</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($prescription->start_date)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-dark bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-play text-dark"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Start Date</h6>
                                        <small class="text-muted">{{ $prescription->start_date->format('F j, Y') }}</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-check text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Generic Allowed</h6>
                                        <small class="text-muted">{{ $prescription->generic_allowed ? 'Yes' : 'No' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Information -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-4">Prescribing Doctor</h5>
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                @if($prescription->doctor && $prescription->doctor->profile_image)
                                    <img src="{{ asset('storage/' . $prescription->doctor->profile_image) }}"
                                         alt="{{ $prescription->doctor->name ?? 'Doctor' }}"
                                         class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                         style="width: 64px; height: 64px;">
                                        <i class="fas fa-user-md text-primary"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $prescription->doctor->name ?? 'Doctor' }}</h5>
                                <p class="text-primary mb-2">{{ $prescription->doctor->doctor->specialty->name ?? 'Medical Professional' }}</p>
                                @if($prescription->doctor && $prescription->doctor->doctor)
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="text-warning me-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= floor($prescription->doctor->doctor->average_rating ?? 0))
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <small class="text-muted">{{ number_format($prescription->doctor->doctor->average_rating ?? 0, 1) }} ({{ $prescription->doctor->doctor->reviews_count ?? 0 }} reviews)</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointment Information -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Related Appointment</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Date & Time</p>
                                <p class="mb-3">{{ $prescription->appointment ? $prescription->appointment->appointment_date->format('F j, Y \a\t g:i A') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Type</p>
                                <p class="mb-3">{{ $prescription->appointment->appointment_type ?? 'Regular Consultation' }}</p>
                            </div>
                            @if($prescription->appointment && $prescription->appointment->reason)
                                <div class="col-12">
                                    <p class="text-muted mb-1">Reason for Visit</p>
                                    <p class="mb-3">{{ $prescription->appointment->reason }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($prescription->instructions)
                <!-- Instructions -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Patient Instructions</h5>
                        <div class="bg-light p-3 rounded">
                            {{ $prescription->instructions }}
                        </div>
                    </div>
                </div>
                @endif

                @if($prescription->notes)
                <!-- Notes -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Additional Notes</h5>
                        <div class="bg-light p-3 rounded">
                            {{ $prescription->notes }}
                        </div>
                    </div>
                </div>
                @endif

                @if($prescription->ai_suggestions && count($prescription->ai_suggestions) > 0)
                <!-- AI Suggestions -->
                <div class="table-card mb-4">
                    <div class="bg-info text-white p-4 rounded-top">
                        <h4 class="mb-0 fw-bold">
                            <i class="fas fa-robot me-2"></i>AI-Generated Suggestions
                        </h4>
                    </div>
                    <div class="p-4">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>AI Assistance:</strong> This prescription was created with the help of our AI system, which analyzed your medical information and provided additional recommendations.
                        </div>

                        <h6 class="mb-3 text-primary">AI Recommended Alternatives/Considerations:</h6>
                        @foreach($prescription->ai_suggestions as $suggestion)
                            <div class="card mb-3 border-primary">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $suggestion['med'] ?? $suggestion }}</h6>
                                            <small class="text-muted">{{ $suggestion['reason'] ?? 'AI recommended alternative' }}</small>
                                        </div>
                                        @if(isset($suggestion['confidence']))
                                            <span class="badge bg-primary ms-2">
                                                {{ $suggestion['confidence'] }}% Confidence
                                            </span>
                                        @endif
                                    </div>
                                    @if(isset($suggestion['dosage']) && $suggestion['dosage'] !== 'N/A')
                                        <div class="row text-small">
                                            <div class="col-md-4">
                                                <strong>Dosage:</strong><br>
                                                <span class="text-muted">{{ $suggestion['dosage'] }}</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Frequency:</strong><br>
                                                <span class="text-muted">{{ $suggestion['freq'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Duration:</strong><br>
                                                <span class="text-muted">{{ $suggestion['dur'] ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($prescription->ai_risk_flags && count($prescription->ai_risk_flags) > 0)
                <!-- AI Risk Flags -->
                <div class="table-card mb-4">
                    <div class="bg-warning text-dark p-4 rounded-top">
                        <h4 class="mb-0 fw-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>AI-Identified Considerations
                        </h4>
                    </div>
                    <div class="p-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Our AI system identified these additional considerations for your safety and care.
                        </div>

                        <ul class="list-group list-group-flush">
                            @foreach($prescription->ai_risk_flags as $risk)
                                <li class="list-group-item">
                                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                    {{ $risk }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="{{ route('prescriptions.show', $prescription) }}?pdf=1" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i>Download PDF
                            </a>
                            @if($prescription->doctor)
                                <a href="mailto:{{ $prescription->doctor->email }}?subject=Question about Prescription #{{ $prescription->id }}" class="btn btn-outline-primary">
                                    <i class="fas fa-envelope me-2"></i>Contact Doctor
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="table-card">
                    <div class="p-4">
                        <h5 class="mb-3">Important Information</h5>
                        <div class="alert alert-light border">
                            <small>
                                <strong>Prescription ID:</strong> #{{ $prescription->id }}<br>
                                <strong>Issued:</strong> {{ $prescription->created_at->format('F j, Y') }}<br>
                                <strong>Valid for dispensing according to local regulations.</strong><br><br>
                                <em>Please consult your healthcare provider before making any changes to your medication regimen.</em>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection