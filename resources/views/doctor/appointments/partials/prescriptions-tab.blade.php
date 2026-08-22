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

