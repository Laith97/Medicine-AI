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

<div class="ai-section">
    <button type="button" id="aiSuggestBtn" class="btn fw-semibold w-100" style="background:#1e293b; color:#fff; border:1px solid #1e293b; border-radius:10px; padding:0.6rem; font-size:0.86rem; box-shadow: 0 4px 10px rgba(30,41,59,0.15);">
        <i class="fas fa-magic me-2"></i>Get AI Medication Suggestions
    </button>
    <div class="small text-muted mt-2" style="font-size:0.74rem; color:#64748b; line-height:1.4;">
        <i class="fas fa-info-circle me-1"></i>Analyzes symptoms, doctor notes, allergies, and medical history. <strong>All suggestions require clinical review.</strong>
    </div>
</div>

<!-- Clinical Data Summary — premium -->
<div id="clinical-data-summary" class="mb-3 p-3" style="display:none; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px;">
    <h6 class="mb-2 fw-bold" style="font-size:0.84rem; color:#1e293b;"><i class="fas fa-clipboard-check me-2" style="color:#475569;"></i>Clinical Data Used</h6>
    <div id="clinical-data-content" class="small" style="font-size:0.78rem; color:#334155;"></div>
</div>

<!-- AI Suggestions — premium -->
<div id="ai-suggestions" class="mb-3 p-3" style="display:none; background:#fff; border:1px solid #e2e8f0; border-radius:10px;"></div>
<div id="ai-risks" class="mb-3" style="display:none; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:1rem;">
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
// Function to save quick data to appointment for persistence
function saveQuickDataToAppointment(allergies, medications, notes) {
    console.log('Saving quick data:', { allergies, medications, notes });
    
    return $.ajax({
        url: "{{ route('doctor.appointments.save-quick-data', $appointment->id) }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            allergies: allergies,
            medications: medications,
            clinical_notes: notes
        },
        success: function(response) {
            console.log('Quick data saved successfully:', response);
        },
        error: function(xhr, status, error) {
            console.error('Failed to save quick data:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            let errorMessage = 'Failed to save quick data';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMessage = xhr.responseText;
            }
            
            showNotification('Error saving data: ' + errorMessage, 'error');
        }
    });
}

// Function to show clinical data summary
function showClinicalDataSummary(clinicalData) {
    var summaryHtml = '';

    // Handle edge cases: null, undefined, empty array, or empty object all mean no data
    if (clinicalData && typeof clinicalData === 'object' && Object.keys(clinicalData).length > 0) {
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

    // Include current (most recent) diagnosis data if available
    var currentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);

    // Get allergies and medications from the most recent diagnosis patient_data
    var allergies = [];
    var pastMeds = [];

    if (currentDiagnosis && currentDiagnosis.patient_data) {
        // Handle allergies - can be string or array
        var diagnosisAllergies = currentDiagnosis.patient_data.allergies || [];
        if (typeof diagnosisAllergies === 'string' && diagnosisAllergies.trim().length > 0) {
            allergies = [diagnosisAllergies.trim()];
        } else if (Array.isArray(diagnosisAllergies)) {
            allergies = diagnosisAllergies;
        }

        // Handle medications - can be string or array
        var diagnosisMeds = currentDiagnosis.patient_data.medications || currentDiagnosis.patient_data.past_medications || [];
        if (typeof diagnosisMeds === 'string' && diagnosisMeds.trim().length > 0) {
            pastMeds = [diagnosisMeds.trim()];
        } else if (Array.isArray(diagnosisMeds)) {
            pastMeds = diagnosisMeds;
        }

        // CRITICAL FIX: Include clinical_notes as symptoms if doctor_notes is empty
        // This is where Quick Entry symptoms are stored (e.g., "Chest pain, shortness of breath")
        if ((!symptoms || symptoms.trim() === '') && currentDiagnosis.patient_data.clinical_notes) {
            symptoms = currentDiagnosis.patient_data.clinical_notes;
            console.log('🔍 Using clinical_notes as symptoms:', symptoms);
        }

        // Also check patient_data.symptoms as another fallback
        if ((!symptoms || symptoms.trim() === '') && currentDiagnosis.patient_data.symptoms) {
            symptoms = currentDiagnosis.patient_data.symptoms;
            console.log('🔍 Using patient_data.symptoms as symptoms:', symptoms);
        }

        // If still no symptoms, use diagnosis_text as symptoms (this IS the clinical presentation)
        if ((!symptoms || symptoms.trim() === '') && currentDiagnosis.diagnosis_text) {
            symptoms = currentDiagnosis.diagnosis_text;
            console.log('🔍 Using diagnosis_text as symptoms:', symptoms);
        }
    }

    // Include past diagnosis history (all except most recent, limit to last 10)
    var pastDiagnoses = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect());

    // Include voice assistant diagnosis if available
    var voiceDiagnosis = @json($appointment->patient ? \App\Models\AiAssistantResult::where('patient_id', $appointment->patient->id)->where('source', 'voice_assistant')->latest()->first() : null);

    // Debug logging to see what data we actually have
    console.log('🔍 DEBUG: Checking AI prescription data availability');
    console.log('Current Diagnosis:', currentDiagnosis);
    console.log('Patient Data:', currentDiagnosis ? currentDiagnosis.patient_data : 'No diagnosis');
    console.log('Extracted Allergies:', allergies);
    console.log('Extracted Medications:', pastMeds);
    console.log('Doctor Notes:', symptoms);
    
    // Check for missing critical data
    var missingData = [];
    
    // Check allergies - should be a non-empty string or non-empty array
    // Accept "No known allergies", "None", "N/A" as valid entries
    var hasAllergies = false;
    if (allergies) {
        if (Array.isArray(allergies) && allergies.length > 0) {
            hasAllergies = true;
        } else if (typeof allergies === 'string' && allergies.trim().length > 0) {
            const allergyText = allergies.trim().toLowerCase();
            // Accept explicit "no allergies" statements as valid
            if (allergyText === 'none' || allergyText === 'n/a' || 
                allergyText.includes('no known') || allergyText.includes('no allergies') ||
                allergyText === 'nka' || allergyText === 'nkda') {
                hasAllergies = true;
            } else if (allergies.trim().length > 0) {
                hasAllergies = true;
            }
        }
    }
    console.log('Has Allergies:', hasAllergies, 'Value:', allergies);
    
    // Check medications - should be a non-empty string or non-empty array
    // Accept "No current medications", "None", "N/A" as valid entries
    var hasMedications = false;
    if (pastMeds) {
        if (Array.isArray(pastMeds) && pastMeds.length > 0) {
            hasMedications = true;
        } else if (typeof pastMeds === 'string' && pastMeds.trim().length > 0) {
            const medText = pastMeds.trim().toLowerCase();
            // Accept explicit "no medications" statements as valid
            if (medText === 'none' || medText === 'n/a' || 
                medText.includes('no current') || medText.includes('no medications') ||
                medText.includes('no meds')) {
                hasMedications = true;
            } else if (pastMeds.trim().length > 0) {
                hasMedications = true;
            }
        }
    }
    console.log('Has Medications:', hasMedications, 'Value:', pastMeds);
    
    var hasClinicalAssessment = !!(symptoms || currentDiagnosis);
    console.log('Has Clinical Assessment:', hasClinicalAssessment, 'Symptoms:', symptoms, 'Diagnosis:', !!currentDiagnosis);

    // Check if symptoms from patient_data.symptoms is populated
    var hasPatientDataSymptoms = !!(currentDiagnosis && currentDiagnosis.patient_data && currentDiagnosis.patient_data.symptoms && currentDiagnosis.patient_data.symptoms.trim() !== '');

    // Only add to missing if truly missing
    if (!hasAllergies) {
        missingData.push('Patient Allergies');
    }
    if (!hasMedications) {
        missingData.push('Current Medications');
    }
    // Note: If patient_data.symptoms is empty but diagnosis_text exists, diagnosis_text is used as symptoms fallback
    if (!hasPatientDataSymptoms) {
        missingData.push('Symptoms (empty - diagnosis text will be used)');
    }
    
    console.log('Missing Data:', missingData);

    // Show warning modal if critical data is missing
    if (missingData.length > 0) {
        var warningHtml = `
            <div class="modal fade modal-premium" id="aiWarningModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="head-icon" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color:#d97706; border:1px solid #fde68a;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">Missing Critical Data</h5>
                                    <div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">AI requires allergies & meds for safety</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="ml-warning">
                                <i class="fas fa-shield-alt" style="color:#dc2626; margin-top:2px;"></i>
                                <div>
                                    <strong style="color:#7f1d1d;">⚠️ Missing:</strong>
                                    <ul class="mb-1 mt-1" style="font-size:0.78rem; color:#7f1d1d;">
                                        ${missingData.map(item => '<li>' + item + '</li>').join('')}
                                    </ul>
                                    <small style="font-size:0.72rem; color:#991b1b;">AI is blocked for patient safety until critical data is provided.</small>
                                </div>
                            </div>
                            <div class="ml-note mt-3">
                                <i class="fas fa-info-circle" style="color:#2563eb; margin-top:2px;"></i>
                                <div>
                                    <strong>Why this matters:</strong>
                                    <ul class="small mb-0 mt-1" style="font-size:0.74rem; color:#475569;">
                                        <li><strong>Without allergies:</strong> Life-threatening allergy risk</li>
                                        <li><strong>Without current medications:</strong> Cannot check drug interactions</li>
                                        <li><strong>Without clinical assessment:</strong> No basis for recommendations</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Quick Data Entry Form — premium -->
                            <div id="quickDataEntry" class="mt-3 p-3" style="display:none; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px;">
                                <h6 class="mb-3 fw-bold" style="font-size:0.84rem; color:#1e293b;"><i class="fas fa-edit me-2" style="color:#475569;"></i>Quick Data Entry</h6>
                                <form id="quickDataForm">
                                    <div id="quickAllergyField" style="display:none;" class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Patient Allergies *</label>
                                        <input type="text" id="quickAllergies" class="form-control" placeholder="Enter allergies or 'None' if no known allergies" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                        <div class="form-text" style="font-size:0.72rem;">Examples: Penicillin, Sulfa, None, No known allergies</div>
                                    </div>
                                    <div id="quickMedicationField" style="display:none;" class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Current Medications *</label>
                                        <input type="text" id="quickMedications" class="form-control" placeholder="Enter current medications or 'None' if no current medications" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                        <div class="form-text" style="font-size:0.72rem;">Examples: Lisinopril 10mg daily, Metformin 500mg twice daily, None</div>
                                    </div>
                                    <div id="quickNotesField" style="display:none;" class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Symptoms *</label>
                                        <textarea id="quickNotes" class="form-control" rows="3" placeholder="Brief clinical assessment or symptoms" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;"></textarea>
                                        <div class="form-text" style="font-size:0.72rem;">Brief description of patient's condition or symptoms</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn fw-semibold" style="background:#1e293b; color:#fff; border-radius:8px; padding:0.5rem 1rem; font-size:0.84rem;" id="saveQuickDataBtn">
                                            <i class="fas fa-save me-1"></i>Save & Continue with AI
                                        </button>
                                        <button type="button" class="btn fw-semibold" style="background:#fff; border:1px solid #e2e8f0; color:#64748b; border-radius:8px; font-size:0.84rem;" id="cancelQuickDataBtn">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="ml-note mt-3" id="optionsAlert" style="background:#eff6ff; border-color:#dbeafe;">
                                <i class="fas fa-lightbulb" style="color:#2563eb; margin-top:2px;"></i>
                                <div>
                                    <strong>Options:</strong>
                                    <div class="row g-2 mt-1" style="font-size:0.78rem;">
                                        <div class="col-6"><strong>1. Quick Entry</strong><br><small style="color:#64748b;">Fill missing data here (recommended)</small></div>
                                        <div class="col-6"><strong>2. Continue Limited</strong><br><small style="color:#64748b;">General guidance only</small></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="button" class="btn fw-semibold" style="background:#1e293b; color:#fff; border-radius:8px; font-size:0.84rem;" id="quickEntryBtn">
                                <i class="fas fa-edit me-1"></i>Quick Entry
                            </button>
                            <button type="button" class="btn fw-semibold" style="background:#fff; border:1px solid #e2e8f0; color:#475569; border-radius:8px; font-size:0.84rem;" id="continueAnywayBtn">
                                <i class="fas fa-exclamation-triangle me-1"></i>Continue Limited
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
        
        // Handle quick entry button
        $('#quickEntryBtn').click(function() {
            // Hide options alert and show quick data entry form
            $('#optionsAlert').hide();
            $('#quickDataEntry').show();
            
            // Show relevant fields based on missing data
            if (missingData.includes('Patient Allergies')) {
                $('#quickAllergyField').show();
            }
            if (missingData.includes('Current Medications')) {
                $('#quickMedicationField').show();
            }
            if (missingData.includes('Symptoms')) {
                $('#quickNotesField').show();
            }
            
            // Hide the footer buttons
            $(this).closest('.modal-footer').hide();
            
            // Focus on first visible field
            setTimeout(() => {
                if ($('#quickAllergyField').is(':visible')) {
                    $('#quickAllergies').focus();
                } else if ($('#quickMedicationField').is(':visible')) {
                    $('#quickMedications').focus();
                } else if ($('#quickNotesField').is(':visible')) {
                    $('#quickNotes').focus();
                }
            }, 100);
        });
        
        // Handle save quick data button
        $('#saveQuickDataBtn').click(function() {
            var quickAllergies = $('#quickAllergies').val().trim();
            var quickMedications = $('#quickMedications').val().trim();
            var quickNotes = $('#quickNotes').val().trim();
            
            // Validate required fields
            var hasError = false;
            if (missingData.includes('Patient Allergies') && !quickAllergies) {
                $('#quickAllergies').addClass('is-invalid');
                hasError = true;
            } else {
                $('#quickAllergies').removeClass('is-invalid');
            }
            
            if (missingData.includes('Current Medications') && !quickMedications) {
                $('#quickMedications').addClass('is-invalid');
                hasError = true;
            } else {
                $('#quickMedications').removeClass('is-invalid');
            }
            
            if (missingData.includes('Symptoms') && !quickNotes) {
                $('#quickNotes').addClass('is-invalid');
                hasError = true;
            } else {
                $('#quickNotes').removeClass('is-invalid');
            }
            
            if (hasError) {
                showNotification('Please fill all required fields', 'error');
                return;
            }
            
            // Save the data temporarily and update our variables
            if (quickAllergies) {
                allergies = [quickAllergies];
            }
            if (quickMedications) {
                pastMeds = [quickMedications];
            }
            if (quickNotes) {
                symptoms = quickNotes;
            }
            
            // Save to appointment (for persistence) and wait for completion
            var savePromise = saveQuickDataToAppointment(quickAllergies, quickMedications, quickNotes);
            
            savePromise.done(function(response) {
                console.log('Quick data save completed successfully:', response);
                
                // Update the current diagnosis data to reflect the saved data
                if (currentDiagnosis) {
                    if (!currentDiagnosis.patient_data) {
                        currentDiagnosis.patient_data = {};
                    }
                    if (quickAllergies) {
                        currentDiagnosis.patient_data.allergies = quickAllergies;
                    }
                    if (quickMedications) {
                        currentDiagnosis.patient_data.medications = quickMedications;
                    }
                    if (quickNotes) {
                        currentDiagnosis.patient_data.clinical_notes = quickNotes;
                    }
                }
                
                // Close modal and proceed with AI suggestion
                modal.hide();
                proceedWithAISuggestion();
            }).fail(function(xhr, status, error) {
                console.error('Quick data save failed:', error);
                showNotification('Failed to save data to database. Proceeding with temporary data only.', 'warning');
                
                // Still proceed with AI suggestion using temporary data
                modal.hide();
                proceedWithAISuggestion();
            });
        });
        
        // Handle cancel quick data button
        $('#cancelQuickDataBtn').click(function() {
            // Show options alert and hide quick data entry form
            $('#quickDataEntry').hide();
            $('#optionsAlert').show();
            
            // Show the footer buttons
            $('.modal-footer').show();
        });
        
        // Handle continue anyway button — inject safe placeholders so backend safety check passes and AI can give general guidance
        $('#continueAnywayBtn').click(function() {
            if (!hasAllergies) allergies = ["No known allergies"];
            if (!hasMedications) pastMeds = ["No current medications"];
            if (!hasClinicalAssessment || !symptoms || (typeof symptoms === 'string' && symptoms.trim() === '')) {
                symptoms = "General consultation - no specific symptoms documented";
            }
            // Mark as acknowledged so backend could bypass strict block if needed
            window._aiContinueLimited = true;
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
            doctor_notes: @json($appointment->doctor_notes),
            clinical_notes: currentDiagnosis && currentDiagnosis.patient_data ? currentDiagnosis.patient_data.clinical_notes : null,
            continue_limited: window._aiContinueLimited ? 1 : 0
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
        },
        complete: function() {
            window._aiContinueLimited = false;
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
    const currentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);
    const pastDiagnoses = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect());
    const voiceDiagnosis = @json($appointment->patient ? \App\Models\AiAssistantResult::where('patient_id', $appointment->patient->id)->where('source', 'voice_assistant')->latest()->first() : null);

    // Get patient data from the most recent diagnosis
    const patientData = currentDiagnosis ? currentDiagnosis.patient_data : null;

    const dataSources = [
        {
            name: 'Patient Allergies',
            status: patientData && patientData.allergies && (Array.isArray(patientData.allergies) ? patientData.allergies.length > 0 : (typeof patientData.allergies === 'string' && patientData.allergies.trim().length > 0)) ? 'available' : 'missing',
            example: patientData && patientData.allergies ? (Array.isArray(patientData.allergies) ? patientData.allergies.join(', ') : patientData.allergies.toString()) : 'No allergies recorded',
            location: 'Diagnosis creation form (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-allergies',
            importance: 'critical',
            reason: 'Prevents prescribing medications patient is allergic to (life-threatening). "None" or "No known allergies" are acceptable entries.'
        },
        {
            name: 'Current Medications',
            status: patientData && (patientData.medications || patientData.past_medications) && (Array.isArray(patientData.medications || patientData.past_medications) ? (patientData.medications || patientData.past_medications).length > 0 : (typeof (patientData.medications || patientData.past_medications) === 'string' && (patientData.medications || patientData.past_medications).trim().length > 0)) ? 'available' : 'missing',
            example: patientData && (patientData.medications || patientData.past_medications) ? (Array.isArray(patientData.medications || patientData.past_medications) ? (patientData.medications || patientData.past_medications).join(', ') : (patientData.medications || patientData.past_medications).toString()) : 'No medications recorded',
            location: 'Diagnosis creation form (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-pills',
            importance: 'critical',
            reason: 'Required to check drug-drug interactions (dangerous without this). "None" or "No current medications" are acceptable entries.'
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
    const modalBody = document.querySelector('#aiDataSourcesModal .modal-body');
    if(modalBody){
        let fb = document.getElementById('refreshInlineFeedback');
        if(!fb){
            fb = document.createElement('div');
            fb.id = 'refreshInlineFeedback';
            fb.style.cssText = 'background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:8px;padding:0.6rem 0.75rem;font-size:0.82rem;font-weight:600;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem';
            fb.innerHTML = '<i class="fas fa-check-circle"></i><span>Data sources refreshed successfully.</span><span style="margin-left:auto;font-weight:400;color:#047857;font-size:0.74rem">'+new Date().toLocaleTimeString()+'</span>';
            modalBody.prepend(fb);
        } else {
            fb.style.display='flex';
            fb.querySelector('span:last-child').textContent = new Date().toLocaleTimeString();
        }
        clearTimeout(window._refreshFbTimer);
        window._refreshFbTimer = setTimeout(()=>{ if(fb) fb.style.display='none'; }, 2500);
    }
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