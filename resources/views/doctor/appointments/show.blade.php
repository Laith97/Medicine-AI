@extends('master')

@section('title', 'Appointment Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('doctor.appointments.index') }}" class="btn btn-secondary-custom me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                </a>
                <div>
                    <h2 class="h1 mb-1">Appointment Details</h2>
                    <p>ID: #{{ $appointment->id }}</p>
                </div>
            </div>

            @php
                $statusColors = [
                    'pending' => 'bg-warning',
                    'confirmed' => 'bg-success',
                    'completed' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    'no_show' => 'bg-secondary'
                ];
            @endphp
            <span class="badge {{ $statusColors[$appointment->status] ?? 'bg-secondary' }} fs-6">
                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
            </span>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Appointment Overview -->
                <div class="table-card mb-4">
                    <div class="bg-primary text-white p-4 rounded-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">{{ $appointment->appointment_date->format('l, F j, Y') }}</h3>
                                <p class="mb-0 opacity-75">{{ $appointment->appointment_date->format('g:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <div class="h2 mb-0">30</div>
                                <small class="opacity-75">minutes</small>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-calendar text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Appointment Type</h6>
                                        <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                        <i class="fas fa-dollar-sign text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Consultation Fee</h6>
                                        <small class="text-muted">${{ number_format($appointment->consultation_fee / 100, 2) }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Information -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-4">Your Doctor</h5>
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                @if($appointment->doctor->profile_image)
                                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                         alt="{{ $appointment->doctor->user->name }}"
                                         class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                         style="width: 64px; height: 64px;">
                                        <i class="fas fa-user-md text-primary"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $appointment->doctor->user->name }}</h5>
                                <p class="text-primary mb-2">{{ $appointment->doctor->specialty->name }}</p>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="text-warning me-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($appointment->doctor->average_rating ?? 0))
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <small class="text-muted">{{ number_format($appointment->doctor->average_rating ?? 0, 1) }} ({{ $appointment->doctor->reviews_count ?? 0 }} reviews)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Patient Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Name</p>
                                <p class="mb-3">{{ $appointment->patient_name }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Email</p>
                                <p class="mb-3">{{ $appointment->patient_email }}</p>
                            </div>
                            @if($appointment->patient_phone)
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Phone</p>
                                    <p class="mb-3">{{ $appointment->patient_phone }}</p>
                                </div>
                            @endif
                            @if($appointment->isGuestAppointment() && $appointment->guest_date_of_birth)
                                <div class="col-md-6">
                                    <p class="text-muted mb-1">Date of Birth</p>
                                    <p class="mb-3">{{ \Carbon\Carbon::parse($appointment->guest_date_of_birth)->format('F j, Y') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Reason for Visit -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Reason for Visit</h5>
                        <p class="mb-0">{{ $appointment->reason }}</p>
                    </div>
                </div>

                <!-- Prescriptions Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div class="table-card mb-4">
                    <div class="bg-success text-white p-4 rounded-top">
                        <h4 class="mb-0 fw-bold">Prescriptions</h4>
                    </div>
                    <div class="p-4">
                        @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                            <h5 class="mb-3">Existing Prescriptions</h5>
                            @foreach($appointment->prescriptions as $prescription)
                                <div class="card mb-3" data-prescription-id="{{ $prescription->id }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0 fw-bold">{{ $prescription->medication_name }}</h6>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('prescriptions.show', $prescription->id) }}?pdf=1" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-download me-1"></i>Download PDF
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deletePrescription({{ $prescription->id }}, '{{ $prescription->medication_name }}')">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row text-small mb-2">
                                            <div class="col-md-3">
                                                <strong>Dosage:</strong><br>
                                                <span class="text-muted">{{ $prescription->dosage }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Form:</strong><br>
                                                <span class="text-muted">{{ ucfirst($prescription->form ?? 'N/A') }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Route:</strong><br>
                                                <span class="text-muted">{{ ucfirst($prescription->route ?? 'N/A') }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Quantity:</strong><br>
                                                <span class="text-muted">{{ $prescription->quantity ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div class="row text-small mb-2">
                                            <div class="col-md-3">
                                                <strong>Frequency:</strong><br>
                                                <span class="text-muted">{{ $prescription->frequency }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Duration:</strong><br>
                                                <span class="text-muted">{{ $prescription->duration }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Refills:</strong><br>
                                                <span class="text-muted">{{ $prescription->refills ?? 0 }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Created:</strong><br>
                                                <span class="text-muted">{{ $prescription->created_at->format('M j, Y') }}</span>
                                            </div>
                                        </div>
                                        @if($prescription->indication || $prescription->start_date || $prescription->generic_allowed !== null)
                                        <div class="row text-small mb-2">
                                            @if($prescription->indication)
                                            <div class="col-md-4">
                                                <strong>Indication:</strong><br>
                                                <span class="text-muted">{{ $prescription->indication }}</span>
                                            </div>
                                            @endif
                                            @if($prescription->start_date)
                                            <div class="col-md-4">
                                                <strong>Start Date:</strong><br>
                                                <span class="text-muted">{{ $prescription->start_date->format('M j, Y') }}</span>
                                            </div>
                                            @endif
                                            <div class="col-md-4">
                                                <strong>Generic Allowed:</strong><br>
                                                <span class="text-muted">{{ $prescription->generic_allowed ? 'Yes' : 'No' }}</span>
                                            </div>
                                        </div>
                                        @endif
                                        @if($prescription->instructions)
                                            <hr class="my-2">
                                            <p class="mb-0 text-muted small"><strong>Instructions:</strong> {{ $prescription->instructions }}</p>
                                        @endif
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
                                    <select class="form-select" id="form" name="form" required>
                                        <option value="">Select form</option>
                                        <option value="tablet">Tablet</option>
                                        <option value="capsule">Capsule</option>
                                        <option value="liquid">Liquid</option>
                                        <option value="injection">Injection</option>
                                        <option value="cream">Cream/Ointment</option>
                                        <option value="inhaler">Inhaler</option>
                                        <option value="patch">Patch</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="route" class="form-label fw-semibold">Route <span class="text-danger">*</span></label>
                                    <select class="form-select" id="route" name="route" required>
                                        <option value="">Select route</option>
                                        <option value="oral">Oral</option>
                                        <option value="topical">Topical</option>
                                        <option value="intravenous">Intravenous</option>
                                        <option value="intramuscular">Intramuscular</option>
                                        <option value="subcutaneous">Subcutaneous</option>
                                        <option value="inhalation">Inhalation</option>
                                        <option value="rectal">Rectal</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="quantity" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" placeholder="e.g., 30" min="1" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="frequency" class="form-label fw-semibold">Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select" id="frequency" name="frequency" required>
                                        <option value="">Select frequency</option>
                                        <option value="once daily">Once daily</option>
                                        <option value="twice daily">Twice daily</option>
                                        <option value="three times daily">Three times daily</option>
                                        <option value="four times daily">Four times daily</option>
                                        <option value="every 6 hours">Every 6 hours</option>
                                        <option value="every 8 hours">Every 8 hours</option>
                                        <option value="every 12 hours">Every 12 hours</option>
                                        <option value="as needed">As needed</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="duration" class="form-label fw-semibold">Duration <span class="text-danger">*</span></label>
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
                                <div class="col-md-3">
                                    <label for="refills" class="form-label fw-semibold">Refills</label>
                                    <input type="number" class="form-control" id="refills" name="refills" placeholder="0" min="0" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label fw-semibold">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                                <div class="col-md-6">
                                    <label for="indication" class="form-label fw-semibold">Indication</label>
                                    <input type="text" class="form-control" id="indication" name="indication" placeholder="e.g., Hypertension">
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="generic_allowed" name="generic_allowed" value="1" checked>
                                        <label class="form-check-label" for="generic_allowed">
                                            Allow generic substitution
                                        </label>
                                    </div>
                                </div>
                                @if(config('ai.prescription_suggestions.enabled', true))
                                    @include('ai.prescription_suggestion')
                                @endif
                                <div class="col-md-6">
                                    <label for="instructions" class="form-label fw-semibold">Specific Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="2" placeholder="e.g., Take with food, avoid alcohol"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="notes" class="form-label fw-semibold">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional instructions or special considerations..."></textarea>
                                </div>
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
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">Quick Actions</h5>
                        <div class="d-grid gap-2">
                            @if($appointment->canBeCancelled())
                                <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Cancel Appointment
                                </button>
                            @endif

                            @if($appointment->status == 'confirmed' && $appointment->appointment_type == 'video_call')
                                <button class="btn btn-success">
                                    <i class="fas fa-video me-2"></i>Join Video Call
                                </button>
                            @endif

                            @if($appointment->status == 'completed' && !Auth::user()->isDoctor())
                                <a href="{{ route('appointments.review', $appointment) }}" class="btn btn-warning">
                                    <i class="fas fa-star me-2"></i>Leave Review
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Appointment Timeline -->
                <div class="table-card">
                    <div class="p-4">
                        <h5 class="mb-3">Timeline</h5>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Appointment Booked</h6>
                                    <small class="text-muted">{{ $appointment->created_at->format('M j, Y g:i A') }}</small>
                                </div>
                            </div>

                            @if($appointment->status != 'pending')
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Status Updated</h6>
                                        <small class="text-muted">{{ ucfirst($appointment->status) }}</small>
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
@endsection

@push('scripts')
<script>
function cancelAppointment(appointmentId) {
    const form = document.getElementById('cancelForm');
    form.action = `/appointments/${appointmentId}/cancel`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function submitCancellation() {
    document.getElementById('cancelForm').submit();
}

// Prescription delete functionality
let prescriptionToDelete = null;

function deletePrescription(prescriptionId, medicationName) {
    prescriptionToDelete = prescriptionId;
    document.getElementById('deletePrescriptionName').textContent = medicationName;
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

    // Initialize form transformation
    initializeFormTransformation();
});

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
</script>
@endpush
