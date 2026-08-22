@if(auth()->check() && auth()->user()->isDoctor())
<div class="doctor-empty-state text-center py-3 mb-4 d-none d-md-block">
    <small class="text-muted"><i class="fas fa-prescription-bottle me-1"></i> Prescriptions for appointment #{{ $appointment->id }} • {{ $appointment->patient_name }}</small>
</div>

@if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
<div class="doctor-card mb-4">
    <div class="doctor-card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list me-2 text-success"></i>Existing Prescriptions <span class="badge bg-success ms-2">{{ $appointment->prescriptions->count() }}</span></h5>
        <small class="text-muted">Patient medications</small>
    </div>
    <div class="doctor-card-body p-0">
        <div class="doctor-table-container">
            <table class="doctor-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-pills me-1"></i>Medication</th>
                        <th>Dosage</th>
                        <th>Schedule</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointment->prescriptions as $prescription)
                    <tr>
                        <td>
                            <strong>{{ $prescription->medication_name }}</strong>
                            <small class="d-block text-muted">{{ ucfirst($prescription->form ?? '-') }} • {{ ucfirst($prescription->route ?? '-') }} • Qty {{ $prescription->quantity }}</small>
                        </td>
                        <td><span class="doctor-badge doctor-badge-info">{{ $prescription->dosage }}</span></td>
                        <td><small>{{ $prescription->frequency }}<br><span class="text-muted">{{ $prescription->duration }}</span></small></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('prescriptions.show', $prescription->id) }}?pdf=1" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Download PDF"><i class="fas fa-download"></i></a>
                                <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm" style="color:var(--accent-danger); border-color:var(--accent-danger);" onclick="deletePrescription({{ $prescription->id }}, '{{ $prescription->medication_name }}')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @if($prescription->instructions)
                    <tr>
                        <td colspan="4" class="bg-light small"><strong>Instructions:</strong> {{ $prescription->instructions }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="doctor-empty-state text-center py-5 mb-4" style="background:var(--bg-primary); border:1px dashed var(--border-medium); border-radius:var(--radius-lg);">
    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;"><i class="fas fa-prescription-bottle text-success fa-lg"></i></div>
    <h6 class="fw-semibold">No prescriptions yet</h6>
    <p class="text-muted small">Add first prescription below</p>
</div>
@endif

<!-- Add New Prescription - Professional -->
<div class="doctor-card">
    <div class="doctor-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Prescription</h5>
        <div class="d-flex gap-2">
            <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm" data-bs-toggle="modal" data-bs-target="#prescriptionHelpModal"><i class="fas fa-question-circle me-1"></i>Help</button>
            @if(config('ai.prescription_suggestions.enabled', true) && $appointment->status=='completed')
            <span class="doctor-badge doctor-badge-warning"><i class="fas fa-brain me-1"></i>AI Assist Available</span>
            @endif
        </div>
    </div>
    <div class="doctor-card-body">
        <form id="prescriptionForm" method="POST" action="{{ route('doctor.prescriptions.store', $appointment->id) }}">
            @csrf

            <!-- Medication -->
            <div class="doctor-form-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-pills me-2 text-primary"></i>Medication</h6>
                    <span class="doctor-badge doctor-badge-danger">Required</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="doctor-form-label">Medication Name <span class="text-danger">*</span></label>
                        <input type="text" class="doctor-form-control form-control" id="medication_name" name="medication_name" placeholder="e.g., Amoxicillin" required>
                    </div>
                    <div class="col-md-3">
                        <label class="doctor-form-label">Dosage <span class="text-danger">*</span></label>
                        <input type="text" class="doctor-form-control form-control" id="dosage" name="dosage" placeholder="500mg" required>
                    </div>
                    <div class="col-md-3">
                        <label class="doctor-form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="doctor-form-control form-control" id="quantity" name="quantity" placeholder="30" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="doctor-form-label">Form <span class="text-danger">*</span></label>
                        <select class="doctor-form-control form-select" id="form" name="form" required>
                            <option value="">Select</option>
                            <option value="tablet">Tablet</option>
                            <option value="capsule">Capsule</option>
                            <option value="liquid">Liquid</option>
                            <option value="injection">Injection</option>
                            <option value="cream">Cream</option>
                            <option value="inhaler">Inhaler</option>
                            <option value="patch">Patch</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="doctor-form-label">Route <span class="text-danger">*</span></label>
                        <select class="doctor-form-control form-select" id="route" name="route" required>
                            <option value="">Select</option>
                            <option value="oral">Oral</option>
                            <option value="topical">Topical</option>
                            <option value="intravenous">IV</option>
                            <option value="intramuscular">IM</option>
                            <option value="subcutaneous">Sub-Q</option>
                            <option value="inhalation">Inhalation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="doctor-form-label">Refills</label>
                        <input type="number" class="doctor-form-control form-control" id="refills" name="refills" value="0" min="0">
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="doctor-form-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-clock me-2 text-primary"></i>Schedule</h6>
                    <span class="doctor-badge doctor-badge-danger">Required</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="doctor-form-label">Frequency <span class="text-danger">*</span></label>
                        <select class="doctor-form-control form-select" id="frequency" name="frequency" required>
                            <option value="">Select</option>
                            <option value="once daily">Once daily</option>
                            <option value="twice daily">Twice daily</option>
                            <option value="three times daily">Three times daily</option>
                            <option value="every 6 hours">Every 6h</option>
                            <option value="every 8 hours">Every 8h</option>
                            <option value="every 12 hours">Every 12h</option>
                            <option value="as needed">As needed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="doctor-form-label">Duration <span class="text-danger">*</span></label>
                        <select class="doctor-form-control form-select" id="duration" name="duration" required>
                            <option value="">Select</option>
                            <option value="3 days">3 days</option>
                            <option value="7 days">7 days</option>
                            <option value="14 days">14 days</option>
                            <option value="1 month">1 month</option>
                            <option value="3 months">3 months</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="doctor-form-label">Start Date</label>
                        <input type="date" class="doctor-form-control form-control" id="start_date" name="start_date">
                    </div>
                    <div class="col-md-6">
                        <label class="doctor-form-label">Indication</label>
                        <input type="text" class="doctor-form-control form-control" id="indication" name="indication" placeholder="e.g., Hypertension">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="generic_allowed" name="generic_allowed" value="1" checked>
                            <label class="form-check-label small fw-semibold" for="generic_allowed">Allow generic substitution</label>
                        </div>
                    </div>
                </div>
            </div>

            @if(config('ai.prescription_suggestions.enabled', true))
            <div class="doctor-form-section mb-4" style="background:var(--gray-50); border:1px dashed var(--border-medium); border-radius:var(--radius-md); padding:1rem;">
                <h6 class="mb-2"><i class="fas fa-brain me-2 text-warning"></i>AI Clinical Support <span class="doctor-badge doctor-badge-warning ms-2">Optional</span></h6>
                @include('ai.prescription_suggestion')
            </div>
            @endif

            <!-- Instructions -->
            <div class="doctor-form-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-sticky-note me-2 text-primary"></i>Instructions</h6>
                    <span class="doctor-badge doctor-badge-info">Recommended</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="doctor-form-label">Specific Instructions</label>
                        <textarea class="doctor-form-control form-control" id="instructions" name="instructions" rows="2" placeholder="Take with food, avoid alcohol..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="doctor-form-label">Additional Notes</label>
                        <textarea class="doctor-form-control form-control" id="notes" name="notes" rows="2" placeholder="Monitoring, allergies..."></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end pt-3 border-top">
                <button type="button" class="doctor-btn doctor-btn-outline" onclick="resetPrescriptionForm()"><i class="fas fa-undo me-1"></i>Reset</button>
                <button type="submit" class="doctor-btn doctor-btn-primary"><i class="fas fa-save me-1"></i>Save Prescription</button>
            </div>
            <small class="text-muted d-block text-end mt-2"><i class="fas fa-shield-alt me-1"></i>Requires clinical review</small>
        </form>
    </div>
</div>
@endif
