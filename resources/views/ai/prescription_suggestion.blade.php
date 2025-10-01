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

<div class="col-md-4">
    <label class="form-label fw-semibold">&nbsp;</label>
    <button type="button" id="aiSuggestBtn" class="btn btn-outline-info w-100">
        <i class="fas fa-magic me-1"></i>Suggest with AI
    </button>
</div>

<div id="ai-suggestions" class="mb-3 p-3 bg-light border rounded" style="display: none;"></div>
<div id="ai-risks" class="alert alert-warning mb-3" style="display: none;">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Potential Risks:</strong> <span id="risks-content"></span>
</div>

@push('scripts')
<script>
// Prescription AI Suggestion
$('#aiSuggestBtn').click(function(e) {
    e.preventDefault();

    var button = $(this);
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...');

    var symptoms = @json($appointment->symptoms ?? data_get($appointment->patient, 'patient_data.symptoms', ''));
    var allergies = @json(data_get($appointment->patient, 'patient_data.allergies', []));
    var pastMeds = @json(data_get($appointment->patient, 'patient_data.past_medications', []));

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
            console.log('Suggestions:', response.suggestions);
            console.log('First suggestion:', response.suggestions ? response.suggestions[0] : 'none');

            // Suggestions
            if (response.suggestions && response.suggestions.length > 0) {
                var suggestionsHtml = '<h6 class="mb-3 text-primary">AI Suggested Medications:</h6>';
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

                    suggestionsHtml += '<div class="suggestion-item p-3 bg-white border rounded mb-2" data-index="' + i + '">';
                    suggestionsHtml += '<div class="d-flex justify-content-between align-items-start mb-2">';
                    suggestionsHtml += '<div class="flex-grow-1">';
                    suggestionsHtml += '<h6 class="mb-1">' + (suggestion.med || 'Unknown Medication') + '</h6>';
                    suggestionsHtml += '<small class="text-muted">' + (suggestion.reason || 'No reason provided') + '</small>';
                    suggestionsHtml += '</div>';
                    suggestionsHtml += '<span class="badge bg-' + confidenceClass + ' ms-2">' + confidence + '% ' + confidenceText + '</span>';
                    suggestionsHtml += '</div>';
                    suggestionsHtml += '<div class="row text-small mb-2">';
                    suggestionsHtml += '<div class="col-md-4"><strong>Dosage:</strong> ' + (suggestion.dosage || 'N/A') + '</div>';
                    suggestionsHtml += '<div class="col-md-4"><strong>Frequency:</strong> ' + (suggestion.freq || 'N/A') + '</div>';
                    suggestionsHtml += '<div class="col-md-4"><strong>Duration:</strong> ' + (suggestion.dur || 'N/A') + '</div>';
                    suggestionsHtml += '</div>';
                    suggestionsHtml += '<div class="d-flex gap-2">';
                    suggestionsHtml += '<button type="button" class="btn btn-success btn-sm accept-suggestion" data-index="' + i + '">';
                    suggestionsHtml += '<i class="fas fa-check me-1"></i>Accept</button>';
                    suggestionsHtml += '<button type="button" class="btn btn-danger btn-sm reject-suggestion" data-index="' + i + '">';
                    suggestionsHtml += '<i class="fas fa-times me-1"></i>Reject</button>';
                    suggestionsHtml += '</div>';
                    suggestionsHtml += '</div>';
                });
                $('#ai-suggestions').html(suggestionsHtml).show();

                // Set hidden field
                $('#ai_suggestions').val(JSON.stringify(response.suggestions));
            } else {
                $('#ai-suggestions').html('<div class="p-3 text-muted">No AI suggestions available at this time.</div>').show();
                $('#ai_suggestions').val('');
            }

            // Risks
            if (response.risk_flags && response.risk_flags.length > 0) {
                var risksText = response.risk_flags.join(', ');
                $('#risks-content').text(risksText);
                $('#ai-risks').show();
                $('#ai_risk_flags').val(JSON.stringify(response.risk_flags));
            } else {
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

    // Mark this suggestion as accepted
    $(this).closest('.suggestion-item').addClass('accepted').removeClass('rejected');
    $(this).closest('.suggestion-item').find('.reject-suggestion').prop('disabled', true);
    $(this).prop('disabled', true).html('<i class="fas fa-check me-1"></i>Accepted');

    showNotification('Suggestion accepted and form filled!', 'success');
});

// Handle reject suggestion button
$(document).on('click', '.reject-suggestion', function() {
    var suggestionItem = $(this).closest('.suggestion-item');

    // Mark this suggestion as rejected
    suggestionItem.addClass('rejected').removeClass('accepted');
    suggestionItem.find('.accept-suggestion').prop('disabled', true);
    $(this).prop('disabled', true).html('<i class="fas fa-times me-1"></i>Rejected');

    showNotification('Suggestion rejected.', 'info');
});

// Reset form function - AI parts
window.resetPrescriptionForm = window.resetPrescriptionForm || function() {
    $('#prescriptionForm')[0].reset();
    $('#ai-suggestions').hide();
    $('#ai-risks').hide();
    $('#ai_suggestions').val('');
    $('#ai_risk_flags').val('');
    // Reset any accepted/rejected states
    $('.suggestion-item').removeClass('accepted rejected');
    $('.accept-suggestion, .reject-suggestion').prop('disabled', false);
    $('.accept-suggestion').html('<i class="fas fa-check me-1"></i>Accept');
    $('.reject-suggestion').html('<i class="fas fa-times me-1"></i>Reject');
    showNotification('Form reset.', 'info');
};
</script>
@endpush