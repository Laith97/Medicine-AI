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
        if (clinicalData.appointment_symptoms) {
            summaryHtml += '<div class="col-12"><strong>📅 Appointment Symptoms:</strong> ' + clinicalData.appointment_symptoms + '</div>';
        }
        if (clinicalData.doctor_diagnosis) {
            summaryHtml += '<div class="col-12"><strong>👨‍⚕️ Doctor Diagnosis:</strong> ' + clinicalData.doctor_diagnosis + '</div>';
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

    var button = $(this);
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...');

    // Use doctor-verified clinical data from diagnosis records and appointment notes
    var symptoms = @json($appointment->symptoms ??
                        data_get($appointment->patient, 'patient_data.symptoms', '') ??
                        $appointment->doctor_notes ??
                        '');
    var allergies = @json(data_get($appointment->patient, 'patient_data.allergies', []));
    var pastMeds = @json(data_get($appointment->patient, 'patient_data.past_medications', []));

    // Include recent diagnosis data if available
    var recentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);

    $.ajax({
        url: "{{ route('ai.appointments.suggest', $appointment->id) }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            symptoms: symptoms,
            allergies: JSON.stringify(allergies),
            past_meds: JSON.stringify(pastMeds),
            recent_diagnosis: JSON.stringify(recentDiagnosis),
            doctor_notes: @json($appointment->doctor_notes),
            appointment_symptoms: @json($appointment->symptoms)
        },
        success: function(response) {
            button.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Suggest with AI');

            // Debug logging
            console.log('AI Response:', response);
            console.log('Suggestions:', response.suggestions);
            console.log('First suggestion:', response.suggestions ? response.suggestions[0] : 'none');

            // Show clinical data summary first
            showClinicalDataSummary(response.clinical_data_used);

            // Suggestions
            if (response.suggestions && response.suggestions.length > 0) {
                var suggestionsHtml = '<h6 class="mb-3 text-primary"><i class="fas fa-pills me-2"></i>AI Suggested Medications:</h6>';
                $.each(response.suggestions, function(i, suggestion) {
                    console.log('Processing suggestion ' + i + ':', suggestion);

                    // Ensure suggestion is an object
                    if (typeof suggestion !== 'object' || suggestion === null) {
                        console.error('Suggestion is not an object:', suggestion);
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
</script>
@endpush