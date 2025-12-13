@extends('master')

@section('content')
<div class="container-fluid py-4" data-session-id="{{ $sessionId }}">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h1 class="card-title h3 mb-2">Ambient Listening</h1>
                    <p class="card-text">Ambient listening for medical consultation with real-time AI analysis</p>

                    <!-- Privacy Notice -->
                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Privacy & Security Notice</strong>
                                <p class="mb-0 mt-1 small">Ambient listening recordings are processed securely and stored encrypted. All transcriptions are HIPAA-compliant and only accessible to authorized medical personnel. By using this feature, you consent to ambient listening for medical documentation purposes.</p>
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
                                    <span class="text-muted">Not Listening</span>
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
                                    Ambient Listening
                                    <i class="fas fa-info-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="Enhanced ambient listening mode with audio monitoring, pause/resume controls, session persistence, and automatic error recovery. Use Ctrl+H to toggle quickly."></i>
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
                        <!-- React AmbientAudioRecorder component will be mounted here -->
                        <div id="react-audio-recorder-container" class="me-2"></div>

                        <!-- Fallback buttons when React component is not active -->
                        <button id="startRecordingBtn" class="btn btn-success" type="button" disabled style="display: none;">
                            <i class="fas fa-microphone me-2"></i>
                            Start Ambient Listening
                        </button>

                        <button id="stopRecordingBtn" class="btn btn-danger" disabled style="display: none;">
                            <i class="fas fa-stop me-2"></i>
                            Stop Ambient Listening
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
                            Ambient Listening Guide
                        </a>

                        <a href="{{ route('ai.voice-assistant.recorded-voices') }}" class="btn btn-info">
                            <i class="fas fa-history me-2"></i>
                            Session Recordings
                        </a>

                        <a href="{{ route('ai.voice-assistant.performance') }}" class="btn btn-warning">
                            <i class="fas fa-chart-line me-2"></i>
                            Listening Stats
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

        /* Speaker identification styles */
        .speaker-transcription {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.5;
        }

        .speaker-segment {
            transition: all 0.3s ease;
        }

        .speaker-segment:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .speaker-header {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .speaker-label {
            color: #495057;
            font-weight: 600;
        }

        .speaker-doctor .speaker-label {
            color: #007bff;
        }

        .speaker-patient .speaker-label {
            color: #28a745;
        }

        .speaker-text {
            font-size: 0.9rem;
            color: #212529;
            margin-left: 1.5rem;
        }

        /* Enhanced transcription status */
        #transcriptionStatus .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        /* Language auto-detection indicator */
        .language-auto-detected {
            animation: languagePulse 2s ease-in-out;
        }

        @keyframes languagePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); background-color: #17a2b8 !important; }
        }
    </style>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Left Column: Transcription -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Ambient Transcription
                    </h5>
                    <div id="transcriptionStatus" class="d-flex align-items-center gap-2">
                        <!-- Status indicators will be inserted here -->
                    </div>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                        <div id="transcriptionContainer" style="height: 100%;">
                            <!-- React RealTimeTranscript component will be mounted here -->
                            <div id="react-transcript-container"></div>

                            <!-- Fallback textarea for non-React implementation -->
                            <textarea id="transcriptionArea" class="form-control" style="height: 100%; border: none; background: transparent; resize: none; display: none;" placeholder="Start ambient listening to see transcription here..."></textarea>
                        </div>
                    </div>
                    <!-- Speaker Legend -->
                    <div id="speakerLegend" class="mt-2 d-none">
                        <small class="text-muted">
                            <i class="fas fa-users me-1"></i>
                            <span id="speakerLegendText"></span>
                        </small>
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
                            placeholder="Write your professional diagnosis based on the ambient listening session and your clinical judgment..."
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
                                <i class="fas fa-check me-1"></i>Complete Session
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
                        <i class="fas fa-check me-2"></i>Complete Session
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
                        <strong>Complete this ambient listening session:</strong>
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
                        <i class="fas fa-check me-1"></i>Complete Session
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Make PHP variables available to JavaScript -->
<script>
    window.records = @json($records ?? []);
    window.patientAppointments = @json($patientAppointments ?? []);
</script>

<!-- Include the ambient listening JavaScript -->
<script src="{{ asset('js/voice-assistant.js') }}"></script>

<!-- Include React components for ambient listening -->
@viteReactRefresh
@vite(['resources/js/voice-assistant-main.jsx'])

<!-- Form components are now initialized by the main ambient listening script -->
<!-- This ensures proper timing and prevents conflicts -->
@endsection
