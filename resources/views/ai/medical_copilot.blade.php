@push('styles')
<style>
.ai-copilot-modal {
    max-width: 900px;
    margin: 1.75rem auto;
}

.ai-copilot-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 0.5rem 0.5rem 0 0;
}

.ai-copilot-body {
    padding: 2rem;
    max-height: 600px;
    overflow-y: auto;
}

.ai-copilot-footer {
    padding: 1rem 2rem;
    background-color: #f8f9fa;
    border-radius: 0 0 0.5rem 0.5rem;
}

.copilot-tab {
    cursor: pointer;
    padding: 0.75rem 1.5rem;
    border: none;
    background-color: transparent;
    color: #6c757d;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.copilot-tab.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    font-weight: 500;
}

.copilot-tab-content {
    display: none;
    padding: 1.5rem 0;
}

.copilot-tab-content.active {
    display: block;
}

.copilot-section {
    margin-bottom: 2rem;
}

.copilot-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.copilot-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.copilot-badge {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.copilot-content {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.25rem;
    border-left: 4px solid #0d6efd;
}

.copilot-list {
    list-style-type: none;
    padding-left: 0;
}

.copilot-list li {
    padding: 0.5rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.copilot-list li:before {
    content: "•";
    color: #0d6efd;
    position: absolute;
    left: 0;
    font-weight: bold;
}

.copilot-warning {
    background-color: #fff3cd;
    border-left-color: #ffc107;
}

.copilot-danger {
    background-color: #f8d7da;
    border-left-color: #dc3545;
}

.copilot-success {
    background-color: #d1e7dd;
    border-left-color: #198754;
}

.copilot-info {
    background-color: #cff4fc;
    border-left-color: #0dcaf0;
}

.copilot-disclaimer {
    font-size: 0.875rem;
    color: #6c757d;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-top: 1rem;
    border: 1px solid #e9ecef;
}

.copilot-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.copilot-checkbox {
    margin-right: 0.5rem;
}

.edit-copilot-btn {
    cursor: pointer;
    color: #0d6efd;
    font-size: 0.875rem;
}

.edit-copilot-btn:hover {
    text-decoration: underline;
}

.copilot-loading {
    display: none;
    text-align: center;
    padding: 2rem;
}

.copilot-loading.active {
    display: block;
}

.copilot-loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 0.25rem solid #f3f3f3;
    border-top: 0.25rem solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.copilot-error {
    color: #dc3545;
    background-color: #f8d7da;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
    border: 1px solid #f5c2c7;
}

.copilot-compliance-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
    margin-top: 1rem;
    font-style: italic;
}
</style>
@endpush

<div class="modal fade" id="aiMedicalCopilotModal" tabindex="-1" aria-labelledby="aiMedicalCopilotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl ai-copilot-modal">
        <div class="modal-content">
            <div class="modal-header ai-copilot-header">
                <h5 class="modal-title" id="aiMedicalCopilotModalLabel">
                    <i class="fas fa-brain me-2"></i>AI Medical Copilot (Draft Only)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ai-copilot-body">
                <div class="copilot-loading" id="copilotLoading">
                    <div class="copilot-loading-spinner"></div>
                    <h5 class="text-primary">AI Medical Copilot is analyzing...</h5>
                    <p class="text-muted">Processing clinical data and generating decision support insights</p>
                </div>

                <div class="copilot-error" id="copilotError" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="copilotErrorMessage"></span>
                </div>

                <div id="copilotContent" style="display: none;">
                    <!-- Tab Navigation -->
                    <div class="d-flex justify-content-start mb-3 border-bottom">
                        <button class="copilot-tab active" data-tab="summary">
                            <i class="fas fa-file-medical me-1"></i>Summary
                        </button>
                        <button class="copilot-tab" data-tab="considerations">
                            <i class="fas fa-list-check me-1"></i>Considerations
                        </button>
                        <button class="copilot-tab" data-tab="questions">
                            <i class="fas fa-question-circle me-1"></i>Questions
                        </button>
                        <button class="copilot-tab" data-tab="red-flags">
                            <i class="fas fa-flag me-1"></i>Red Flags
                        </button>
                        <button class="copilot-tab" data-tab="history">
                            <i class="fas fa-history me-1"></i>Patient History
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div id="copilotTabs">
                        <!-- Summary Tab -->
                        <div class="copilot-tab-content active" data-tab-content="summary">
                            <div class="copilot-section">
                                <div class="copilot-section-header">
                                    <h6 class="copilot-section-title">
                                        <i class="fas fa-file-medical me-2"></i>Medical Case Summary
                                    </h6>
                                    <span class="badge copilot-badge bg-primary">
                                        <i class="fas fa-check-circle me-1"></i>AI-Generated
                                    </span>
                                </div>
                                <div class="copilot-content" id="copilotSummary">
                                    <p class="text-muted">Loading medical case summary...</p>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input copilot-checkbox" type="checkbox" id="includeSummaryInNote">
                                    <label class="form-check-label" for="includeSummaryInNote">
                                        Include in clinical note
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Considerations Tab -->
                        <div class="copilot-tab-content" data-tab-content="considerations">
                            <div class="copilot-section">
                                <div class="copilot-section-header">
                                    <h6 class="copilot-section-title">
                                        <i class="fas fa-list-check me-2"></i>Differential Considerations
                                    </h6>
                                    <span class="badge copilot-badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Not Diagnoses
                                    </span>
                                </div>
                                <div class="copilot-content copilot-warning" id="copilotConsiderations">
                                    <p class="text-muted">Loading differential considerations...</p>
                                </div>
                                <div class="copilot-disclaimer">
                                    <strong>⚠️ For clinical consideration only. Physician judgment required.</strong>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input copilot-checkbox" type="checkbox" id="includeConsiderationsInNote">
                                    <label class="form-check-label" for="includeConsiderationsInNote">
                                        Include in clinical note
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Questions Tab -->
                        <div class="copilot-tab-content" data-tab-content="questions">
                            <div class="copilot-section">
                                <div class="copilot-section-header">
                                    <h6 class="copilot-section-title">
                                        <i class="fas fa-question-circle me-2"></i>Suggested Follow-up Questions
                                    </h6>
                                    <span class="badge copilot-badge bg-info">
                                        <i class="fas fa-lightbulb me-1"></i>Clinical Insights
                                    </span>
                                </div>
                                <div class="copilot-content copilot-info" id="copilotQuestions">
                                    <p class="text-muted">Loading follow-up questions...</p>
                                </div>
                                <div class="copilot-disclaimer">
                                    <strong>💡 These questions help raise diagnostic quality and reduce oversight.</strong>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input copilot-checkbox" type="checkbox" id="includeQuestionsInNote">
                                    <label class="form-check-label" for="includeQuestionsInNote">
                                        Include in clinical note
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Red Flags Tab -->
                        <div class="copilot-tab-content" data-tab-content="red-flags">
                            <div class="copilot-section">
                                <div class="copilot-section-header">
                                    <h6 class="copilot-section-title">
                                        <i class="fas fa-flag me-2"></i>Red Flags Detection
                                    </h6>
                                    <span class="badge copilot-badge bg-danger">
                                        <i class="fas fa-exclamation-circle me-1"></i>Urgent Attention
                                    </span>
                                </div>
                                <div class="copilot-content copilot-danger" id="copilotRedFlags">
                                    <p class="text-muted">Loading red flags analysis...</p>
                                </div>
                                <div class="copilot-disclaimer">
                                    <strong>⚠️ Consider urgent evaluation if clinically indicated.</strong>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input copilot-checkbox" type="checkbox" id="includeRedFlagsInNote">
                                    <label class="form-check-label" for="includeRedFlagsInNote">
                                        Include in clinical note
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Patient History Tab -->
                        <div class="copilot-tab-content" data-tab-content="history">
                            <div class="copilot-section">
                                <div class="copilot-section-header">
                                    <h6 class="copilot-section-title">
                                        <i class="fas fa-history me-2"></i>Patient Medical History
                                    </h6>
                                    <span class="badge copilot-badge bg-info">
                                        <i class="fas fa-database me-1"></i>Historical Data
                                    </span>
                                </div>
                                <div class="copilot-content" id="copilotHistory">
                                    <p class="text-muted">Loading patient history...</p>
                                </div>
                                <div class="copilot-disclaimer">
                                    <strong>📋 Patient history was used in AI analysis to provide context-aware recommendations.</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance Information -->
                    <div class="copilot-compliance-label">
                        <i class="fas fa-shield-alt me-1"></i>
                        <span id="copilotComplianceLabel">AI-generated draft. Physician verified.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer ai-copilot-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-primary" id="saveCopilotAnalysis">
                    <i class="fas fa-save me-1"></i>Save Analysis
                </button>
            </div>
        </div>
    </div>
</div>

<!-- AI History Modal -->
<div class="modal fade" id="aiHistoryModal" tabindex="-1" aria-labelledby="aiHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl ai-copilot-modal">
        <div class="modal-content">
            <div class="modal-header ai-copilot-header">
                <h5 class="modal-title" id="aiHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>Patient AI Analysis History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ai-copilot-body">
                <div id="aiHistoryContent">
                    <!-- AI analyses will be loaded here -->
                </div>
            </div>
            <div class="modal-footer ai-copilot-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// AI Medical Copilot functionality
$(document).ready(function() {
    // Tab switching
    $('.copilot-tab').click(function() {
        const tabId = $(this).data('tab');

        // Update tab buttons
        $('.copilot-tab').removeClass('active');
        $(this).addClass('active');

        // Update tab content
        $('.copilot-tab-content').removeClass('active');
        $(`.copilot-tab-content[data-tab-content="${tabId}"]`).addClass('active');
    });

    // Function to open AI Medical Copilot modal
    window.openAIMedicalCopilot = function(appointmentId) {
        const modal = new bootstrap.Modal(document.getElementById('aiMedicalCopilotModal'));
        modal.show();

        // Show loading state
        $('#copilotLoading').addClass('active');
        $('#copilotContent').hide();
        $('#copilotError').hide();

        // Collect structured data from the appointment
        const structuredData = collectStructuredData(appointmentId);

        // Call AI Medical Copilot API
        callAIMedicalCopilotAPI(appointmentId, structuredData);
    };

    // Function to collect structured data from the appointment
    function collectStructuredData(appointmentId) {
        // This would be populated with actual data from the appointment
        // For now, we'll use sample data that matches the required structure

        return {
            complaint: {
                chief_complaint: $('#appointmentReason').val() || 'Chest pain',
                onset: '2 days',
                severity: 'Moderate',
                associated_symptoms: ['shortness of breath', 'fatigue']
            },
            vitals: {
                bp: '150/95',
                hr: 105,
                spo2: 94,
                temperature: 37.8
            },
            history: {
                chronic_conditions: ['Hypertension', 'Diabetes'],
                medications: ['Metformin', 'Amlodipine'],
                allergies: ['Penicillin']
            },
            labs: {
                troponin: 'pending',
                cbc: {
                    wbc: 14.2,
                    hb: 13.5
                }
            },
            previous_visits: {
                last_diagnoses: ['Hypertension', 'Type 2 Diabetes'],
                recent_er_visits: ['None in last 6 months'],
                patterns: ['Recurrent chest pain episodes']
            }
        };
    }

    // Function to call AI Medical Copilot API
    function callAIMedicalCopilotAPI(appointmentId, structuredData) {
        $.ajax({
            url: `/ai/appointments/${appointmentId}/medical-copilot`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                complaint: structuredData.complaint,
                vitals: structuredData.vitals,
                history: structuredData.history,
                labs: structuredData.labs,
                previous_visits: structuredData.previous_visits
            },
            success: function(response) {
                // Hide loading, show content
                $('#copilotLoading').removeClass('active');
                $('#copilotContent').show();

                // Check for errors
                if (response.error) {
                    showCopilotError(response.message || response.error);
                    return;
                }

                if (response.disabled) {
                    showCopilotError('AI Medical Copilot is currently disabled');
                    return;
                }

                // Store the response for saving
                window.currentCopilotResponse = response;
                window.currentAppointmentId = appointmentId;

                // Populate the UI with AI analysis
                populateCopilotUI(response);

                // Log success
                console.log('AI Medical Copilot analysis successful', response);
            },
            error: function(xhr, status, error) {
                // Hide loading, show error
                $('#copilotLoading').removeClass('active');

                const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Failed to connect to AI Medical Copilot';
                showCopilotError(errorMessage);

                // Log error
                console.error('AI Medical Copilot error:', errorMessage);
            }
        });
    }

    // Function to show error
    function showCopilotError(message) {
        $('#copilotErrorMessage').text(message);
        $('#copilotError').show();
        $('#copilotContent').hide();
    }

    // Function to populate UI with AI analysis
    function populateCopilotUI(response) {
        // Medical Case Summary
        const summaryContent = `
            <p class="mb-0">${response.medical_case_summary}</p>
            <div class="mt-2 small text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Smart summary for quick case understanding
            </div>
        `;
        $('#copilotSummary').html(summaryContent);

        // Differential Considerations
        let considerationsHtml = '<p><strong>Possible considerations (not diagnoses):</strong></p>';
        considerationsHtml += '<ul class="copilot-list">';
        response.differential_considerations.forEach(item => {
            if (typeof item === 'object' && item.consideration) {
                considerationsHtml += `<li>
                    <strong>${item.consideration}</strong>
                    ${item.rationale ? `<br><small class="text-muted">${item.rationale}</small>` : ''}
                </li>`;
            } else {
                // Fallback for string format
                considerationsHtml += `<li>${item}</li>`;
            }
        });
        considerationsHtml += '</ul>';

        if (response.differential_considerations.length === 0) {
            considerationsHtml = '<p class="text-muted">No specific considerations identified based on current data.</p>';
        }

        $('#copilotConsiderations').html(considerationsHtml);

        // Follow-up Questions
        let questionsHtml = '<p><strong>Questions to help complete the clinical picture:</strong></p>';
        questionsHtml += '<ul class="copilot-list">';
        response.follow_up_questions.forEach(question => {
            questionsHtml += `<li>${question}</li>`;
        });
        questionsHtml += '</ul>';

        if (response.follow_up_questions.length === 0) {
            questionsHtml = '<p class="text-muted">No additional questions suggested based on current information.</p>';
        }

        $('#copilotQuestions').html(questionsHtml);

        // Red Flags
        let redFlagsHtml = '<p><strong>Potential red flags detected:</strong></p>';
        redFlagsHtml += '<ul class="copilot-list">';
        response.red_flags.forEach(flag => {
            redFlagsHtml += `<li>${flag}</li>`;
        });
        redFlagsHtml += '</ul>';

        if (response.red_flags.length === 0) {
            redFlagsHtml = '<p class="text-success">No immediate red flags detected based on available data.</p>';
        }

        $('#copilotRedFlags').html(redFlagsHtml);

        // Compliance label
        if (response.compliance && response.compliance.label) {
            $('#copilotComplianceLabel').text(response.compliance.label);
        }

        // Patient History (if available in response)
        if (response.patient_history) {
            const history = response.patient_history;
            let historyHtml = '';

            if (history.previous_diagnoses && history.previous_diagnoses.length > 0) {
                historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-stethoscope me-1"></i>Previous Diagnoses:</h6>';
                historyHtml += '<ul class="copilot-list mb-3">';
                history.previous_diagnoses.forEach(diagnosis => {
                    historyHtml += `<li>${diagnosis}</li>`;
                });
                historyHtml += '</ul>';
            }

            if (history.chronic_conditions && history.chronic_conditions.length > 0) {
                historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-heartbeat me-1"></i>Chronic Conditions:</h6>';
                historyHtml += '<ul class="copilot-list mb-3">';
                history.chronic_conditions.forEach(condition => {
                    historyHtml += `<li>${condition}</li>`;
                });
                historyHtml += '</ul>';
            }

            if (history.previous_ai_analyses && history.previous_ai_analyses.length > 0) {
                historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-brain me-1"></i>Previous AI Analyses:</h6>';
                history.previous_ai_analyses.forEach(analysis => {
                    historyHtml += `<div class="border-start border-info border-3 ps-3 mb-3">
                        <small class="text-muted">${analysis.generated_at}</small>
                        <p class="mb-1">${analysis.summary}</p>
                        ${analysis.red_flags && analysis.red_flags.length > 0 ?
                            `<small class="text-danger">⚠️ Red flags: ${analysis.red_flags.join(', ')}</small>` :
                            '<small class="text-success">✅ No red flags</small>'}
                    </div>`;
                });
            }

            if (!historyHtml) {
                historyHtml = '<p class="text-muted">No significant patient history available.</p>';
            }

            $('#copilotHistory').html(historyHtml);
        }

        // Add disclaimer if available
        if (response.legal_disclaimer) {
            $('.copilot-disclaimer').last().after(`
                <div class="copilot-disclaimer mt-3">
                    <i class="fas fa-shield-alt me-1"></i>
                    ${response.legal_disclaimer}
                </div>
            `);
        }
    }

    // Save analysis button
    $('#saveCopilotAnalysis').click(function() {
        const appointmentId = window.currentAppointmentId; // This should be set when opening the modal

        if (!appointmentId) {
            showNotification('Error: Appointment ID not found', 'error');
            return;
        }

        // Collect current analysis data from the UI
        const analysisData = collectAnalysisData();

        // Include checkboxes for clinical note inclusion
        const includeInNote = {
            summary: $('#includeSummaryInNote').is(':checked'),
            considerations: $('#includeConsiderationsInNote').is(':checked'),
            questions: $('#includeQuestionsInNote').is(':checked'),
            red_flags: $('#includeRedFlagsInNote').is(':checked')
        };

        // Save the analysis
        saveAICopilotAnalysis(appointmentId, analysisData, includeInNote);
    });

    // Function to collect analysis data from the current UI state
    function collectAnalysisData() {
        // Extract data from the global response object if available
        if (window.currentCopilotResponse) {
            return window.currentCopilotResponse;
        }

        // Fallback: extract from current display (less reliable)
        return {
            medical_case_summary: $('#copilotSummary p').first().text().trim() || 'No summary available',
            differential_considerations: extractListItems('#copilotConsiderations li'),
            follow_up_questions: extractListItems('#copilotQuestions li'),
            red_flags: extractListItems('#copilotRedFlags li'),
            disclaimer: 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.',
            compliance: {
                ai_generated: true,
                physician_verification_required: true,
                label: 'AI-generated draft. Physician verified.',
                timestamp: new Date().toISOString(),
                generated_by: 'AI Medical Copilot',
                version: 'ai-copilot-clinical-v1.1'
            },
            legal_disclaimer: 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.'
        };
    }

    // Helper function to extract list items from HTML
    function extractListItems(selector) {
        const items = [];
        $(selector).each(function() {
            const text = $(this).clone().children().remove().end().text().trim();
            if (text && text !== 'Loading...' && !text.includes('Loading')) {
                items.push(text);
            }
        });
        return items;
    }

    // Function to save AI copilot analysis
    function saveAICopilotAnalysis(appointmentId, analysisData, includeInNote) {
        $.ajax({
            url: `/ai/appointments/${appointmentId}/ai-analyses/save`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                analysis_data: analysisData,
                include_in_note: includeInNote
            },
            success: function(response) {
                if (response.success) {
                    showNotification('AI Medical Copilot analysis saved successfully!', 'success');

                    // Close the modal after a short delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('aiMedicalCopilotModal'));
                        modal.hide();
                    }, 1500);
                } else {
                    showNotification(response.message || 'Failed to save analysis', 'error');
                }
            },
            error: function(xhr, status, error) {
                const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Failed to save AI analysis';
                showNotification(errorMessage, 'error');
                console.error('Save AI analysis error:', errorMessage);
            }
        });
    }

    // Function to view patient's AI analysis history
    window.viewPatientAIAnalyses = function(patientId) {
        // Show loading state
        const historyModal = new bootstrap.Modal(document.getElementById('aiHistoryModal'));
        historyModal.show();

        // Load patient AI analyses
        loadPatientAIAnalyses(patientId);
    };

    // Function to load patient AI analyses
    function loadPatientAIAnalyses(patientId) {
        $('#aiHistoryContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading AI analysis history...</p></div>');

        $.ajax({
            url: `/ai/patients/${patientId}/ai-analyses`,
            method: 'GET',
            success: function(response) {
                displayAIAnalyses(response.data || []);
            },
            error: function(xhr, status, error) {
                const errorMessage = xhr.responseJSON?.message || 'Failed to load AI analysis history';
                $('#aiHistoryContent').html(`<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${errorMessage}</div>`);
            }
        });
    }

    // Function to display AI analyses
    function displayAIAnalyses(analyses) {
        if (!analyses || analyses.length === 0) {
            $('#aiHistoryContent').html(`
                <div class="text-center py-5">
                    <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No AI Analyses Found</h5>
                    <p class="text-muted">This patient hasn't had any AI Medical Copilot analyses saved yet.</p>
                </div>
            `);
            return;
        }

        let html = '<div class="ai-analyses-timeline">';

        analyses.forEach(analysis => {
            const analysisData = typeof analysis.analysis_data === 'string' ?
                JSON.parse(analysis.analysis_data) : analysis.analysis_data;

            html += `
                <div class="ai-analysis-card mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">
                                    <i class="fas fa-brain me-2"></i>AI Medical Copilot Analysis
                                </h6>
                                <small>${new Date(analysis.generated_at).toLocaleDateString()} at ${new Date(analysis.generated_at).toLocaleTimeString()}</small>
                            </div>
                            <div class="d-flex gap-2">
                                ${analysis.status === 'reviewed' ?
                                    '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Reviewed</span>' :
                                    '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending Review</span>'}
                                <button class="btn btn-sm btn-outline-light" onclick="viewFullAnalysis(${analysis.id})">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-file-medical me-1"></i>Summary</h6>
                                    <p class="mb-3">${analysisData.medical_case_summary || 'No summary available'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-warning"><i class="fas fa-list-check me-1"></i>Key Considerations</h6>
                                    <ul class="mb-3 small">
                                        ${displayConsiderations(analysisData.differential_considerations || [])}
                                    </ul>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-info"><i class="fas fa-question-circle me-1"></i>Follow-up Questions</h6>
                                    <ul class="mb-3 small">
                                        ${displayQuestions(analysisData.follow_up_questions || [])}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-danger"><i class="fas fa-flag me-1"></i>Red Flags</h6>
                                    <ul class="mb-3 small">
                                        ${displayRedFlags(analysisData.red_flags || [])}
                                    </ul>
                                </div>
                            </div>
                            ${analysis.reviewed_at ? `
                                <div class="border-top pt-3 mt-3">
                                    <h6 class="text-success"><i class="fas fa-user-md me-1"></i>Physician Review</h6>
                                    <p class="mb-1 small text-muted">Reviewed by Dr. ${analysis.reviewer?.name || 'Unknown'} on ${new Date(analysis.reviewed_at).toLocaleDateString()}</p>
                                    ${analysis.doctor_notes ? `<p class="mb-0">${analysis.doctor_notes}</p>` : '<p class="text-muted small">No additional notes</p>'}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        $('#aiHistoryContent').html(html);
    }

    // Helper functions for displaying analysis components
    function displayConsiderations(considerations) {
        if (!considerations || considerations.length === 0) return '<li class="text-muted">No considerations recorded</li>';

        return considerations.slice(0, 3).map(item => {
            if (typeof item === 'object' && item.consideration) {
                return `<li><strong>${item.consideration}</strong><br><small class="text-muted">${item.rationale || ''}</small></li>`;
            } else {
                return `<li>${item}</li>`;
            }
        }).join('');
    }

    function displayQuestions(questions) {
        if (!questions || questions.length === 0) return '<li class="text-muted">No questions recorded</li>';
        return questions.slice(0, 3).map(question => `<li>${question}</li>`).join('');
    }

    function displayRedFlags(flags) {
        if (!flags || flags.length === 0) return '<li class="text-success">No red flags detected</li>';
        return flags.slice(0, 3).map(flag => `<li class="text-danger">${flag}</li>`).join('');
    }

    // Function to view full analysis details
    window.viewFullAnalysis = function(analysisId) {
        // Redirect to the full analysis details page
        window.location.href = `/ai/ai-analyses/${analysisId}`;
    }

    // Helper function to show notifications
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

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
});
</script>
@endpush