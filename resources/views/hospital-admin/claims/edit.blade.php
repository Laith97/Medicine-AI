@extends('layouts.app')

@section('page-title', 'Edit Claim')

@push('styles')
<style>
    .ai-suggestions { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin-top: 10px; }
    .suggestion-item { background-color: white; border: 1px solid #e9ecef; border-radius: 3px; padding: 8px; margin-bottom: 5px; }
    .confidence-high { border-left: 4px solid #28a745; }
    .confidence-medium { border-left: 4px solid #ffc107; }
    .confidence-low { border-left: 4px solid #dc3545; }
    .risk-tooltip { cursor: help; }
    .form-section { margin-bottom: 2rem; padding: 1.5rem; border: 1px solid #e9ecef; border-radius: 5px; }
    .section-header { border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Claim</h1>
                    <p class="text-muted">Update claim details with AI-powered assistance</p>
                </div>
                <div>
                    <a href="{{ route('hospital-admin.claims.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Claims
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form id="claimForm" method="POST" action="{{ route('hospital-admin.claims.update', $claim) }}">
                @csrf
                @method('PUT')

                <!-- Patient Information Section -->
                <div class="form-section">
                    <h4 class="section-header">
                        <i class="fas fa-user me-2"></i>Patient Information
                    </h4>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="patient_name" class="form-label">Patient Name *</label>
                            <input type="text" class="form-control" id="patient_name" name="patient_name" value="{{ $claim->patient_name ?? '' }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="patient_dob" class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control" id="patient_dob" name="patient_dob" value="{{ $claim->patient_dob ? $claim->patient_dob->format('Y-m-d') : '' }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="patient_gender" class="form-label">Gender</label>
                            <select class="form-select" id="patient_gender" name="patient_gender">
                                <option value="">Select Gender</option>
                                <option value="male" {{ ($claim->patient_gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ ($claim->patient_gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ ($claim->patient_gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="patient_insurance_id" class="form-label">Insurance ID</label>
                            <input type="text" class="form-control" id="patient_insurance_id" name="patient_insurance_id" value="{{ $claim->patient_insurance_id ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label for="patient_insurance_provider" class="form-label">Insurance Provider</label>
                            <input type="text" class="form-control" id="patient_insurance_provider" name="patient_insurance_provider" value="{{ $claim->patient_insurance_provider ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- Provider Information Section -->
                <div class="form-section">
                    <h4 class="section-header">
                        <i class="fas fa-user-md me-2"></i>Provider Information
                    </h4>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="provider_name" class="form-label">Provider Name *</label>
                            <input type="text" class="form-control" id="provider_name" name="provider_name" value="{{ $claim->provider_name ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="provider_npi" class="form-label">NPI Number</label>
                            <input type="text" class="form-control" id="provider_npi" name="provider_npi" value="{{ $claim->provider_npi ?? '' }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="service_date" class="form-label">Service Date *</label>
                            <input type="date" class="form-control" id="service_date" name="service_date" value="{{ $claim->service_date ? $claim->service_date->format('Y-m-d') : '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="facility_name" class="form-label">Facility Name</label>
                            <input type="text" class="form-control" id="facility_name" name="facility_name" value="{{ $claim->facility_name ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- Diagnosis and Procedure Section -->
                <div class="form-section">
                    <h4 class="section-header">
                        <i class="fas fa-stethoscope me-2"></i>Diagnosis & Procedures
                    </h4>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="diagnosis_description" class="form-label">Clinical Description *</label>
                            <textarea class="form-control" id="diagnosis_description" name="diagnosis_description" rows="3" required>{{ $claim->diagnosis_description ?? '' }}</textarea>
                            <div class="form-text">Provide detailed clinical information for accurate AI code suggestions</div>
                        </div>
                    </div>

                    <!-- AI Suggest Codes Button -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="button" id="aiSuggestCodes" class="btn btn-primary">
                                <i class="fas fa-brain me-2"></i>AI Suggest Codes
                            </button>
                            <small class="text-muted ms-2">Get ICD-10 and CPT code suggestions based on the clinical description</small>
                        </div>
                    </div>

                    <!-- AI Suggestions Display -->
                    <div id="aiSuggestions" class="ai-suggestions" style="display: none;">
                        <h6><i class="fas fa-lightbulb me-2"></i>AI Code Suggestions</h6>
                        <div id="suggestionsContent">
                            <!-- Suggestions will be populated here -->
                        </div>
                        <div class="mt-3">
                            <button type="button" id="applySuggestions" class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i>Apply Selected Codes
                            </button>
                            <button type="button" id="clearSuggestions" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Codes Section -->
                <div class="form-section">
                    <h4 class="section-header">
                        <i class="fas fa-code me-2"></i>Medical Codes
                    </h4>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="icd10_codes" class="form-label">ICD-10 Codes</label>
                            <textarea class="form-control" id="icd10_codes" name="icd10_codes" rows="3">{{ $claim->icd10_codes ?? '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="cpt_codes" class="form-label">CPT Codes</label>
                            <textarea class="form-control" id="cpt_codes" name="cpt_codes" rows="3">{{ $claim->cpt_codes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Billing Information Section -->
                <div class="form-section">
                    <h4 class="section-header">
                        <i class="fas fa-dollar-sign me-2"></i>Billing Information
                    </h4>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="total_amount" class="form-label">Total Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" value="{{ $claim->total_amount ?? '' }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="allowed_amount" class="form-label">Allowed Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="allowed_amount" name="allowed_amount" step="0.01" value="{{ $claim->allowed_amount ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="paid_amount" class="form-label">Paid Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="paid_amount" name="paid_amount" step="0.01" value="{{ $claim->paid_amount ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- Denial Risk Indicator -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div id="denialRiskIndicator" style="display: none;">
                                <div class="alert alert-warning">
                                    <h6>
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Denial Risk Assessment
                                        <i class="fas fa-info-circle risk-tooltip" data-bs-toggle="tooltip"
                                           data-bs-placement="top" title="Click for detailed explanation"></i>
                                    </h6>
                                    <div id="riskExplanation">
                                        <!-- Risk explanation will be populated here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="form-section">
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Update Claim
                            </button>
                            <button type="button" id="saveDraft" class="btn btn-outline-primary btn-lg ms-2">
                                <i class="fas fa-save me-2"></i>Save as Draft
                            </button>
                            <a href="{{ route('hospital-admin.claims.index') }}" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // AI Suggest Codes functionality (same as create form)
    document.getElementById('aiSuggestCodes').addEventListener('click', function() {
        const description = document.getElementById('diagnosis_description').value.trim();

        if (!description) {
            alert('Please enter a clinical description first.');
            return;
        }

        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Analyzing...';
        this.disabled = true;

        // Call AI API for code suggestions
        fetch('/api/ai/code-suggestions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                description: description,
                patient_info: {
                    age: calculateAge(document.getElementById('patient_dob').value),
                    gender: document.getElementById('patient_gender').value
                }
            })
        })
        .then(response => response.json())
        .then(data => {
            displayAISuggestions(data);
            checkDenialRisk(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error getting AI suggestions. Please try again.');
        })
        .finally(() => {
            // Reset button state
            this.innerHTML = '<i class="fas fa-brain me-2"></i>AI Suggest Codes';
            this.disabled = false;
        });
    });

    function displayAISuggestions(data) {
        const suggestionsDiv = document.getElementById('aiSuggestions');
        const contentDiv = document.getElementById('suggestionsContent');

        if (data.icd10_codes && data.icd10_codes.length > 0) {
            let html = '<div class="row"><div class="col-md-6"><h6>ICD-10 Codes:</h6>';
            data.icd10_codes.forEach(code => {
                const confidenceClass = getConfidenceClass(code.confidence);
                html += `
                    <div class="suggestion-item ${confidenceClass}">
                        <div class="form-check">
                            <input class="form-check-input icd10-checkbox" type="checkbox" value="${code.code}" id="icd10_${code.code}">
                            <label class="form-check-label" for="icd10_${code.code}">
                                <strong>${code.code}</strong> - ${code.description}
                                <br><small class="text-muted">Confidence: ${Math.round(code.confidence * 100)}%</small>
                            </label>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            if (data.cpt_codes && data.cpt_codes.length > 0) {
                html += '<div class="col-md-6"><h6>CPT Codes:</h6>';
                data.cpt_codes.forEach(code => {
                    const confidenceClass = getConfidenceClass(code.confidence);
                    html += `
                        <div class="suggestion-item ${confidenceClass}">
                            <div class="form-check">
                                <input class="form-check-input cpt-checkbox" type="checkbox" value="${code.code}" id="cpt_${code.code}">
                                <label class="form-check-label" for="cpt_${code.code}">
                                    <strong>${code.code}</strong> - ${code.description}
                                    <br><small class="text-muted">Confidence: ${Math.round(code.confidence * 100)}%</small>
                                </label>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            html += '</div>';
            contentDiv.innerHTML = html;
            suggestionsDiv.style.display = 'block';
        }
    }

    function getConfidenceClass(confidence) {
        if (confidence >= 0.8) return 'confidence-high';
        if (confidence >= 0.6) return 'confidence-medium';
        return 'confidence-low';
    }

    function checkDenialRisk(data) {
        // Call denial prediction API
        fetch('/api/ai/denial-prediction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                codes: {
                    icd10: data.icd10_codes || [],
                    cpt: data.cpt_codes || []
                },
                amount: document.getElementById('total_amount').value,
                provider_npi: document.getElementById('provider_npi').value
            })
        })
        .then(response => response.json())
        .then(data => {
            displayDenialRisk(data);
        })
        .catch(error => {
            console.error('Error checking denial risk:', error);
        });
    }

    function displayDenialRisk(data) {
        const riskDiv = document.getElementById('denialRiskIndicator');
        const explanationDiv = document.getElementById('riskExplanation');

        if (data.risk_probability > 0.3) { // Show if risk is above 30%
            let riskLevel = 'Low';
            let alertClass = 'alert-warning';

            if (data.risk_probability > 0.7) {
                riskLevel = 'High';
                alertClass = 'alert-danger';
            } else if (data.risk_probability > 0.5) {
                riskLevel = 'Medium';
                alertClass = 'alert-warning';
            }

            riskDiv.className = `alert ${alertClass}`;
            explanationDiv.innerHTML = `
                <p><strong>Risk Level:</strong> ${riskLevel} (${Math.round(data.risk_probability * 100)}% chance of denial)</p>
                <p><strong>Key Factors:</strong></p>
                <ul>
                    ${data.explanations ? data.explanations.map(exp => `<li>${exp}</li>`).join('') : '<li>No specific factors identified</li>'}
                </ul>
                <p><strong>Recommendations:</strong></p>
                <ul>
                    <li>Review coding accuracy</li>
                    <li>Ensure medical necessity is well documented</li>
                    <li>Verify insurance coverage</li>
                </ul>
            `;
            riskDiv.style.display = 'block';

            // Update tooltip
            const tooltipEl = document.querySelector('.risk-tooltip');
            if (tooltipEl) {
                tooltipEl.setAttribute('title', `Denial Risk: ${Math.round(data.risk_probability * 100)}%. ${data.explanations ? data.explanations.join('. ') : 'No specific factors.'}`);
                // Reinitialize tooltip
                bootstrap.Tooltip.getInstance(tooltipEl)?.dispose();
                new bootstrap.Tooltip(tooltipEl);
            }
        }
    }

    // Apply selected suggestions
    document.getElementById('applySuggestions').addEventListener('click', function() {
        const selectedICD10 = Array.from(document.querySelectorAll('.icd10-checkbox:checked')).map(cb => cb.value);
        const selectedCPT = Array.from(document.querySelectorAll('.cpt-checkbox:checked')).map(cb => cb.value);

        document.getElementById('icd10_codes').value = selectedICD10.join('\n');
        document.getElementById('cpt_codes').value = selectedCPT.join('\n');

        alert('Selected codes have been applied to the form.');
    });

    // Clear suggestions
    document.getElementById('clearSuggestions').addEventListener('click', function() {
        document.getElementById('aiSuggestions').style.display = 'none';
        document.getElementById('suggestionsContent').innerHTML = '';
        document.getElementById('denialRiskIndicator').style.display = 'none';
    });

    // Calculate age helper
    function calculateAge(dob) {
        if (!dob) return null;
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    // Save as draft functionality
    document.getElementById('saveDraft').addEventListener('click', function() {
        const form = document.getElementById('claimForm');
        const formData = new FormData(form);

        // Add draft flag
        formData.append('status', 'draft');

        fetch(form.action, {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                alert('Claim saved as draft successfully!');
                window.location.href = '{{ route("hospital-admin.claims.index") }}';
            } else {
                alert('Error saving draft. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving draft. Please try again.');
        });
    });
</script>
@endpush
