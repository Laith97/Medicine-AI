{{-- resources/views/ai/documentation-intelligence.blade.php --}}
<div class="card shadow-sm border-0 mb-4" id="ai-documentation-card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary">
            <i class="fas fa-robot mr-2"></i> AI-Powered Clinical Documentation
        </h5>
        <div class="card-tools">
            <span class="badge badge-pill badge-info px-3 py-2" id="doc-status-badge">
                <i class="fas fa-info-circle mr-1"></i> Ready to Generate
            </span>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Documentation Status & Metrics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 rounded bg-light border">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary-soft text-primary mr-3 p-3 rounded-circle">
                            <i class="fas fa-file-medical fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small uppercase font-weight-bold">Documentation Status</p>
                            <h6 class="mb-0" id="doc-status-text">Not Generated</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded bg-light border">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success-soft text-success mr-3 p-3 rounded-circle">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small uppercase font-weight-bold">Completeness Score</p>
                            <h6 class="mb-0" id="completeness-score-text">0%</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap mb-4">
            <button id="generate-doc-btn" class="btn btn-primary btn-lg mr-2 mb-2 shadow-sm">
                <i class="fas fa-magic mr-2"></i> Generate Documentation
            </button>
            <button id="validate-doc-btn" class="btn btn-success btn-lg mr-2 mb-2 shadow-sm" style="display:none;">
                <i class="fas fa-check-double mr-2"></i> Validate & Approve
            </button>
            <button id="edit-doc-btn" class="btn btn-warning btn-lg mr-2 mb-2 shadow-sm" style="display:none;">
                <i class="fas fa-edit mr-2"></i> Edit Sections
            </button>
            <button id="export-doc-btn" class="btn btn-outline-secondary btn-lg mb-2" style="display:none;">
                <i class="fas fa-file-export mr-2"></i> Export PDF
            </button>
        </div>

        <!-- Documentation Sections -->
        <div id="documentation-container" style="display:none;" class="mt-4">
            <div class="row">
                <div class="col-md-6">
                    <!-- Chief Complaint -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold text-dark">Chief Complaint</span>
                            <button class="btn btn-xs btn-link text-primary copy-btn" data-target="chief-complaint">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <textarea id="chief-complaint" class="form-control border-0 bg-white" rows="3" readonly></textarea>
                        </div>
                        <div class="card-footer bg-white py-1 px-3 border-top-0">
                            <small class="text-muted" id="cc-confidence"></small>
                        </div>
                    </div>

                    <!-- History of Present Illness -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold text-dark">History of Present Illness</span>
                            <button class="btn btn-xs btn-link text-primary copy-btn" data-target="history-present-illness">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <textarea id="history-present-illness" class="form-control border-0 bg-white" rows="6" readonly></textarea>
                        </div>
                        <div class="card-footer bg-white py-1 px-3 border-top-0">
                            <small class="text-muted" id="hpi-confidence"></small>
                        </div>
                    </div>

                    <!-- Physical Exam -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold text-dark">Physical Exam Findings</span>
                            <button class="btn btn-xs btn-link text-primary copy-btn" data-target="physical-exam">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <textarea id="physical-exam" class="form-control border-0 bg-white" rows="4" readonly></textarea>
                        </div>
                        <div class="card-footer bg-white py-1 px-3 border-top-0">
                            <small class="text-muted" id="pe-confidence"></small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- Assessment -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold text-dark">Assessment</span>
                            <button class="btn btn-xs btn-link text-primary copy-btn" data-target="assessment">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <textarea id="assessment" class="form-control border-0 bg-white" rows="4" readonly></textarea>
                        </div>
                        <div class="card-footer bg-white py-1 px-3 border-top-0">
                            <small class="text-muted" id="assessment-confidence"></small>
                        </div>
                    </div>

                    <!-- Plan -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold text-dark">Plan & Follow-up</span>
                            <button class="btn btn-xs btn-link text-primary copy-btn" data-target="plan">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <textarea id="plan" class="form-control border-0 bg-white" rows="6" readonly></textarea>
                        </div>
                        <div class="card-footer bg-white py-1 px-3 border-top-0">
                            <small class="text-muted" id="plan-confidence"></small>
                        </div>
                    </div>

                    <!-- Suggested Codes -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <span class="font-weight-bold text-dark">Suggested Medical Codes</span>
                        </div>
                        <div class="card-body p-3">
                            <div id="suggested-codes-container" class="bg-light rounded p-2" style="min-height: 100px;">
                                <div class="text-center py-4 text-muted" id="no-codes-msg">
                                    <i class="fas fa-barcode fa-2x mb-2 d-block opacity-5"></i>
                                    No codes suggested yet
                                </div>
                                <div id="codes-list" class="list-group list-group-flush" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Missing Elements Warning -->
            <div id="missing-elements-alert" class="alert alert-custom-warning border-0 shadow-sm mb-4" style="display:none;">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h6 class="font-weight-bold mb-1">Missing Elements</h6>
                        <ul id="missing-elements-list" class="mb-0 pl-3 small"></ul>
                    </div>
                </div>
            </div>

            <!-- Compliance Flags -->
            <div id="compliance-flags-alert" class="alert alert-custom-info border-0 shadow-sm mb-4" style="display:none;">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-info-circle fa-2x text-info"></i>
                    </div>
                    <div>
                        <h6 class="font-weight-bold mb-1">Compliance Notes</h6>
                        <ul id="compliance-flags-list" class="mb-0 pl-3 small"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(0, 123, 255, 0.1); }
    .bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .alert-custom-warning { background-color: #fff9e6; border-left: 4px solid #ffc107; }
    .alert-custom-info { background-color: #eef7ff; border-left: 4px solid #17a2b8; }
    .opacity-5 { opacity: 0.5; }
    .btn-xs { padding: 0.1rem 0.3rem; font-size: 0.75rem; }
    #ai-documentation-card textarea[readonly] { background-color: #fff !important; cursor: default; }
    #ai-documentation-card textarea:not([readonly]) { background-color: #fff9e6 !important; border: 1px solid #ffc107 !important; }
    .code-item:hover { background-color: rgba(0,0,0,0.02); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let appointmentId = {{ $appointment->id ?? 'null' }};
    let transcriptionId = {{ $transcription->id ?? 'null' }};
    let currentDocId = null;

    // Global setters for dynamic integration
    window.setAIDocumentationIds = function(aptId, transId) {
        appointmentId = aptId;
        transcriptionId = transId;
        console.log('AI Documentation IDs updated:', { appointmentId, transcriptionId });
        
        if (appointmentId) {
            checkExistingDocumentation();
        }
    };

    const generateBtn = document.getElementById('generate-doc-btn');
    const validateBtn = document.getElementById('validate-doc-btn');
    const editBtn = document.getElementById('edit-doc-btn');
    const exportBtn = document.getElementById('export-doc-btn');
    const container = document.getElementById('documentation-container');

    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            if (!transcriptionId) {
                Swal.fire('Error', 'Missing transcription ID. Please start a recording first.', 'error');
                return;
            }

            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating Documentation...';

            fetch('{{ route("ai.documentation.generate.from.transcription") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    transcription_id: transcriptionId,
                    appointment_id: appointmentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayDocumentation(data.documentation);
                    Swal.fire({
                        title: 'Generated!',
                        text: 'Clinical documentation has been generated from the transcription.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to generate documentation', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An unexpected error occurred during generation', 'error');
            })
            .finally(() => {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fas fa-magic mr-2"></i> Generate Documentation';
            });
        });
    }

    if (validateBtn) {
        validateBtn.addEventListener('click', function() {
            if (!currentDocId) return;

            validateBtn.disabled = true;
            validateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Validating...';

            fetch(`/ai/documentation/${currentDocId}/validate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    modifications: getModifications()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Approved', 'Documentation has been validated and saved.', 'success');
                    updateStatusUI(true);
                    disableEditing();
                } else {
                    Swal.fire('Error', data.message || 'Failed to validate documentation', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An unexpected error occurred during validation', 'error');
            })
            .finally(() => {
                validateBtn.disabled = false;
                validateBtn.innerHTML = '<i class="fas fa-check-double mr-2"></i> Validate & Approve';
            });
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            if (!currentDocId) return;
            window.location.href = `/ai/documentation/${currentDocId}/export`;
        });
    }

    if (editBtn) {
        editBtn.addEventListener('click', function() {
            const isEditing = editBtn.classList.contains('btn-success');
            if (isEditing) {
                disableEditing();
                editBtn.classList.replace('btn-success', 'btn-warning');
                editBtn.innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Sections';
            } else {
                enableEditing();
                editBtn.classList.replace('btn-warning', 'btn-success');
                editBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Finish Editing';
            }
        });
    }

    // Copy to clipboard functionality
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const textarea = document.getElementById(targetId);
            textarea.select();
            document.execCommand('copy');
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
            setTimeout(() => {
                this.innerHTML = originalHtml;
            }, 2000);
        });
    });

    function displayDocumentation(doc) {
        currentDocId = doc.id;
        container.style.display = 'block';
        
        document.getElementById('chief-complaint').value = doc.chief_complaint || '';
        document.getElementById('history-present-illness').value = doc.history_of_present_illness || '';
        document.getElementById('physical-exam').value = doc.physical_exam_findings || '';
        document.getElementById('assessment').value = doc.assessment || '';
        document.getElementById('plan').value = doc.plan || '';

        if (doc.section_confidences) {
            document.getElementById('cc-confidence').innerText = `Confidence: ${Math.round((doc.section_confidences.chief_complaint || 0) * 100)}%`;
            document.getElementById('hpi-confidence').innerText = `Confidence: ${Math.round((doc.section_confidences.history_of_present_illness || 0) * 100)}%`;
            document.getElementById('pe-confidence').innerText = `Confidence: ${Math.round((doc.section_confidences.physical_exam || 0.8) * 100)}%`;
            document.getElementById('assessment-confidence').innerText = `Confidence: ${Math.round((doc.section_confidences.assessment || 0) * 100)}%`;
            document.getElementById('plan-confidence').innerText = `Confidence: ${Math.round((doc.section_confidences.plan || 0) * 100)}%`;
        }

        document.getElementById('completeness-score-text').innerText = `${Math.round((doc.completeness_score || 0) * 100)}%`;
        updateStatusUI(doc.validated_by_doctor);
        
        // Suggested Codes
        renderCodes(doc.suggested_codes || []);
        
        // Alerts
        renderAlerts(doc.missing_elements || [], doc.compliance_flags || []);

        exportBtn.style.display = 'inline-block';
    }

    function updateStatusUI(isValidated) {
        const badge = document.getElementById('doc-status-badge');
        const statusText = document.getElementById('doc-status-text');
        
        if (isValidated) {
            badge.className = 'badge badge-pill badge-success px-3 py-2';
            badge.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Validated';
            statusText.innerText = 'Validated by Doctor';
            validateBtn.style.display = 'none';
            editBtn.style.display = 'none';
        } else {
            badge.className = 'badge badge-pill badge-warning px-3 py-2';
            badge.innerHTML = '<i class="fas fa-clock mr-1"></i> Pending Approval';
            statusText.innerText = 'Generated - Pending Approval';
            validateBtn.style.display = 'inline-block';
            editBtn.style.display = 'inline-block';
        }
    }

    function renderCodes(codes) {
        const codesList = document.getElementById('codes-list');
        const noCodesMsg = document.getElementById('no-codes-msg');
        codesList.innerHTML = '';
        
        if (codes && codes.length > 0) {
            noCodesMsg.style.display = 'none';
            codesList.style.display = 'block';
            codes.forEach(code => {
                const item = document.createElement('div');
                item.className = 'list-group-item bg-transparent border-0 px-0 py-2 code-item';
                const isValidated = code.is_validated;
                
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <span class="badge badge-secondary mr-2">${code.code_type}</span>
                            <span class="font-weight-bold">${code.code_value}</span>
                            <p class="mb-0 small text-muted">${code.code_description}</p>
                        </div>
                        <div class="text-right ml-2" style="min-width: 80px;">
                            <div class="code-actions ${isValidated ? 'd-none' : ''}">
                                <button class="btn btn-xs btn-outline-success approve-code" data-id="${code.id}" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger reject-code" data-id="${code.id}" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <span class="badge badge-success ${isValidated ? '' : 'd-none'} validated-badge">
                                <i class="fas fa-check mr-1"></i> Approved
                            </span>
                        </div>
                    </div>
                `;
                codesList.appendChild(item);
            });
            
            // Add listeners for code validation
            document.querySelectorAll('.approve-code, .reject-code').forEach(btn => {
                btn.addEventListener('click', function() {
                    const codeId = this.getAttribute('data-id');
                    const isApprove = this.classList.contains('approve-code');
                    validateCode(codeId, isApprove, this);
                });
            });
        } else {
            noCodesMsg.style.display = 'block';
            codesList.style.display = 'none';
        }
    }

    function validateCode(codeId, isApprove, btn) {
        const row = btn.closest('.code-item');
        const actions = row.querySelector('.code-actions');
        const badge = row.querySelector('.validated-badge');
        
        fetch(`/ai/documentation/codes/${codeId}/validate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ is_validated: isApprove })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isApprove) {
                    actions.classList.add('d-none');
                    badge.classList.remove('d-none');
                } else {
                    row.style.opacity = '0.5';
                    actions.innerHTML = '<span class="text-danger small">Rejected</span>';
                }
            }
        });
    }

    function renderAlerts(missing, compliance) {
        const missingList = document.getElementById('missing-elements-list');
        const missingAlert = document.getElementById('missing-elements-alert');
        if (missing && missing.length > 0) {
            missingList.innerHTML = '';
            missing.forEach(el => {
                const li = document.createElement('li');
                li.innerText = el.replace(/_/g, ' ').toUpperCase();
                missingList.appendChild(li);
            });
            missingAlert.style.display = 'block';
        } else {
            missingAlert.style.display = 'none';
        }

        const complianceList = document.getElementById('compliance-flags-list');
        const complianceAlert = document.getElementById('compliance-flags-alert');
        if (compliance && compliance.length > 0) {
            complianceList.innerHTML = '';
            compliance.forEach(flag => {
                const li = document.createElement('li');
                li.innerText = flag;
                complianceList.appendChild(li);
            });
            complianceAlert.style.display = 'block';
        } else {
            complianceAlert.style.display = 'none';
        }
    }

    function enableEditing() {
        const textareas = container.querySelectorAll('textarea');
        textareas.forEach(ta => ta.readOnly = false);
    }

    function disableEditing() {
        const textareas = container.querySelectorAll('textarea');
        textareas.forEach(ta => ta.readOnly = true);
    }

    function getModifications() {
        return {
            chief_complaint: document.getElementById('chief-complaint').value,
            history_of_present_illness: document.getElementById('history-present-illness').value,
            physical_exam_findings: document.getElementById('physical-exam').value,
            assessment: document.getElementById('assessment').value,
            plan: document.getElementById('plan').value
        };
    }

    function checkExistingDocumentation() {
        if (appointmentId) {
            fetch(`/ai/documentation/appointment/${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.documentation) {
                        displayDocumentation(data.documentation);
                    }
                })
                .catch(error => console.error('Error fetching existing documentation:', error));
        }
    }

    checkExistingDocumentation();

    window.triggerAIDocumentationGeneration = function() {
        if (generateBtn) generateBtn.click();
    };
});
</script>
