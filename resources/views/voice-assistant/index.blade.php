@extends('master')

@section('content')
<div class="container-fluid py-4" data-session-id="{{ $sessionId }}">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h1 class="card-title h3 mb-2">Voice Assistant</h1>
                    <p class="card-text">Hands-free medical consultation with real-time AI analysis</p>

                    <!-- Privacy Notice -->
                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Privacy & Security Notice</strong>
                                <p class="mb-0 mt-1 small">Voice recordings are processed securely and stored encrypted. All transcriptions are HIPAA-compliant and only accessible to authorized medical personnel. By using this feature, you consent to voice recording for medical documentation purposes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Selection -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold mb-0">Select Patient</label>
                        <button id="showNewPatientFormBtn" class="btn btn-outline-primary btn-sm" type="button">
                            <i class="fas fa-user-plus me-1"></i>
                            Add New Patient
                        </button>
                    </div>
                    <select id="patientSelect" class="form-select">
                        <option value="">Select a patient...</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient['id'] }}">{{ $patient['name'] }} ({{ $patient['age'] ? $patient['age'] . 'y' : 'Age N/A' }}, {{ $patient['gender'] ? ucfirst($patient['gender']) : 'Gender N/A' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- New Patient Form Modal -->
    <div id="newPatientForm" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            Create New Patient
                        </h5>
                        <button id="hideNewPatientFormBtn" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" id="newPatientName" class="form-control" placeholder="Enter patient's full name">
                            <div id="newPatientNameError" class="text-danger small d-none"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" id="newPatientEmail" class="form-control" placeholder="patient@example.com">
                            <div id="newPatientEmailError" class="text-danger small d-none"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Age *</label>
                            <input type="number" id="newPatientAge" class="form-control" min="1" max="150" placeholder="Age">
                            <div id="newPatientAgeError" class="text-danger small d-none"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender *</label>
                            <select id="newPatientGender" class="form-select">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <div id="newPatientGenderError" class="text-danger small d-none"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="newPatientPhone" class="form-control" placeholder="Phone number">
                            <div id="newPatientPhoneError" class="text-danger small d-none"></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> A default password "patient123" will be assigned. Please inform the patient to change it after first login.
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button id="createNewPatientBtn" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>
                            Create Patient
                        </button>
                        <button id="cancelNewPatientBtn" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Alert Container -->
    <div id="alertContainer" class="mb-3"></div>

    <!-- Control Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <!-- Recording Status -->
                            <div class="d-flex align-items-center me-4">
                                <div id="recordingStatus">
                                    <span class="badge bg-secondary me-2">
                                        <i class="fas fa-circle fa-xs"></i>
                                    </span>
                                    <span class="text-muted">Not Recording</span>
                                </div>
                            </div>

                            <!-- Processing Status -->
                            <div id="processingStatus" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span class="text-primary">Processing...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Language Selector and Hands-Free Toggle -->
                        <div class="d-flex align-items-center gap-3">
                            <!-- Language Selector -->
                            <div class="d-flex align-items-center">
                                <label class="form-label me-2 mb-0 small fw-bold">Language:</label>
                                <select id="languageSelector" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                                    <option value="ar">🇸🇦 العربية</option>
                                    <option value="en">🇺🇸 English</option>
                                    <option value="fr">🇫🇷 Français</option>
                                    <option value="es">🇪🇸 Español</option>
                                    <option value="de">🇩🇪 Deutsch</option>
                                </select>
                            </div>

                            <!-- Hands-Free Toggle -->
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="handsFreeToggle">
                                <label class="form-check-label" for="handsFreeToggle">
                                    <i class="fas fa-robot me-1"></i>
                                    Hands-Free Mode
                                    <i class="fas fa-info-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Enhanced hands-free mode with audio monitoring, pause/resume controls, session persistence, and automatic error recovery. Use Ctrl+H to toggle quickly."></i>
                                </label>
                            </div>

                            <!-- Enhanced Status Indicators -->
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">Status:</small>
                                <div id="enhancedStatusContainer" class="d-flex align-items-center gap-2">
                                    <!-- Dynamic status indicators will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Control Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button id="startRecordingBtn" class="btn btn-success" type="button">
                            <i class="fas fa-microphone me-2"></i>
                            Start Recording
                        </button>

                        <button id="stopRecordingBtn" class="btn btn-danger" disabled>
                            <i class="fas fa-stop me-2"></i>
                            Stop Recording
                        </button>

                        <button id="generateAnalysisBtn" class="btn btn-primary" disabled>
                            <i class="fas fa-brain me-2"></i>
                            Generate AI Analysis
                        </button>

                        <button id="resetSessionBtn" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>
                            Reset
                        </button>

                        <a href="{{ route('ai.voice-assistant.training') }}" class="btn btn-success">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Training Guide
                        </a>

                        <a href="{{ route('ai.voice-assistant.recorded-voices') }}" class="btn btn-info">
                            <i class="fas fa-history me-2"></i>
                            Recorded Voices
                        </a>

                        <a href="{{ route('ai.voice-assistant.performance') }}" class="btn btn-warning">
                            <i class="fas fa-chart-line me-2"></i>
                            Performance
                        </a>
                    </div>

                    <!-- JavaScript-based Progress Indicator (shows immediately) -->
                    <div id="jsProgressIndicator" class="mt-3" style="display: none; animation: fadeIn 0.5s ease-in;">
                        <div class="card border-primary shadow-sm" style="background: linear-gradient(135deg, #f8f9ff, #ffffff); border-width: 2px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border text-primary me-3" role="status" style="animation: spin 1s linear infinite, pulse 2s ease-in-out infinite;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-primary fw-bold">
                                            <i class="fas fa-brain me-2" style="animation: pulse 2s ease-in-out infinite;"></i>
                                            AI Analysis in Progress
                                        </h6>
                                        <p class="mb-0 text-muted small" id="jsProcessingStage">
                                            Initializing AI analysis...
                                        </p>
                                    </div>
                                </div>

                                <!-- Animated Progress Bar -->
                                <div class="progress mt-3" style="height: 8px; border-radius: 10px; background-color: #e9ecef;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar"
                                         style="width: 100%; background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 10px;">
                                    </div>
                                </div>

                                <!-- Processing Time Indicator -->
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Estimated time: 10-30 seconds
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-robot me-1"></i>
                                        AI Engine
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS for Progress Animations -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced progress bar animation */
        .progress-bar-animated {
            background-size: 40px 40px !important;
            animation: progress-bar-stripes 1s linear infinite, shimmer 2s ease-in-out infinite alternate !important;
        }

        @keyframes shimmer {
            0% { background-position: -40px 0; }
            100% { background-position: 40px 0; }
        }

        /* Smooth transitions for processing stages */
        .processing-stage-text {
            transition: all 0.3s ease-in-out;
        }

        /* Enhanced hands-free mode styles */
        .hands-free-active {
            animation: pulse 2s ease-in-out infinite;
        }

        .audio-level-container {
            position: relative;
            overflow: hidden;
        }

        .audio-level-bar {
            transition: width 0.1s ease-out, background-color 0.3s ease;
        }

        .recording-timer {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        /* Status indicators animations */
        @keyframes statusPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .status-active {
            animation: statusPulse 1.5s ease-in-out infinite;
        }

        /* Enhanced button states */
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-hands-free-active {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
        }

        /* Language indicator animations */
        .language-changed {
            animation: languageChangePulse 2s ease-in-out;
            transform: scale(1.05);
        }

        @keyframes languageChangePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); background-color: #17a2b8 !important; }
        }

        /* Auto language indicator styling */
        #autoLanguageIndicator {
            transition: all 0.3s ease-in-out;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
        }

        /* Keyboard shortcuts help */
        .keyboard-shortcuts-help {
            backdrop-filter: blur(5px);
            background: rgba(0, 0, 0, 0.8) !important;
        }
    </style>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Left Column: Transcription -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Live Transcription
                    </h5>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                        <textarea id="transcriptionArea" class="form-control" style="height: 100%; border: none; background: transparent; resize: none;" placeholder="Start recording to see transcription here..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Chart Fields -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Auto-Generated Chart
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Symptoms</label>
                            <textarea id="symptoms" class="form-control" rows="2" placeholder="Symptoms will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Medical History</label>
                            <textarea id="medicalHistory" class="form-control" rows="2" placeholder="Medical history will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Physical Findings</label>
                            <textarea id="physicalFindings" class="form-control" rows="2" placeholder="Physical findings will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Medications</label>
                            <textarea id="medications" class="form-control" rows="2" placeholder="Medications will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vital Signs</label>
                            <textarea id="vitalSigns" class="form-control" rows="2" placeholder="Vital signs will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diagnosis</label>
                            <textarea id="diagnosis" class="form-control" rows="2" placeholder="Diagnosis suggestions will appear here..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Care Plan</label>
                            <textarea id="carePlan" class="form-control" rows="2" placeholder="Care plan will be generated automatically..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Recording Manual Diagnosis Form -->
    <div id="postRecordingDiagnosisForm" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-stethoscope me-2"></i>
                        Manual Diagnosis After Recording
                    </h5>
                    <small>Enter your diagnosis based on the recorded session</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="postRecordingDiagnosisText" class="form-label">
                            <strong>Your Diagnosis:</strong>
                        </label>
                        <textarea
                            id="postRecordingDiagnosisText"
                            class="form-control"
                            rows="4"
                            placeholder="Enter your diagnosis based on the recording..."
                        ></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Save your diagnosis and optionally complete an appointment</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button id="cancelPostRecordingDiagnosisBtn" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button id="saveAndCompletePostRecordingBtn" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Save & Complete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Analysis Section -->
    <div id="aiAnalysisSection" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-robot me-2"></i>
                            AI Clinical Analysis
                        </h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3" style="max-height: 500px; overflow-y: auto; background-color: #f8f9fa;">
                        <div id="aiAnalysisArea" style="white-space: pre-wrap;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnosis Entry Form -->
    <div id="diagnosisEntryForm" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        Write Your Professional Diagnosis
                    </h5>
                    <small>Complete your diagnosis to finish the consultation</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="diagnosisText" class="form-label">
                            <strong>Your Professional Diagnosis:</strong>
                        </label>
                        <textarea
                            id="diagnosisText"
                            class="form-control"
                            rows="6"
                            placeholder="Write your professional diagnosis based on the consultation and your clinical judgment..."
                            required
                        ></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            This diagnosis will be saved to the patient's record. You can link it to an appointment or save it independently.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="d-flex gap-2">
                            <button id="cancelDiagnosisBtn" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button id="completeConsultationBtn" class="btn btn-success" disabled>
                                <i class="fas fa-check me-1"></i>Complete Consultation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Consultation Modal -->
    <div class="modal fade" id="completeConsultationModal" tabindex="-1" aria-labelledby="completeConsultationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="completeConsultationModalLabel">
                        <i class="fas fa-check me-2"></i>Complete Consultation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Diagnosis Preview -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary">
                            <i class="fas fa-file-medical me-2"></i>Diagnosis Preview
                        </h6>
                        <div id="diagnosisPreview" class="border rounded p-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                            <!-- Diagnosis text will be inserted here -->
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Complete this voice consultation:</strong>
                        <p class="mb-0 mt-2">Link this diagnosis to a scheduled appointment and mark it as completed, or save it independently if no appointment is available.</p>
                    </div>

                    <!-- Patient Info Display -->
                    <div class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <small class="text-muted">Patient:</small>
                                <span id="modalPatientName" class="fw-bold"></span>
                                <small class="text-muted ms-2">(Selected from main form)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Selection -->
                    <div class="mb-4">
                        <div id="appointmentInfo" class="alert alert-info" style="display: none;">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="appointmentInfoText"></span>
                        </div>
                    </div>


                    <!-- Doctor Notes (shown when complete appointment is selected) -->
                    <div id="doctorNotesSection" class="mb-3" style="display: none;">
                        <label for="appointmentDoctorNotes" class="form-label fw-bold">
                            Doctor Notes for Appointment:
                        </label>
                        <textarea
                            id="appointmentDoctorNotes"
                            class="form-control"
                            rows="3"
                            placeholder="Add notes about treatment plan, follow-up instructions, etc..."
                        ></textarea>
                        <div class="form-text">
                            These notes will be added to the appointment record.
                        </div>
                    </div>

                    <!-- Appointment Preview -->
                    <div id="appointmentPreview" class="card bg-light" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-calendar-alt me-2"></i>Appointment Details
                            </h6>
                            <div id="appointmentDetails"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" id="modalCompleteConsultationBtn" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Complete Consultation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Make PHP variables available to JavaScript -->
<script>
    window.records = @json($records ?? []);
    window.patientAppointments = @json($patientAppointments ?? []);
</script>

<!-- Include the voice assistant JavaScript -->
<script src="{{ asset('js/voice-assistant.js') }}"></script>

<!-- Additional JavaScript for new patient form and diagnosis form -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // New Patient Form Handling
    const showNewPatientFormBtn = document.getElementById('showNewPatientFormBtn');
    const hideNewPatientFormBtn = document.getElementById('hideNewPatientFormBtn');
    const newPatientForm = document.getElementById('newPatientForm');
    const cancelNewPatientBtn = document.getElementById('cancelNewPatientBtn');
    const createNewPatientBtn = document.getElementById('createNewPatientBtn');

    if (showNewPatientFormBtn) {
        showNewPatientFormBtn.addEventListener('click', function() {
            newPatientForm.style.display = 'block';
            clearNewPatientForm();
        });
    }

    if (hideNewPatientFormBtn || cancelNewPatientBtn) {
        const hideForm = function() {
            newPatientForm.style.display = 'none';
            clearNewPatientForm();
        };

        if (hideNewPatientFormBtn) hideNewPatientFormBtn.addEventListener('click', hideForm);
        if (cancelNewPatientBtn) cancelNewPatientBtn.addEventListener('click', hideForm);
    }

    if (createNewPatientBtn) {
        createNewPatientBtn.addEventListener('click', function() {
            createNewPatient();
        });
    }

    // Show diagnosis entry form after recording stops
    window.showDiagnosisEntryForm = function() {
        const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
        if (diagnosisEntryForm) {
            diagnosisEntryForm.style.display = 'block';
            const diagnosisText = document.getElementById('diagnosisText');
            if (diagnosisText) {
                diagnosisText.focus();
            }
        }
    };

    // Diagnosis Entry Form Handling
    const cancelDiagnosisBtn = document.getElementById('cancelDiagnosisBtn');
    const completeConsultationBtn = document.getElementById('completeConsultationBtn');
    const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
    const diagnosisText = document.getElementById('diagnosisText');

    if (cancelDiagnosisBtn) {
        cancelDiagnosisBtn.addEventListener('click', function() {
            diagnosisEntryForm.style.display = 'none';
            diagnosisText.value = '';
            completeConsultationBtn.disabled = true;
        });
    }

    if (diagnosisText) {
        diagnosisText.addEventListener('input', function() {
            completeConsultationBtn.disabled = !this.value.trim();
        });
    }

    if (completeConsultationBtn) {
        completeConsultationBtn.addEventListener('click', function() {
            showCompleteConsultationModal();
        });
    }

    // Modal complete consultation button handler
    const modalCompleteConsultationBtn = document.getElementById('modalCompleteConsultationBtn');
    if (modalCompleteConsultationBtn) {
        modalCompleteConsultationBtn.addEventListener('click', function() {
            completeConsultation();
        });
    }

    // Clear new patient form
    function clearNewPatientForm() {
        document.getElementById('newPatientName').value = '';
        document.getElementById('newPatientEmail').value = '';
        document.getElementById('newPatientAge').value = '';
        document.getElementById('newPatientGender').value = '';
        document.getElementById('newPatientPhone').value = '';

        // Clear errors
        document.getElementById('newPatientNameError').classList.add('d-none');
        document.getElementById('newPatientEmailError').classList.add('d-none');
        document.getElementById('newPatientAgeError').classList.add('d-none');
        document.getElementById('newPatientGenderError').classList.add('d-none');
        document.getElementById('newPatientPhoneError').classList.add('d-none');
    }

    // Create new patient
    function createNewPatient() {
        const name = document.getElementById('newPatientName').value.trim();
        const email = document.getElementById('newPatientEmail').value.trim();
        const age = document.getElementById('newPatientAge').value;
        const gender = document.getElementById('newPatientGender').value;
        const phone = document.getElementById('newPatientPhone').value.trim();

        // Clear previous errors
        document.getElementById('newPatientNameError').classList.add('d-none');
        document.getElementById('newPatientEmailError').classList.add('d-none');
        document.getElementById('newPatientAgeError').classList.add('d-none');
        document.getElementById('newPatientGenderError').classList.add('d-none');

        // Validation
        let hasError = false;

        if (!name) {
            document.getElementById('newPatientNameError').textContent = 'Name is required';
            document.getElementById('newPatientNameError').classList.remove('d-none');
            hasError = true;
        }

        if (!email) {
            document.getElementById('newPatientEmailError').textContent = 'Email is required';
            document.getElementById('newPatientEmailError').classList.remove('d-none');
            hasError = true;
        } else if (!isValidEmail(email)) {
            document.getElementById('newPatientEmailError').textContent = 'Please enter a valid email';
            document.getElementById('newPatientEmailError').classList.remove('d-none');
            hasError = true;
        }

        if (!age || age < 1 || age > 150) {
            document.getElementById('newPatientAgeError').textContent = 'Please enter a valid age (1-150)';
            document.getElementById('newPatientAgeError').classList.remove('d-none');
            hasError = true;
        }

        if (!gender) {
            document.getElementById('newPatientGenderError').textContent = 'Please select gender';
            document.getElementById('newPatientGenderError').classList.remove('d-none');
            hasError = true;
        }

        if (hasError) return;

        // AJAX call to create new patient
        $.ajax({
            url: '/ai/voice-assistant/create-new-patient',
            method: 'POST',
            data: {
                newPatientName: name,
                newPatientEmail: email,
                newPatientAge: age,
                newPatientGender: gender,
                newPatientPhone: phone,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Add patient to select dropdown
                    const patientSelect = document.getElementById('patientSelect');
                    const option = document.createElement('option');
                    option.value = response.patient.id;
                    option.textContent = response.patient.name + ' (' + (response.patient.age ? response.patient.age + 'y' : 'Age N/A') + ', ' + (response.patient.gender ? response.patient.gender.charAt(0).toUpperCase() + response.patient.gender.slice(1) : 'Gender N/A') + ')';
                    patientSelect.appendChild(option);

                    // Select the new patient
                    patientSelect.value = response.patient.id;

                    // Trigger change event to update the voice assistant's selectedPatient variable
                    const changeEvent = new Event('change', { bubbles: true });
                    patientSelect.dispatchEvent(changeEvent);

                    // Also directly update the voice assistant if available
                    if (window.voiceAssistant && window.voiceAssistant.setSelectedPatient) {
                        window.voiceAssistant.setSelectedPatient(response.patient.id);
                    }

                    // Hide form
                    newPatientForm.style.display = 'none';
                    clearNewPatientForm();

                    // Show success message
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message || 'Failed to create patient.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Create patient error:', error);
                showNotification('Failed to create patient. Please try again.', 'error');
            }
        });
    }


    // Show notification
    function showNotification(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const alertClass = type === 'error' ? 'alert-danger' :
                          type === 'success' ? 'alert-success' :
                          type === 'warning' ? 'alert-warning' : 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        alertContainer.innerHTML = alertHtml;

        // Scroll to alert for errors and warnings
        if (type === 'error' || type === 'warning') {
            alertContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }

    // Validate email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Complete Consultation Modal Functions
    function showCompleteConsultationModal() {
        const diagnosisText = document.getElementById('diagnosisText').value.trim();
        if (!diagnosisText) {
            showNotification('Please enter your diagnosis first.', 'error');
            return;
        }

        const selectedPatient = document.getElementById('patientSelect').value;
        if (!selectedPatient) {
            showNotification('Please select a patient first.', 'error');
            return;
        }

        // Update diagnosis preview
        document.getElementById('diagnosisPreview').textContent = diagnosisText;

        // Update patient name display in modal
        const patientSelect = document.getElementById('patientSelect');
        const selectedOption = patientSelect.options[patientSelect.selectedIndex];
        const patientName = selectedOption ? selectedOption.text.split(' (')[0] : 'Unknown Patient';
        document.getElementById('modalPatientName').textContent = patientName;

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('completeConsultationModal'));
        modal.show();

        // Load appointments for the selected patient
        loadPatientAppointments(selectedPatient);

        // Set up completion type change handler
        setupCompletionTypeHandler();
    }

    function setupCompletionTypeHandler() {
        // No longer needed since we removed the radio buttons
        // Appointment selection is now always visible
        updateCompleteButtonState();
    }

    function loadPatientAppointments(patientId) {
        const appointmentInfoDiv = document.getElementById('appointmentInfo');
        const appointmentInfoText = document.getElementById('appointmentInfoText');

        // Debug logging
        console.log('Loading appointments for patient:', patientId);
        console.log('Available patient appointments:', window.patientAppointments);

        // Use the pre-loaded appointments data instead of AJAX call
        const appointments = window.patientAppointments[patientId] || [];

        console.log('Found appointments for patient:', appointments);

        if (appointments.length > 0) {
            // Get the first available appointment
            const appointment = appointments[0];
            appointmentInfoDiv.style.display = 'block';
            appointmentInfoText.textContent = `Found ${appointments.length} incomplete appointment(s). The first one will be automatically selected: ${appointment.appointment_date_formatted} (${appointment.appointment_type})`;
            
            // Store appointment ID for completion
            window.selectedAppointmentId = appointment.id;
            
            // Show appointment details
            showAppointmentPreview(appointment.id);
        } else {
            appointmentInfoDiv.style.display = 'block';
            appointmentInfoText.textContent = 'No scheduled appointments available for this patient. Diagnosis will be saved without appointment completion.';
            window.selectedAppointmentId = null;
            showAppointmentPreview(null);
            console.log('No appointments found for patient:', patientId);
        }
    }

    function showAppointmentPreview(appointmentId) {
        const previewDiv = document.getElementById('appointmentPreview');
        const detailsDiv = document.getElementById('appointmentDetails');

        if (!appointmentId) {
            previewDiv.style.display = 'none';
            return;
        }

        // Find appointment details
        const appointments = window.patientAppointments[document.getElementById('patientSelect').value] || [];
        const appointment = appointments.find(apt => apt.id == appointmentId);
        
        if (appointment) {
            detailsDiv.innerHTML = `
                <p><strong>Appointment:</strong> ${appointment.appointment_date_formatted}</p>
                <p><strong>Type:</strong> ${appointment.appointment_type}</p>
                <p><strong>Status:</strong> Will be marked as completed</p>
                <p><strong>Diagnosis:</strong> Will be linked to current diagnosis</p>
            `;
        } else {
            detailsDiv.innerHTML = '<p>Appointment details not found.</p>';
        }

        previewDiv.style.display = 'block';
    }

    function updateCompleteButtonState() {
        const completeBtn = document.getElementById('modalCompleteConsultationBtn');
        const hasAppointment = window.selectedAppointmentId !== null;

        // Button is always enabled, but text changes based on appointment availability
        completeBtn.disabled = false;
        completeBtn.innerHTML = hasAppointment ?
            '<i class="fas fa-check me-1"></i>Complete Appointment' :
            '<i class="fas fa-save me-1"></i>Save Diagnosis';
    }


    function completeConsultation() {
        const diagnosisText = document.getElementById('diagnosisText').value.trim();
        const appointmentId = window.selectedAppointmentId;
        const doctorNotes = document.getElementById('appointmentDoctorNotes').value.trim();
        const completionType = appointmentId ? 'complete_appointment' : 'save_only';

        if (!diagnosisText) {
            showNotification('Please enter your diagnosis text.', 'error');
            return;
        }

        const selectedPatient = document.getElementById('patientSelect').value;
        if (!selectedPatient) {
            showNotification('Please select a patient first.', 'error');
            return;
        }

        // Get session data
        const container = document.querySelector('[data-session-id]');
        const sessionId = container ? container.getAttribute('data-session-id') : '';
        const transcription = document.getElementById('transcriptionArea').value;

        // Get AI data if available
        const aiResultId = window.voiceAssistant ? window.voiceAssistant.getAiResultId() : null;
        const extractedData = window.voiceAssistant ? window.voiceAssistant.getExtractedData() : {};

        // Disable button and show loading
        const completeBtn = document.getElementById('modalCompleteConsultationBtn');
        const originalText = completeBtn.innerHTML;
        completeBtn.disabled = true;
        completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Completing...';

        // Prepare data for AJAX call
        const ajaxData = {
            diagnosisText: diagnosisText,
            sessionId: sessionId,
            selectedPatient: selectedPatient,
            transcription: transcription,
            completionType: completionType,
            appointmentId: appointmentId || null,
            doctorNotes: doctorNotes,
            aiResultId: aiResultId,
            extractedData: extractedData,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        // AJAX call to complete consultation
        $.ajax({
            url: '/ai/voice-assistant/complete-consultation',
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('completeConsultationModal'));
                    modal.hide();

                    // Hide the diagnosis form
                    document.getElementById('diagnosisEntryForm').style.display = 'none';
                    document.getElementById('diagnosisText').value = '';

                    // Show appropriate success message
                    const message = completionType === 'complete_appointment' ?
                        'Diagnosis saved and appointment completed successfully!' :
                        'Diagnosis saved successfully!';
                    showNotification(message, 'success');

                    // Redirect to diagnosis view
                    if (response.redirectUrl) {
                        setTimeout(function() {
                            window.location.href = response.redirectUrl;
                        }, 2000);
                    }
                } else {
                    showNotification(response.message || 'Failed to complete consultation.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Complete consultation error:', error);
                showNotification('Failed to complete consultation. Please try again.', 'error');
            },
            complete: function() {
                // Re-enable button
                completeBtn.disabled = false;
                completeBtn.innerHTML = originalText;
            }
        });
    }

    // Debug function to check patient selection
    window.debugPatientSelection = function() {
        const patientSelect = document.getElementById('patientSelect');
        console.log('=== PATIENT SELECTION DEBUG ===');
        console.log('Patient select element:', patientSelect);
        console.log('Patient select value:', patientSelect ? patientSelect.value : 'null');
        console.log('Voice assistant available:', !!window.voiceAssistant);
        if (window.voiceAssistant) {
            console.log('Voice assistant selected patient:', window.voiceAssistant.getSelectedPatient());
        }
        console.log('All patients in dropdown:');
        if (patientSelect) {
            for (let i = 0; i < patientSelect.options.length; i++) {
                console.log(`  ${i}: value="${patientSelect.options[i].value}" text="${patientSelect.options[i].text}"`);
            }
        }
        console.log('=== END DEBUG ===');

        // Try to sync
        if (window.voiceAssistant && window.voiceAssistant.syncPatientSelection) {
            console.log('Attempting to sync patient selection...');
            window.voiceAssistant.syncPatientSelection();
            console.log('After sync - selected patient:', window.voiceAssistant.getSelectedPatient());
        }
    };
});
</script>
@endsection
