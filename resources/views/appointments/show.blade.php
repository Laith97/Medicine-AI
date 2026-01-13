@extends('master')

@section('title', 'Appointment Details')

@section('content')
@php
    $patient = $appointment->patient;
@endphp
<div class="dashboard-container">
    <div class="container-fluid">

        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                    <!-- Back Button & Title -->
                    <div class="d-flex align-items-center mb-3 mb-md-0">
                        <a href="{{ route('appointments.index') }}" class="btn btn-secondary-custom me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                        </a>
                        <div>
                            <h1 class="h2 mb-1 fw-bold">Appointment Details</h1>
                            <small class="text-muted">ID: #{{ $appointment->id }}</small>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    @php
                        $statusClasses = [
                            'pending' => 'bg-warning text-dark',
                            'confirmed' => 'bg-success text-white',
                            'completed' => 'bg-primary text-white',
                            'cancelled' => 'bg-danger text-white',
                            'no_show' => 'bg-secondary text-white'
                        ];
                        $statusClass = $statusClasses[$appointment->status] ?? 'bg-secondary text-white';
                    @endphp
                    <span class="badge {{ $statusClass }} px-3 py-2 rounded-pill fs-6">
                        <i class="fas fa-circle me-2" style="font-size: 0.5rem;"></i>
                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Left Column - Main Content -->
            <div class="col-lg-8">

                <!-- Appointment Overview Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title mb-1 fw-bold">{{ $appointment->appointment_date->format('l, F j, Y') }}</h3>
                                <p class="mb-0 opacity-75">{{ $appointment->appointment_date->format('g:i A') }} - {{ $appointment->appointment_end->format('g:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <div class="h2 mb-0 fw-bold">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}</div>
                                <small class="opacity-75">minutes</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                        <i class="fas fa-calendar-alt text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-semibold mb-1">Appointment Type</h6>
                                        <p class="text-muted mb-0">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                                        <i class="fas fa-dollar-sign text-success fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-semibold mb-1">Consultation Fee</h6>
                                        <p class="text-muted mb-0">${{ number_format($appointment->consultation_fee / 100, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Information Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4 fw-bold">Your Doctor</h4>

                        <div class="d-flex align-items-start">
                            <!-- Doctor Avatar -->
                            <div class="me-4 flex-shrink-0">
                                @if($appointment->doctor->profile_image)
                                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                         alt="{{ $appointment->doctor->user->name }}"
                                         class="rounded-3 border" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <i class="fas fa-user-md text-white fs-2"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Doctor Details -->
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1">{{ $appointment->doctor->user->name }}</h5>
                                <p class="text-primary fw-semibold mb-2">{{ $appointment->doctor->specialty->name }}</p>

                                <!-- Rating -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="text-warning me-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($appointment->doctor->average_rating))
                                                <i class="fas fa-star"></i>
                                            @elseif($i - 0.5 <= $appointment->doctor->average_rating)
                                                <i class="fas fa-star-half-alt"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-muted">
                                        {{ number_format($appointment->doctor->average_rating, 1) }} ({{ $appointment->doctor->total_reviews }} reviews)
                                    </span>
                                </div>

                                <!-- Contact Actions -->
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-user me-1"></i>View Profile
                                    </a>
                                    @if($appointment->doctor->phone)
                                        <a href="tel:{{ $appointment->doctor->phone }}" class="btn btn-success btn-sm">
                                            <i class="fas fa-phone me-1"></i>Call Doctor
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointment Information Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4 fw-bold">Appointment Information</h4>

                        <!-- Reason for Visit -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-clipboard-list text-info"></i>
                                </div>
                                <h6 class="fw-semibold mb-0">Reason for Visit</h6>
                            </div>
                            <div class="bg-light rounded-3 p-3 ms-5">
                                <p class="mb-0">{{ $appointment->reason }}</p>
                            </div>
                        </div>

                        @if($appointment->symptoms)
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    </div>
                                    <h6 class="fw-semibold mb-0">Symptoms</h6>
                                </div>
                                <div class="bg-light rounded-3 p-3 ms-5">
                                    <p class="mb-0">{{ $appointment->symptoms }}</p>
                                </div>
                            </div>
                        @endif

                        @if($appointment->patient_notes)
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-secondary bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-sticky-note text-secondary"></i>
                                    </div>
                                    <h6 class="fw-semibold mb-0">Additional Notes</h6>
                                </div>
                                <div class="bg-light rounded-3 p-3 ms-5">
                                    <p class="mb-0">{{ $appointment->patient_notes }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Doctor's Assessment (if completed) -->
                @if($appointment->status == 'completed' && $appointment->doctor_notes)
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="fas fa-user-md text-primary fs-4"></i>
                                </div>
                                <h4 class="card-title mb-0 fw-bold">Doctor's Assessment</h4>
                            </div>

                            <div class="bg-primary bg-opacity-5 rounded-3 p-4">
                                <p class="mb-0 fs-5">{{ $appointment->doctor_notes }}</p>
                            </div>

                            @if($appointment->follow_up_required)
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Follow-up appointment recommended</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Prescriptions Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="card-title mb-0 fw-bold">Prescriptions</h4>
                    </div>
                    <div class="card-body">
                        @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                            <h5 class="mb-3">Existing Prescriptions</h5>
                            @foreach($appointment->prescriptions as $prescription)
                                <div class="card mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0 fw-bold">{{ $prescription->medication_name }}</h6>
                                            <a href="{{ route('prescriptions.show', $prescription->id) }}?pdf=1" class="btn btn-primary btn-sm">
                                                <i class="fas fa-download me-1"></i>Download PDF
                                            </a>
                                        </div>
                                        <div class="row text-small">
                                            <div class="col-md-3">
                                                <strong>Dosage:</strong><br>
                                                <span class="text-muted">{{ $prescription->dosage }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Frequency:</strong><br>
                                                <span class="text-muted">{{ $prescription->frequency }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Duration:</strong><br>
                                                <span class="text-muted">{{ $prescription->duration }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Created:</strong><br>
                                                <span class="text-muted">{{ $prescription->created_at->format('M j, Y') }}</span>
                                            </div>
                                        </div>
                                        @if($prescription->notes)
                                            <hr class="my-2">
                                            <p class="mb-0 text-muted small"><strong>Notes:</strong> {{ $prescription->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-prescription-bottle-alt text-muted mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                                <p class="text-muted">No prescriptions have been added for this appointment yet.</p>
                            </div>
                        @endif
                
                        <hr class="my-4">
                
                        <h5 class="mb-3">Add New Prescription</h5>
                
                        <form id="prescriptionForm" method="POST" action="{{ route('doctor.prescriptions.store', $appointment->id) }}">
                            @csrf
                            <input type="hidden" name="ai_suggestions" id="ai_suggestions" value="">
                            <input type="hidden" name="ai_risk_flags" id="ai_risk_flags" value="">
                
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="medication_name" class="form-label fw-semibold">Medication Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="medication_name" name="medication_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="dosage" class="form-label fw-semibold">Dosage <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="dosage" name="dosage" placeholder="e.g., 500mg" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="form" class="form-label fw-semibold">Form <span class="text-danger">*</span></label>
                                    <select class="form-control" id="form" name="form" required>
                                        <option value="">Select Form</option>
                                        <option value="tablet">Tablet</option>
                                        <option value="capsule">Capsule</option>
                                        <option value="liquid">Liquid</option>
                                        <option value="injection">Injection</option>
                                        <option value="cream">Cream/Ointment</option>
                                        <option value="inhaler">Inhaler</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="route" class="form-label fw-semibold">Route <span class="text-danger">*</span></label>
                                    <select class="form-control" id="route" name="route" required>
                                        <option value="">Select Route</option>
                                        <option value="oral">Oral</option>
                                        <option value="topical">Topical</option>
                                        <option value="intravenous">Intravenous</option>
                                        <option value="intramuscular">Intramuscular</option>
                                        <option value="subcutaneous">Subcutaneous</option>
                                        <option value="inhalation">Inhalation</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="quantity" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" placeholder="e.g., 30" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="frequency" class="form-label fw-semibold">Frequency <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="frequency" name="frequency" placeholder="e.g., twice daily" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="duration" class="form-label fw-semibold">Duration <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="duration" name="duration" placeholder="e.g., 7 days" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="refills" class="form-label fw-semibold">Refills</label>
                                    <input type="number" class="form-control" id="refills" name="refills" min="0" placeholder="0" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label fw-semibold">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">&nbsp;</label>
                                    <button type="button" id="aiSuggestBtn" class="btn btn-outline-info w-100">
                                        <i class="fas fa-magic me-1"></i>Suggest with AI
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <label for="indication" class="form-label fw-semibold">Indication</label>
                                    <input type="text" class="form-control" id="indication" name="indication" placeholder="e.g., Hypertension">
                                </div>
                                <div class="col-md-6">
                                    <label for="generic_allowed" class="form-label fw-semibold">Generic Allowed</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="generic_allowed" name="generic_allowed" value="1" checked>
                                        <label class="form-check-label" for="generic_allowed">
                                            Allow generic substitution
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="instructions" class="form-label fw-semibold">Patient Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="2" placeholder="Specific instructions for the patient..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional instructions or special considerations..."></textarea>
                                </div>
                            </div>
                
                            <div id="ai-suggestions" class="mb-3 p-3 bg-light border rounded" style="display: none;"></div>
                            <div id="ai-risks" class="alert alert-warning mb-3" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Potential Risks:</strong> <span id="risks-content"></span>
                            </div>
                
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Save Prescription
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetPrescriptionForm()">
                                    <i class="fas fa-undo me-1"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Prescriptions Section for Patients -->
                @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="fas fa-prescription-bottle text-success fs-4"></i>
                                </div>
                                <h4 class="card-title mb-0 fw-bold">Your Prescriptions</h4>
                            </div>

                            @foreach($appointment->prescriptions as $prescription)
                                <div class="card mb-3 border-success">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1 fw-bold text-success">{{ $prescription->medication_name }}</h6>
                                                <small class="text-muted">Prescribed on {{ $prescription->created_at->format('M j, Y') }}</small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                                <a href="{{ route('prescriptions.show', $prescription) }}?pdf=1" class="btn btn-success btn-sm">
                                                    <i class="fas fa-download me-1"></i>Download PDF
                                                </a>
                                            </div>
                                        </div>

                                        <div class="row text-small">
                                            <div class="col-md-3">
                                                <strong>Dosage:</strong><br>
                                                <span class="text-muted">{{ $prescription->dosage }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Frequency:</strong><br>
                                                <span class="text-muted">{{ $prescription->frequency }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Duration:</strong><br>
                                                <span class="text-muted">{{ $prescription->duration }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Status:</strong><br>
                                                <span class="badge bg-success">Active</span>
                                            </div>
                                        </div>

                                        @if($prescription->notes)
                                            <hr class="my-2">
                                            <p class="mb-0 text-muted small"><strong>Notes:</strong> {{ $prescription->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Important:</strong> Click "View Details" to see complete prescription information including any AI recommendations and safety considerations.
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Review Section -->
                @if($appointment->status == 'completed')
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-warning bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="fas fa-star text-warning fs-4"></i>
                                </div>
                                <h4 class="card-title mb-0 fw-bold">Your Review</h4>
                            </div>

                            @if($appointment->review)
                                <div class="bg-success bg-opacity-5 rounded-3 p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="text-warning me-3">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $appointment->review->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <small class="text-muted">
                                            Reviewed on {{ $appointment->review->created_at->format('M j, Y') }}
                                        </small>
                                    </div>
                                    @if($appointment->review->comment)
                                        <p class="mb-3">{{ $appointment->review->comment }}</p>
                                    @endif
                                    <a href="{{ route('reviews.show', $appointment->review) }}" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-eye me-1"></i>View full review
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="fas fa-star text-warning fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">How was your appointment?</h5>
                                    <p class="text-muted mb-4">Share your experience to help other patients</p>
                                    <a href="{{ route('appointments.review', $appointment) }}" class="btn btn-warning">
                                        <i class="fas fa-star me-2"></i>Leave a Review
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Column - Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions Card -->
                <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bolt text-primary me-2"></i>
                            <h5 class="card-title mb-0 fw-bold">Quick Actions</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            @if(in_array($appointment->status, ['pending', 'confirmed']) && $appointment->appointment_type == 'video_call')
                                <button onclick="joinVideoCall()" class="btn btn-primary">
                                    <i class="fas fa-video me-2"></i>Join Video Call
                                </button>
                            @endif

                            @if(auth()->check() && auth()->user()->isDoctor())
                                <!-- AI Medical Copilot Button -->
                                <button onclick="openAIMedicalCopilot({{ $appointment->id }})" class="btn btn-info">
                                    <i class="fas fa-brain me-2"></i>AI Medical Copilot
                                </button>

                                <!-- View Patient AI Analyses Button -->
                                <button onclick="viewPatientAIAnalyses({{ $patient->id }})" class="btn btn-outline-info">
                                    <i class="fas fa-history me-2"></i>View AI History
                                </button>
                            @endif

                            @if($appointment->canBeRescheduled())
                                <button onclick="rescheduleAppointment()" class="btn btn-warning">
                                    <i class="fas fa-calendar-alt me-2"></i>Reschedule
                                </button>
                            @endif

                            @if($appointment->canBeCancelled())
                                <button onclick="showCancelModal()" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Cancel Appointment
                                </button>
                            @endif

                            <hr>

                            <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-user-md me-2"></i>View Doctor Profile
                            </a>

                            @if($appointment->doctor->phone)
                                <a href="tel:{{ $appointment->doctor->phone }}" class="btn btn-success">
                                    <i class="fas fa-phone me-2"></i>Call Doctor
                                </a>
                            @endif
                        </div>

                        <!-- Appointment Summary -->
                        <hr class="my-4">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-info-circle text-muted me-2"></i>
                                <h6 class="fw-semibold mb-0">Summary</h6>
                            </div>
                            <div class="small">
                                <div class="d-flex justify-content-between py-2 px-3 bg-light rounded mb-2">
                                    <span class="text-muted">Consultation Fee</span>
                                    <span class="fw-semibold">${{ number_format($appointment->consultation_fee / 100, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 px-3 bg-light rounded mb-2">
                                    <span class="text-muted">Booked on</span>
                                    <span class="fw-medium">{{ $appointment->created_at->format('M j, Y') }}</span>
                                </div>
                                @if($appointment->cancelled_at)
                                    <div class="d-flex justify-content-between py-2 px-3 bg-danger bg-opacity-10 rounded">
                                        <span class="text-danger">Cancelled on</span>
                                        <span class="fw-medium text-danger">{{ $appointment->cancelled_at->format('M j, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preparation Tips Card -->
                @if(in_array($appointment->status, ['pending', 'confirmed']))
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lightbulb me-2"></i>
                                <h6 class="card-title mb-0 fw-bold">Preparation Tips</h6>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @if($appointment->appointment_type == 'in_person')
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Arrive 15 minutes early</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Bring valid ID and insurance card</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Wear a mask if required</span>
                                    </li>
                                @elseif($appointment->appointment_type == 'video_call')
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Test your camera and microphone</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Ensure stable internet connection</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Join the call 5 minutes early</span>
                                    </li>
                                @else
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Ensure your phone is charged</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Be in a quiet location</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                        <span class="small">Have your medical history ready</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 rounded-3 p-2 me-3">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="cancelModalLabel">Cancel Appointment</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Warning Message -->
                <div class="alert alert-danger">
                    <strong>Are you sure you want to cancel this appointment?</strong><br>
                    This action cannot be undone and you may need to reschedule for a later date.
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" id="cancelForm">
                    @csrf
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label fw-semibold">
                            Reason for cancellation (optional)
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason" rows="4"
                                  class="form-control"
                                  placeholder="Please let us know why you're cancelling this appointment..."></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Keep Appointment
                </button>
                <button type="submit" form="cancelForm" class="btn btn-danger">
                    Cancel Appointment
                </button>
            </div>
        </div>
    </div>
</div>

@include('ai.medical_copilot')

<script>
// Modal Functions
function showCancelModal() {
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function rescheduleAppointment() {
    showNotification('Reschedule feature coming soon!', 'info');
}

function joinVideoCall() {
    showNotification('Launching video call...', 'success');
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
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${icons[type]} me-2"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

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

    // Prescription AI Suggestion
    $('#aiSuggestBtn').click(function(e) {
        e.preventDefault();
        
        var button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...');
        
        var symptoms = @json($appointment->symptoms ?? data_get($patient, 'patient_data.symptoms', ''));
        var allergies = @json(data_get($patient, 'patient_data.allergies', []));
        var pastMeds = @json(data_get($patient, 'patient_data.past_medications', []));
        
        $.ajax({
            url: "{{ route('ai.appointments.suggest', $appointment->id) }}",
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                symptoms: symptoms,
                allergies: JSON.stringify(allergies),
                past_meds: JSON.stringify(pastMeds)
            },
            success: function(response) {
                button.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Suggest with AI');

                // Debug logging
                console.log('AI Response:', response);
                console.log('Response suggestions:', response.suggestions);
                console.log('Response risk_flags:', response.risk_flags);

                // Suggestions
                if (response.suggestions && response.suggestions.length > 0) {
                    var suggestionsHtml = '<h6 class="mb-2 text-primary">AI Suggested Medications:</h6><ul class="list-unstyled">';
                    $.each(response.suggestions, function(i, suggestion) {
                        console.log('Processing suggestion ' + i + ':', suggestion);
                        console.log('Suggestion type:', typeof suggestion);
                        console.log('Suggestion keys:', suggestion ? Object.keys(suggestion) : 'null/undefined');

                        // More flexible parsing - try different possible key names
                        var medName = suggestion.med || suggestion.medication || suggestion.name || suggestion.drug || '';
                        var dosage = suggestion.dosage || suggestion.dose || '';
                        var frequency = suggestion.freq || suggestion.frequency || '';
                        var duration = suggestion.dur || suggestion.duration || '';

                        if (medName) {
                            var medDetails = medName;
                            if (dosage) medDetails += ' (' + dosage + ')';
                            if (frequency) medDetails += ' - ' + frequency;
                            if (duration) medDetails += ' for ' + duration;
                            suggestionsHtml += '<li class="p-2 bg-white border rounded mb-1" onclick="useSuggestion(' + i + ')" style="cursor: pointer;">' + medDetails + '</li>';
                        } else {
                            console.log('Suggestion ' + i + ' has no valid medication name, skipping');
                        }
                    });
                    suggestionsHtml += '</ul>';
                    suggestionsHtml += '<small class="text-muted">Click a suggestion to use it in the form</small>';
                    $('#ai-suggestions').html(suggestionsHtml).show();

                    // Store suggestions for use in form
                    window.aiSuggestions = response.suggestions;
                    $('#ai_suggestions').val(JSON.stringify(response.suggestions));
                } else {
                    console.log('No suggestions found in response');
                    $('#ai-suggestions').html('<div class="p-3 text-muted">No AI suggestions available at this time.</div>').show();
                    $('#ai_suggestions').val('');
                    window.aiSuggestions = [];
                }
                
                // Risks
                console.log('Processing risk_flags:', response.risk_flags);
                if (response.risk_flags && Array.isArray(response.risk_flags) && response.risk_flags.length > 0) {
                    var risksText = response.risk_flags.join(', ');
                    console.log('Risk flags text:', risksText);
                    $('#risks-content').text(risksText);
                    $('#ai-risks').show();
                    $('#ai_risk_flags').val(JSON.stringify(response.risk_flags));
                } else {
                    console.log('No valid risk_flags found');
                    $('#ai-risks').hide();
                    $('#ai_risk_flags').val('');
                }
                
                showNotification('AI analysis complete!', 'success');
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Suggest with AI');
                
                var msg = 'Failed to get AI suggestions. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showNotification(msg, 'error');
                
                $('#ai-suggestions').hide();
                $('#ai-risks').hide();
            }
        });
    });

    // Use AI suggestion in form
    window.useSuggestion = function(index) {
        if (window.aiSuggestions && window.aiSuggestions[index]) {
            var suggestion = window.aiSuggestions[index];
            // Use flexible key matching
            var medName = suggestion.med || suggestion.medication || suggestion.name || suggestion.drug || '';
            var dosage = suggestion.dosage || suggestion.dose || '';
            var frequency = suggestion.freq || suggestion.frequency || '';
            var duration = suggestion.dur || suggestion.duration || '';

            $('#medication_name').val(medName);
            $('#dosage').val(dosage);
            $('#frequency').val(frequency);
            $('#duration').val(duration);
            showNotification('Suggestion applied to form.', 'success');
        }
    };

    // Reset form function
    window.resetPrescriptionForm = function() {
        $('#prescriptionForm')[0].reset();
        $('#ai-suggestions').hide();
        $('#ai-risks').hide();
        $('#ai_suggestions').val('');
        $('#ai_risk_flags').val('');
        window.aiSuggestions = [];
        // Reset form field back to select if it was transformed
        resetFormField();
        showNotification('Form reset.', 'info');
    };

    // Dynamic form transformation
    function initializeFormTransformation() {
        const formSelect = document.getElementById('form');
        if (!formSelect) return;

        formSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this);
            } else {
                ensureSelectField(this);
            }
        });
    }

    function transformToTextInput(selectElement) {
        const parent = selectElement.parentElement;
        const currentValue = selectElement.value;

        // Create text input
        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = 'form-control';
        textInput.id = 'form';
        textInput.name = 'form';
        textInput.required = true;
        textInput.placeholder = 'Enter custom form (e.g., Suppository, Patch)';

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

    function ensureSelectField(currentElement) {
        if (currentElement.tagName === 'SELECT') return;

        const parent = currentElement.parentElement;
        const currentValue = currentElement.value;

        // Create select element
        const selectElement = document.createElement('select');
        selectElement.className = 'form-control';
        selectElement.id = 'form';
        selectElement.name = 'form';
        selectElement.required = true;

        // Add options
        const options = [
            { value: '', text: 'Select Form' },
            { value: 'tablet', text: 'Tablet' },
            { value: 'capsule', text: 'Capsule' },
            { value: 'liquid', text: 'Liquid' },
            { value: 'injection', text: 'Injection' },
            { value: 'cream', text: 'Cream/Ointment' },
            { value: 'inhaler', text: 'Inhaler' },
            { value: 'other', text: 'Other' }
        ];

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
        const formElement = document.getElementById('form');
        if (formElement && formElement.tagName !== 'SELECT') {
            ensureSelectField(formElement);
        }
    }

    // Initialize form transformation on page load
    initializeFormTransformation();
});
</script>
@endsection