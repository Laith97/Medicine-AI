@push('styles')
<style>
.suggestion-item.accepted {
    border-color: #198754 !important;
    background-color: #f8fff9 !important;
}
.suggestion-item.rejected {
    border-color: #dc3545 !important;
    background-color: #fff5f5 !important;
    opacity: 0.7;
}
.suggestion-item.accepted .badge {
    background-color: #198754 !important;
}
.suggestion-item.rejected .badge {
    background-color: #dc3545 !important;
}
</style>
@endpush

<input type="hidden" name="ai_suggestions" id="ai_suggestions" value="">
<input type="hidden" name="ai_risk_flags" id="ai_risk_flags" value="">

<div class="col-md-12 ai-section">
    <div class="border rounded p-3 bg-light">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">
                <i class="fas fa-brain text-primary me-2"></i>AI Clinical Support
            </label>
            <span class="badge bg-info">Clinical Decision Support</span>
        </div>
        <button type="button" id="aiSuggestBtn" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-magic me-2"></i>Get AI Medication Suggestions
        </button>
        <div class="form-text small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Analyzes patient symptoms, doctor notes, allergies, and medical history for evidence-based medication suggestions.
            <strong>All AI suggestions require your clinical review and approval.</strong>
        </div>
    </div>
</div>

<!-- Clinical Data Summary -->
<div id="clinical-data-summary" class="mb-3 p-3 bg-light border rounded" style="display: none;">
    <h6 class="mb-3 text-primary fw-bold">
        <i class="fas fa-clipboard-check me-2"></i>Clinical Data Used for AI Analysis
    </h6>
    <div id="clinical-data-content" class="small"></div>
</div>

<!-- AI Suggestions -->
<div id="ai-suggestions" class="mb-3 p-3 bg-light border rounded" style="display: none;"></div>
<div id="ai-risks" class="alert alert-danger mb-3" style="display: none;">
    <i class="fas fa-shield-alt me-2"></i>
    <strong>⚠️ CLINICAL DECISION SUPPORT WARNINGS:</strong>
    <div id="risks-content" class="mt-2"></div>
    <hr class="my-2">
    <small class="text-muted">
        <strong>IMPORTANT:</strong> These are AI-generated suggestions for clinical decision support only.
        All medication decisions must be made by qualified healthcare professionals after thorough clinical evaluation.
    </small>
</div>

@push('scripts')
<script>
// Function to show clinical data summary
function showClinicalDataSummary(clinicalData) {
    var summaryHtml = '';

    if (clinicalData && Object.keys(clinicalData).length > 0) {
        summaryHtml += '<div class="row g-2">';

        if (clinicalData.symptoms) {
            summaryHtml += '<div class="col-12"><strong>📋 Symptoms:</strong> ' + clinicalData.symptoms + '</div>';
        }
        if (clinicalData.doctor_notes) {
            summaryHtml += '<div class="col-12"><strong>👨‍⚕️ Doctor Notes:</strong> ' + clinicalData.doctor_notes + '</div>';
        }
        if (clinicalData.current_diagnosis) {
            summaryHtml += '<div class="col-12"><strong>👨‍⚕️ Current Diagnosis:</strong> ' + clinicalData.current_diagnosis + '</div>';
        }
        if (clinicalData.past_diagnoses && clinicalData.past_diagnoses.length > 0) {
            summaryHtml += '<div class="col-12"><strong>📚 Past Diagnosis History:</strong> ' + clinicalData.past_diagnoses.join('; ') + '</div>';
        }
        if (clinicalData.voice_diagnosis) {
            summaryHtml += '<div class="col-12"><strong>🎤 Voice Assistant Diagnosis:</strong> ' + clinicalData.voice_diagnosis + '</div>';
        }

        summaryHtml += '</div>';
        summaryHtml += '<div class="mt-2 small text-success"><i class="fas fa-check-circle me-1"></i>AI analyzed the above verified clinical data to provide medication suggestions.</div>';
    } else {
        summaryHtml = '<div class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i><strong>No clinical documentation found.</strong><br><em>The AI analyzed available patient data but found no specific symptoms, diagnosis, or clinical notes. Suggestions are based on general preventive care recommendations.</em></div>';
    }

    $('#clinical-data-content').html(summaryHtml);
    $('#clinical-data-summary').show();
}

// Prescription AI Suggestion
$('#aiSuggestBtn').click(function(e) {
    e.preventDefault();

    // CRITICAL SAFETY: Only use doctor-verified clinical data for AI medication suggestions
    // Patient-reported symptoms are excluded due to unreliability
    var symptoms = @json($appointment->doctor_notes ?? '');
    var allergies = @json(data_get($appointment->patient, 'patient_data.allergies', []));
    var pastMeds = @json(data_get($appointment->patient, 'patient_data.past_medications', []));

    // Include current (most recent) diagnosis data if available
    var currentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);

    // Include past diagnosis history (all except most recent, limit to last 10)
    var pastDiagnoses = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect());

    // Include voice assistant diagnosis if available
    var voiceDiagnosis = @json($appointment->patient ? \App\Models\AiAssistantResult::where('patient_id', $appointment->patient->id)->where('source', 'voice_assistant')->latest()->first() : null);

    // Check for missing critical data
    var missingData = [];
    if (!allergies || (Array.isArray(allergies) && allergies.length === 0)) {
        missingData.push('Patient Allergies');
    }
    if ((!pastMeds || (Array.isArray(pastMeds) && pastMeds.length === 0)) && 
        (!currentDiagnosis || !currentDiagnosis.patient_data || !currentDiagnosis.patient_data.medications)) {
        missingData.push('Current Medications');
    }
    if (!symptoms && !currentDiagnosis) {
        missingData.push('Doctor Clinical Assessment');
    }

    // Show warning modal if critical data is missing
    if (missingData.length > 0) {
        var warningHtml = `
            <div class="modal fade" id="aiWarningModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Missing Critical Data</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>⚠️ The following critical data is missing:</strong></p>
                            <ul class="text-danger">
                                ${missingData.map(item => '<li>' + item + '</li>').join('')}
                            </ul>
                            <p class="mb-2"><strong>Why this matters:</strong></p>
                            <ul class="small">
                                <li><strong>Without allergies:</strong> AI could suggest medications patient is allergic to (life-threatening)</li>
                                <li><strong>Without current medications:</strong> Cannot check drug interactions (dangerous)</li>
                                <li><strong>Without clinical assessment:</strong> No basis for medication recommendations</li>
                            </ul>
                            <hr>
                            <p class="mb-0"><strong>What would you like to do?</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <a href="/diagnosis/create" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add Missing Data
                            </a>
                            <button type="button" class="btn btn-warning" id="continueAnywayBtn">
                                <i class="fas fa-exclamation-triangle me-1"></i>Continue Anyway (Not Recommended)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        $('#aiWarningModal').remove();
        
        // Add modal to body
        $('body').append(warningHtml);
        
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('aiWarningModal'));
        modal.show();
        
        // Handle continue anyway button
        $('#continueAnywayBtn').click(function() {
            modal.hide();
            proceedWithAISuggestion();
        });
        
        return;
    }
    
    // If all critical data is present, proceed
    proceedWithAISuggestion();
    
    function proceedWithAISuggestion() {
        var button = $('#aiSuggestBtn');
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...');

        $.ajax({
        url: "{{ route('ai.appointments.suggest', $appointment->id) }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            symptoms: symptoms,
            allergies: JSON.stringify(allergies),
            past_meds: JSON.stringify(pastMeds),
            current_diagnosis: JSON.stringify(currentDiagnosis),
            past_diagnoses: JSON.stringify(pastDiagnoses),
            voice_diagnosis: JSON.stringify(voiceDiagnosis),
            doctor_notes: @json($appointment->doctor_notes)
        },
        success: function(response) {
            button.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Suggest with AI');

            // Debug logging
            // console.log('AI Response:', response);
            // console.log('Suggestions:', response.suggestions);
            // console.log('First suggestion:', response.suggestions ? response.suggestions[0] : 'none');

            // Show clinical data summary first
            showClinicalDataSummary(response.clinical_data_used);

            // Suggestions
            if (response.suggestions && response.suggestions.length > 0) {
                var suggestionsHtml = '<h6 class="mb-3 text-primary"><i class="fas fa-pills me-2"></i>AI Suggested Medications:</h6>';
                $.each(response.suggestions, function(i, suggestion) {
                    // console.log('Processing suggestion ' + i + ':', suggestion);

                    // Ensure suggestion is an object
                    if (typeof suggestion !== 'object' || suggestion === null) {
                        // console.error('Suggestion is not an object:', suggestion);
                        return true; // continue to next iteration
                    }

                    var confidence = suggestion.confidence || 0;
                    var confidenceClass = confidence >= 80 ? 'success' : (confidence >= 60 ? 'warning' : 'danger');
                    var confidenceText = confidence >= 80 ? 'High' : (confidence >= 60 ? 'Medium' : 'Low');

                    suggestionsHtml += '<div class="suggestion-item p-3 bg-white border border-warning rounded mb-3" data-index="' + i + '">';
                    suggestionsHtml += '<div class="d-flex justify-content-between align-items-start mb-2">';
                    suggestionsHtml += '<div class="flex-grow-1">';
                    suggestionsHtml += '<h6 class="mb-1 text-primary"><i class="fas fa-pills me-2"></i>' + (suggestion.med || 'Unknown Medication') + '</h6>';
                    suggestionsHtml += '<small class="text-muted">' + (suggestion.reason || 'Clinical decision support suggestion') + '</small>';
                    suggestionsHtml += '</div>';
                    suggestionsHtml += '<span class="badge bg-' + confidenceClass + ' ms-2"><i class="fas fa-chart-line me-1"></i>' + confidence + '% ' + confidenceText + '</span>';
                    suggestionsHtml += '</div>';

                    // Enhanced medication details
                    suggestionsHtml += '<div class="row text-small mb-2">';
                    suggestionsHtml += '<div class="col-md-4"><strong>Dosage:</strong> <span class="text-primary">' + (suggestion.dosage || 'N/A') + '</span></div>';
                    suggestionsHtml += '<div class="col-md-4"><strong>Frequency:</strong> <span class="text-primary">' + (suggestion.freq || 'N/A') + '</span></div>';
                    suggestionsHtml += '<div class="col-md-4"><strong>Duration:</strong> <span class="text-primary">' + (suggestion.dur || 'N/A') + '</span></div>';
                    suggestionsHtml += '</div>';

                    // Show warnings and interactions if available
                    if (suggestion.warnings && suggestion.warnings.length > 0) {
                        suggestionsHtml += '<div class="mb-2"><strong class="text-warning">⚠️ Warnings:</strong><ul class="mb-1 small">';
                        $.each(suggestion.warnings, function(j, warning) {
                            suggestionsHtml += '<li>' + warning + '</li>';
                        });
                        suggestionsHtml += '</ul></div>';
                    }

                    if (suggestion.interactions && suggestion.interactions.length > 0) {
                        suggestionsHtml += '<div class="mb-2"><strong class="text-danger">💊 Interactions:</strong><ul class="mb-1 small">';
                        $.each(suggestion.interactions, function(j, interaction) {
                            suggestionsHtml += '<li>' + interaction + '</li>';
                        });
                        suggestionsHtml += '</ul></div>';
                    }

                    suggestionsHtml += '<div class="d-flex gap-2 mt-2">';
                    suggestionsHtml += '<button type="button" class="btn btn-success btn-sm accept-suggestion" data-index="' + i + '">';
                    suggestionsHtml += '<i class="fas fa-check me-1"></i>Use Suggestion</button>';
                    suggestionsHtml += '<button type="button" class="btn btn-outline-secondary btn-sm reject-suggestion" data-index="' + i + '">';
                    suggestionsHtml += '<i class="fas fa-times me-1"></i>Dismiss</button>';
                    suggestionsHtml += '</div>';

                    // Professional disclaimer
                    suggestionsHtml += '<div class="mt-2 p-2 bg-light rounded small text-muted">';
                    suggestionsHtml += '<i class="fas fa-user-md me-1"></i><strong>Clinical Decision Support:</strong> This AI suggestion must be reviewed and approved by a licensed healthcare professional before use.';
                    suggestionsHtml += '</div>';

                    suggestionsHtml += '</div>';
                });
                $('#ai-suggestions').html(suggestionsHtml).show();

                // Set hidden field
                $('#ai_suggestions').val(JSON.stringify(response.suggestions));
            } else {
                $('#ai-suggestions').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Preventive Care Recommendations:</strong> No specific medications needed based on current clinical data. The AI suggests focusing on preventive care measures appropriate for the patient\'s age and health status.</div>').show();
                $('#ai_suggestions').val('');
            }

            // Risks - Always show warnings for safety
            if (response.risk_flags && response.risk_flags.length > 0) {
                var risksHtml = '<ul class="mb-0">';
                $.each(response.risk_flags, function(i, risk) {
                    risksHtml += '<li>' + risk + '</li>';
                });
                risksHtml += '</ul>';

                // Add disclaimer if provided
                if (response.disclaimer) {
                    risksHtml += '<div class="mt-2 p-2 bg-light rounded small"><strong>Disclaimer:</strong> ' + response.disclaimer + '</div>';
                }

                $('#risks-content').html(risksHtml);
                $('#ai-risks').show();
                $('#ai_risk_flags').val(JSON.stringify(response.risk_flags));
            } else {
                // Show default safety warnings even if no specific risks
                var defaultWarnings = [
                    '⚠️ CLINICAL DECISION SUPPORT ONLY - Professional medical judgment required',
                    '⚠️ Verify patient allergies and contraindications',
                    '⚠️ Check current medications for interactions',
                    '⚠️ Consider patient age, weight, and organ function'
                ];
                var risksHtml = '<ul class="mb-0">';
                $.each(defaultWarnings, function(i, risk) {
                    risksHtml += '<li>' + risk + '</li>';
                });
                risksHtml += '</ul>';
                $('#risks-content').html(risksHtml);
                $('#ai-risks').show();
                $('#ai_risk_flags').val(JSON.stringify(defaultWarnings));
            }

            showNotification('AI Clinical Decision Support analysis complete. Analyzed available clinical data and provided preventive care recommendations.', 'info');
        },
        error: function(xhr, status, error) {
            button.prop('disabled', false).html('<i class="fas fa-brain me-1"></i>AI Clinical Support');

            var msg = 'AI Clinical Decision Support unavailable. Please proceed with manual prescription entry.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = 'AI Clinical Decision Support: ' + xhr.responseJSON.message + ' - Manual prescription entry required.';
            }
            showNotification(msg, 'warning');

            // Show fallback suggestions when AI is unavailable
            $('#ai-suggestions').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>AI Unavailable:</strong> Please proceed with manual prescription entry. For common conditions, consider evidence-based treatments like acetaminophen for pain/fever or amoxicillin for bacterial infections (after proper diagnosis).</div>').show();

            $('#clinical-data-summary').hide();
            $('#ai-suggestions').hide();
            $('#ai-risks').hide();
        }
    });
    }
});

// Handle accept suggestion button
$(document).on('click', '.accept-suggestion', function() {
    var index = $(this).data('index');
    var suggestions = JSON.parse($('#ai_suggestions').val());
    var suggestion = suggestions[index];

    // Fill the form with the accepted suggestion
    $('#medication_name').val(suggestion.med);
    $('#dosage').val(suggestion.dosage);
    $('#frequency').val(suggestion.freq);
    $('#duration').val(suggestion.dur);

    // Add clinical rationale to instructions if available
    if (suggestion.reason) {
        var currentInstructions = $('#instructions').val();
        var rationaleText = 'AI Clinical Decision Support: ' + suggestion.reason;
        if (currentInstructions) {
            $('#instructions').val(currentInstructions + '\n\n' + rationaleText);
        } else {
            $('#instructions').val(rationaleText);
        }
    }

    // Add warnings to notes if available
    if (suggestion.warnings && suggestion.warnings.length > 0) {
        var currentNotes = $('#notes').val();
        var warningsText = 'AI Warnings: ' + suggestion.warnings.join('; ');
        if (currentNotes) {
            $('#notes').val(currentNotes + '\n\n' + warningsText);
        } else {
            $('#notes').val(warningsText);
        }
    }

    // Mark this suggestion as accepted
    $(this).closest('.suggestion-item').addClass('accepted').removeClass('rejected');
    $(this).closest('.suggestion-item').find('.reject-suggestion').prop('disabled', true);
    $(this).prop('disabled', true).html('<i class="fas fa-check me-1"></i>Applied to Form');

    showNotification('AI suggestion applied to prescription form. Please review and modify as needed for patient safety.', 'success');
});

// Handle reject suggestion button
$(document).on('click', '.reject-suggestion', function() {
    var suggestionItem = $(this).closest('.suggestion-item');

    // Mark this suggestion as rejected
    suggestionItem.addClass('rejected').removeClass('accepted');
    suggestionItem.find('.accept-suggestion').prop('disabled', true);
    $(this).prop('disabled', true).html('<i class="fas fa-times me-1"></i>Rejected');

    showNotification('AI suggestion dismissed. Manual prescription entry required.', 'info');
});

// Reset form function - AI parts
window.resetPrescriptionForm = window.resetPrescriptionForm || function() {
    $('#prescriptionForm')[0].reset();
    $('#clinical-data-summary').hide();
    $('#ai-suggestions').hide();
    $('#ai-risks').hide();
    $('#ai_suggestions').val('');
    $('#ai_risk_flags').val('');
    // Reset any accepted/rejected states
    $('.suggestion-item').removeClass('accepted rejected');
    $('.accept-suggestion, .reject-suggestion').prop('disabled', false);
    $('.accept-suggestion').html('<i class="fas fa-check me-1"></i>Accept');
    $('.reject-suggestion').html('<i class="fas fa-times me-1"></i>Reject');
    showNotification('Prescription form reset. Clinical data and AI suggestions cleared.', 'info');
};

// AI Data Sources Modal Functions
function populateDataSourcesModal() {
    const appointment = @json($appointment);
    const patient = @json($appointment->patient);
    const patientData = @json($appointment->patient ? $appointment->patient->patientData : null);
    const currentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);
    const pastDiagnoses = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect());
    const voiceDiagnosis = @json($appointment->patient ? \App\Models\AiAssistantResult::where('patient_id', $appointment->patient->id)->where('source', 'voice_assistant')->latest()->first() : null);

    const dataSources = [
        {
            name: 'Patient Allergies',
            status: patientData && patientData.allergies && (Array.isArray(patientData.allergies) ? patientData.allergies.length > 0 : patientData.allergies.toString().trim().length > 0) ? 'available' : 'missing',
            example: patientData && patientData.allergies ? (Array.isArray(patientData.allergies) ? patientData.allergies.join(', ') : patientData.allergies.toString()) : 'No allergies recorded',
            location: 'Diagnosis creation form (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-allergies',
            importance: 'critical',
            reason: 'Prevents prescribing medications patient is allergic to (life-threatening)'
        },
        {
            name: 'Current Medications',
            status: patientData && patientData.past_medications && (Array.isArray(patientData.past_medications) ? patientData.past_medications.length > 0 : patientData.past_medications.toString().trim().length > 0) ? 'available' : 'missing',
            example: patientData && patientData.past_medications ? (Array.isArray(patientData.past_medications) ? patientData.past_medications.join(', ') : patientData.past_medications.toString()) : 'No medications recorded',
            location: 'Diagnosis creation form (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-pills',
            importance: 'critical',
            reason: 'Required to check drug-drug interactions (dangerous without this)'
        },
        {
            name: 'Doctor Notes',
            status: appointment.doctor_notes ? 'available' : 'missing',
            example: appointment.doctor_notes ? (appointment.doctor_notes.length > 30 ? appointment.doctor_notes.substring(0, 30) + '...' : appointment.doctor_notes) : 'No doctor notes',
            location: 'Appointment completion modal (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-user-md',
            importance: 'critical',
            reason: 'Provides clinical assessment - required if no diagnosis exists'
        },
        {
            name: 'Current Diagnosis',
            status: currentDiagnosis ? 'available' : 'missing',
            example: currentDiagnosis ? (currentDiagnosis.diagnosis_text ? currentDiagnosis.diagnosis_text.substring(0, 30) + (currentDiagnosis.diagnosis_text.length > 30 ? '...' : '') : 'Diagnosis recorded') : 'No current diagnosis',
            location: 'Most recent diagnosis record (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-stethoscope',
            importance: 'critical',
            reason: 'Primary driver for medication selection - required if no doctor notes'
        },
        {
            name: 'Patient Age',
            status: patient && patient.age ? 'available' : 'missing',
            example: patient && patient.age ? patient.age + ' years old' : 'Age not recorded',
            location: 'Patient Management (Administrative)',
            reliability: 'Administrative',
            icon: 'fas fa-birthday-cake',
            importance: 'important',
            reason: 'Needed for dosage calculations (especially pediatric/geriatric)'
        },
        {
            name: 'Patient Weight',
            status: patientData && patientData.weight ? 'available' : 'missing',
            example: patientData && patientData.weight ? patientData.weight + ' kg' : 'Weight not recorded',
            location: 'Diagnosis creation form',
            reliability: 'Doctor-verified',
            icon: 'fas fa-weight',
            importance: 'important',
            reason: 'Critical for pediatric weight-based dosing calculations'
        },
        {
            name: 'Past Diagnosis History',
            status: pastDiagnoses && pastDiagnoses.length > 0 ? 'available' : 'missing',
            example: pastDiagnoses && pastDiagnoses.length > 0 ? `${pastDiagnoses.length} past diagnosis(es) found` : 'No past diagnosis history',
            location: 'Historical diagnosis records (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-history',
            importance: 'helpful',
            reason: 'Provides context for comorbidities and treatment history'
        },
        {
            name: 'Patient Gender',
            status: patient && patient.gender ? 'available' : 'missing',
            example: patient && patient.gender ? patient.gender.charAt(0).toUpperCase() + patient.gender.slice(1) : 'Gender not recorded',
            location: 'Patient Management (Administrative)',
            reliability: 'Administrative',
            icon: 'fas fa-venus-mars',
            importance: 'helpful',
            reason: 'Relevant for pregnancy/breastfeeding considerations and some medications'
        },
        {
            name: 'Voice Assistant Diagnosis',
            status: voiceDiagnosis ? 'available' : 'missing',
            example: voiceDiagnosis ? (voiceDiagnosis.patient_data && voiceDiagnosis.patient_data.diagnosis ? voiceDiagnosis.patient_data.diagnosis : 'Voice diagnosis available') : 'No voice diagnosis',
            location: 'Voice Assistant sessions (AI-assisted clinical)',
            reliability: 'AI-assisted clinical',
            icon: 'fas fa-microphone',
            importance: 'helpful',
            reason: 'Additional clinical context from voice sessions'
        },

    ];

    let tableHtml = '';
    let availableCount = 0;
    let criticalMissing = [];

    dataSources.forEach(source => {
        const statusBadge = source.status === 'available'
            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Available</span>'
            : '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Missing</span>';

        if (source.status === 'available') availableCount++;
        if (source.status === 'missing' && source.importance === 'critical') {
            criticalMissing.push(source.name);
        }

        const reliabilityBadge = source.reliability === 'Doctor-verified'
            ? '<span class="badge bg-success"><i class="fas fa-user-md me-1"></i>Doctor-verified</span>'
            : source.reliability === 'AI-assisted clinical'
            ? '<span class="badge bg-info"><i class="fas fa-brain me-1"></i>AI-assisted</span>'
            : source.reliability === 'Administrative'
            ? '<span class="badge bg-secondary"><i class="fas fa-cog me-1"></i>Administrative</span>'
            : '<span class="badge bg-warning text-dark"><i class="fas fa-user me-1"></i>Patient-reported</span>';

        const importanceBadge = source.importance === 'critical'
            ? '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>CRITICAL</span>'
            : source.importance === 'important'
            ? '<span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Important</span>'
            : source.importance === 'helpful'
            ? '<span class="badge bg-info"><i class="fas fa-info-circle me-1"></i>Helpful</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-tag me-1"></i>Context</span>';

        tableHtml += `
            <tr class="${source.status === 'missing' ? 'table-light' : ''}">
                <td>
                    <i class="${source.icon} me-2 text-primary"></i><strong>${source.name}</strong>
                    <br><small class="text-muted">${source.reason}</small>
                </td>
                <td>${statusBadge}</td>
                <td class="small">${importanceBadge}</td>
                <td class="small">${reliabilityBadge}</td>
                <td class="small text-muted">${source.example}</td>
            </tr>
        `;
    });

    document.getElementById('dataSourcesTableBody').innerHTML = tableHtml;

    // Calculate completeness
    const completenessPercentage = Math.round((availableCount / dataSources.length) * 100);
    const completenessBar = document.getElementById('dataCompletenessBar');
    const completenessText = document.getElementById('dataCompletenessText');

    completenessBar.style.width = completenessPercentage + '%';
    completenessBar.textContent = completenessPercentage + '% Complete';

    // Check if critical data is missing
    if (criticalMissing.length > 0) {
        completenessBar.className = 'progress-bar bg-danger';
        completenessText.innerHTML = `<strong class="text-danger">⚠️ CRITICAL DATA MISSING:</strong> AI medication suggestions are <strong>BLOCKED</strong> for patient safety. Missing: ${criticalMissing.join(', ')}`;
    } else if (completenessPercentage >= 80) {
        completenessBar.className = 'progress-bar bg-success';
        completenessText.textContent = '✅ Excellent data completeness! AI suggestions will be highly accurate.';
    } else if (completenessPercentage >= 60) {
        completenessBar.className = 'progress-bar bg-warning';
        completenessText.textContent = '⚠️ Good data completeness. AI suggestions will be moderately accurate.';
    } else {
        completenessBar.className = 'progress-bar bg-danger';
        completenessText.textContent = '❌ Limited data available. Consider adding more clinical information for better AI suggestions.';
    }

    // Update improvement suggestions based on missing data
    const missingSources = dataSources.filter(s => s.status === 'missing').map(s => s.name.toLowerCase());
    let suggestionsHtml = '';

    if (missingSources.includes('patient age')) {
        suggestionsHtml += '<li>Ensure patient age is recorded in <strong>Patient Management</strong></li>';
    }
    if (missingSources.includes('patient gender')) {
        suggestionsHtml += '<li>Ensure patient gender is recorded in <strong>Patient Management</strong></li>';
    }
    if (missingSources.includes('patient allergies')) {
        suggestionsHtml += '<li>Complete patient allergy information in <strong>Diagnosis Creation</strong> form</li>';
    }
    if (missingSources.includes('current medications')) {
        suggestionsHtml += '<li>Update current medications in <strong>Diagnosis Creation</strong> form</li>';
    }
    if (missingSources.includes('doctor notes')) {
        suggestionsHtml += '<li>Include comprehensive doctor notes during <strong>appointment completion</strong></li>';
    }
    if (missingSources.includes('complete diagnosis history')) {
        suggestionsHtml += '<li>Create diagnosis records in the <strong>Diagnoses</strong> section for complete medical history</li>';
    }
    if (missingSources.includes('voice assistant diagnosis')) {
        suggestionsHtml += '<li>Use <strong>Voice Assistant</strong> for detailed clinical assessments</li>';
    }
    if (missingSources.includes('reason for visit')) {
        suggestionsHtml += '<li>Specify reason for visit during <strong>appointment booking</strong> (doctor or patient)</li>';
    }

    if (suggestionsHtml) {
        document.getElementById('improvementSuggestions').innerHTML = suggestionsHtml;
    }
}

function refreshDataSources() {
    populateDataSourcesModal();
    showNotification('Data sources refreshed successfully.', 'success');
}

// Initialize data sources modal when opened
document.addEventListener('DOMContentLoaded', function() {
    const aiDataSourcesModal = document.getElementById('aiDataSourcesModal');
    if (aiDataSourcesModal) {
        aiDataSourcesModal.addEventListener('show.bs.modal', function() {
            populateDataSourcesModal();
        });
    }
});
</script>
@endpush