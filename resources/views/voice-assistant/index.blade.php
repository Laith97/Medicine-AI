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

                    <!-- Keyboard Shortcuts -->
                    <div class="alert alert-info mt-3 mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-keyboard me-2 mt-1"></i>
                            <div>
                                <strong>Keyboard Shortcuts</strong>
                                <ul class="mb-0 small">
                                    <li><kbd>Ctrl + Enter</kbd> - Start/Stop Ambient Listening</li>
                                    <li><kbd>Ctrl + H</kbd> - Toggle Hands-Free Mode (if available)</li>
                                    <li><kbd>Alt + T</kbd> - Focus on Transcript Area</li>
                                </ul>
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
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <!-- Recording Status -->
                            <div class="d-flex align-items-center me-4">
                                <div class="d-flex align-items-center" id="recordingStatusContainer">
                                    <div class="status-indicator me-2">
                                        <span class="status-dot" id="statusDot"></span>
                                    </div>
                                    <span class="text-muted" id="recordingStatusText">Ready to Listen</span>
                                </div>
                            </div>

                            <!-- Processing Status -->
                            <div id="processingStatus" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="text-primary">Processing...</span>
                            </div>
                        </div>

                        <!-- Language Selector and Controls -->
                        <div class="d-flex align-items-center gap-4">
                            <!-- Language Selector -->
                            <div class="d-flex align-items-center">
                                <label class="form-label me-2 mb-0 small fw-bold">Language:</label>
                                <select id="languageSelector" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                                    <option value="auto" selected>✨ Auto Detect</option>
                                    <option value="ar">🇸🇦 العربية</option>
                                    <option value="en">🇺🇸 English</option>
                                    <option value="fr">🇫🇷 Français</option>
                                    <option value="es">🇪🇸 Español</option>
                                    <option value="de">🇩🇪 Deutsch</option>
                                </select>
                            </div>

                            <!-- Enhanced Status Indicators -->
                            <div class="d-flex align-items-center">
                                <small class="text-muted me-2">Status:</small>
                                <div id="enhancedStatusContainer" class="d-flex align-items-center gap-2">
                                    <!-- Dynamic status indicators will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Audio Recorder - Separate Row -->
                    <div class="mb-3">
                        <div id="react-audio-recorder-container" style="max-width: 300px;"></div>
                    </div>

                    <!-- Control Buttons -->
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button id="startRecordingBtn" class="btn btn-success d-none" type="button" disabled style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-microphone me-1"></i>Start
                        </button>

                        <button id="stopRecordingBtn" class="btn btn-danger d-none" disabled style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-stop me-1"></i>Stop
                        </button>

                        <button id="generateAnalysisBtn" class="btn" disabled style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; background: #DE6262; color: white; border: 1px solid #DE6262;">
                            <i class="fas fa-brain me-1"></i>AI Analysis
                        </button>

                        <button id="generateClinicalDocBtn" class="btn" disabled style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; background: #2c3e50; color: white; border: 1px solid #2c3e50;">
                            <i class="fas fa-file-medical me-1"></i>Clinical Doc
                        </button>

                        <button id="resetSessionBtn" class="btn btn-secondary" style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-redo me-1"></i>Reset
                        </button>

                        <div class="vr align-self-stretch mx-1" style="opacity: 0.2;"></div>

                        <a href="{{ route('ai.voice-assistant.training') }}" class="btn btn-outline-secondary" style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-graduation-cap me-1"></i>Guide
                        </a>

                        <a href="{{ route('ai.voice-assistant.recorded-voices') }}" class="btn btn-outline-secondary" style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-history me-1"></i>History
                        </a>

                        <a href="{{ route('ai.voice-assistant.performance') }}" class="btn btn-outline-secondary" style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-chart-line me-1"></i>Stats
                        </a>

                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#ambientListeningHelpModal" style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; background: #f8f9fa; color: #333; border: 1px solid #dee2e6;">
                            <i class="fas fa-question-circle me-1"></i>Help
                        </button>
                    </div>

                    <!-- Advanced Controls Custom Implementation -->
                    <div class="mt-4">
                        <button id="advancedControlsToggleBtn" class="btn btn-outline-primary btn-sm w-100" type="button">
                            <i class="fas fa-cog me-1"></i> Advanced Controls
                        </button>
                        <div id="voiceAssistantAdvancedControls" class="card card-body bg-light p-3 mt-3" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Audio Quality</label>
                                    <select class="form-select" id="audioQuality">
                                        <option value="high">High Quality (16kHz)</option>
                                        <option value="medium">Medium Quality (8kHz)</option>
                                        <option value="low">Low Quality (4kHz)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sensitivity</label>
                                    <select class="form-select" id="sensitivity">
                                        <option value="high">High (Sensitive)</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="low">Low (Less Sensitive)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- JavaScript-based Progress Indicator (shows immediately) -->
                    <div id="jsProgressIndicator" class="mt-4 p-3 rounded bg-gradient-primary" style="display: none;">
                        <div class="card border-primary shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border text-primary me-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-primary fw-bold">
                                            <i class="fas fa-brain me-2"></i>
                                            Processing Transcript with AI...
                                        </h6>
                                        <p class="mb-0 text-muted small" id="jsProcessingStage">
                                            Initializing AI analysis...
                                        </p>
                                    </div>
                                </div>

                                <!-- Animated Progress Bar -->
                                <div class="progress mt-3" style="height: 12px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                         role="progressbar"
                                         style="width: 100%;">
                                    </div>
                                </div>

                                <!-- Processing Time Indicator -->
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Estimated: 10-30 seconds
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-robot me-1"></i>
                                        AI Processing
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS for Enhanced UI -->
    <style>
        /* Status Indicator Styles */
        .status-indicator {
            display: inline-block;
            position: relative;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            background-color: #6c757d;
            transition: all 0.3s ease;
        }

        .status-dot.active {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }

        .status-dot.connecting {
            background-color: #ffc107;
            animation: pulse 1s infinite;
        }

        .status-dot.recording {
            background-color: #dc3545;
            animation: pulse 0.5s infinite;
        }

        .status-dot.error {
            background-color: #dc3545;
            animation: shake 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }

        /* Enhanced button styles */
        .btn-lg {
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            min-height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .btn-lg:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-lg:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-lg:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Recording button animation */
        .recording-pulse {
            animation: recordingPulse 1.5s infinite ease-in-out;
        }

        @keyframes recordingPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
                background-color: #dc3545;
            }
            50% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
                background-color: #c82333;
            }
        }

        /* Transcription container */
        .transcription-container {
            position: relative;
        }

        /* Transcript message styling */
        .message-segment {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .message-segment.patient {
            border-left-color: #28a745;
        }

        .message-segment.doctor {
            border-left-color: #0d6efd;
        }

        .message-segment.unknown {
            border-left-color: #6c757d;
        }

        .message-content {
            border-radius: 8px;
            padding: 12px;
            margin-top: 5px;
        }

        /* Progress bar styling */
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        /* Card styling */
        .card {
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
        }

        /* Custom scrollbar for transcription */
        .transcription-container::-webkit-scrollbar {
            width: 8px;
        }

        .transcription-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .transcription-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .transcription-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Enhanced recording button */
        .ambient-recorder-container,
        #react-audio-recorder-container {
            max-width: 300px;
        }
        
        .ambient-recorder-container .btn,
        #react-audio-recorder-container .btn {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            min-height: 38px;
            max-height: 42px;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .ambient-recorder-container .btn:active:not(:disabled),
        #react-audio-recorder-container .btn:active:not(:disabled) {
            transform: scale(0.98);
        }

        .ambient-recorder-container .btn:hover:not(:disabled),
        #react-audio-recorder-container .btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* Constrain recorder component size */
        #react-audio-recorder-container > * {
            max-width: 100%;
        }

        /* Transcript container scrollbar */
        #react-transcript-container {
            height: 500px;
            overflow-y: auto;
            border-radius: 8px;
            scroll-behavior: smooth;
        }

        /* Recording dot animation */
        .recording-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #dc3545;
            display: inline-block;
            animation: recordingPulseDot 1.5s infinite;
        }

        @keyframes recordingPulseDot {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Status text */
        .status-text {
            font-weight: 500;
        }

        /* Loading and status animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner-border {
            animation: spin 1s linear infinite;
        }

        /* Speaker identification styles */
        .speaker-transcription {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.5;
        }

        .speaker-segment {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px;
        }

        .speaker-segment:hover {
            background-color: #f8f9fa;
        }

        .speaker-header {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .speaker-label {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .speaker-doctor .speaker-label {
            background-color: #d4edda;
            color: #155724;
        }

        .speaker-patient .speaker-label {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .speaker-text {
            font-size: 0.9rem;
            color: #212529;
        }

        /* Enhanced transcript status */
        #transcriptionStatus .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .card-body {
                padding: 1rem;
            }

            .transcription-container {
                height: 300px;
            }
        }
    </style>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4 p-2 bg-light rounded-3" id="voiceAssistantTabs" role="tablist" style="box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-3 fw-bold" id="transcription-tab" data-bs-toggle="tab" data-bs-target="#transcription-pane" type="button" role="tab" aria-controls="transcription-pane" aria-selected="true">
                <i class="fas fa-microphone-alt me-2"></i>Live Session
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-3 fw-bold" id="documentation-tab" data-bs-toggle="tab" data-bs-target="#documentation-pane" type="button" role="tab" aria-controls="documentation-pane" aria-selected="false">
                <i class="fas fa-file-medical me-2"></i>Clinical Documentation
            </button>
        </li>
    </ul>
    
    <style>
        #voiceAssistantTabs .nav-link {
            transition: all 0.3s ease;
        }
        #voiceAssistantTabs .nav-link:not(.active) {
            background-color: #ffffff !important;
            color: #333333 !important;
            border: 1px solid #dee2e6 !important;
            opacity: 1 !important;
        }
        #voiceAssistantTabs .nav-link:not(.active):hover {
            background-color: #f8f9fa !important;
            color: #DE6262 !important;
            border-color: #DE6262 !important;
        }
        #voiceAssistantTabs .nav-link.active {
            background-color: #DE6262 !important;
            color: white !important;
            border: 1px solid #DE6262 !important;
        }
    </style>

    <div class="tab-content" id="voiceAssistantTabsContent">
        <!-- Live Session Tab -->
        <div class="tab-pane fade show active" id="transcription-pane" role="tabpanel" aria-labelledby="transcription-tab">
            <!-- Main Content Grid -->
            <div class="row">
        <!-- Left Column: Transcription -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Real-time Transcript
                    </h5>
                    <div id="transcriptionStatus" class="d-flex align-items-center gap-2">
                        <!-- Status indicators will be inserted here -->
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="transcription-container" style="background-color: #f8f9fa;">
                        <div id="transcriptionContainer">
                            <div id="react-transcript-container"></div>
                            <textarea id="transcriptionArea" class="form-control" style="height: 100%; border: none; background: transparent; resize: none; display: none;" placeholder="Start ambient listening to see transcription here..."></textarea>
                        </div>
                    </div>
                    <div id="speakerLegend" class="p-3 bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-top: 3px solid #fff;">
                        <div class="d-flex align-items-center justify-content-center gap-3 text-white">
                            <i class="fas fa-users"></i>
                            <span class="badge bg-white text-primary px-3 py-2">Speaker 1</span>
                            <span class="badge bg-white text-success px-3 py-2">Speaker 2</span>
                        </div>
                    </div>

                    <!-- Transcript Controls -->
                    <div class="p-3 bg-light d-flex justify-content-between">
                        <button id="copyTranscriptBtn" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                        <div class="btn-group">
                            <button id="clearTranscriptBtn" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash me-1"></i> Clear
                            </button>
                            <button id="exportTranscriptBtn" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Chart Fields -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Clinical Chart
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Symptoms</label>
                            <textarea id="symptoms" class="form-control" rows="2" placeholder="Symptoms will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Medical History</label>
                            <textarea id="medicalHistory" class="form-control" rows="2" placeholder="Medical history will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Physical Findings</label>
                            <textarea id="physicalFindings" class="form-control" rows="2" placeholder="Physical findings will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Medications</label>
                            <textarea id="medications" class="form-control" rows="2" placeholder="Medications will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vital Signs</label>
                            <textarea id="vitalSigns" class="form-control" rows="2" placeholder="Vital signs will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Diagnosis</label>
                            <textarea id="diagnosis" class="form-control" rows="2" placeholder="Diagnosis suggestions will appear here..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Care Plan</label>
                            <textarea id="carePlan" class="form-control" rows="2" placeholder="Care plan will be generated automatically..."></textarea>
                        </div>

                        <!-- Confidence Score Indicator -->
                        <div class="col-12">
                            <div class="card bg-light border">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Transcription Accuracy:</span>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-3" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 75%" id="accuracyBar"></div>
                                            </div>
                                            <span class="badge bg-success" id="accuracyScore">75%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


            </div>
        </div>

        <!-- Clinical Documentation Tab -->
        <div class="tab-pane fade" id="documentation-pane" role="tabpanel" aria-labelledby="documentation-tab">
            <div id="ai-documentation-wrapper">
                @include('ai.documentation-intelligence', [
                    'appointment' => null,
                    'transcription' => null
                ])
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

    <!-- Ambient Listening Help Modal -->
    <div class="modal fade" id="ambientListeningHelpModal" tabindex="-1" aria-labelledby="ambientListeningHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="ambientListeningHelpModalLabel">
                        <i class="fas fa-headset me-2"></i>Ambient Listening Help
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-microphone-alt text-primary me-2"></i>How to Use</h6>
                            <ul class="mb-3">
                                <li>Select a patient from the dropdown</li>
                                <li>Click the <strong>Start Listening</strong> button to begin ambient recording</li>
                                <li>Speak naturally during the consultation</li>
                                <li>View real-time transcription in the left panel</li>
                                <li>Stop recording when consultation is complete</li>
                            </ul>

                            <h6><i class="fas fa-shield-alt text-success me-2"></i>Privacy & Security</h6>
                            <ul class="mb-3">
                                <li>All recordings are encrypted end-to-end</li>
                                <li>Transcriptions are processed securely</li>
                                <li>Data is HIPAA compliant</li>
                                <li>Only authorized personnel can access recordings</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Troubleshooting</h6>
                            <ul class="mb-3">
                                <li><strong>No microphone access:</strong> Check browser permissions</li>
                                <li><strong>Poor transcription:</strong> Ensure clear audio and minimal background noise</li>
                                <li><strong>Connection issues:</strong> Verify internet connection</li>
                                <li><strong>Wrong language:</strong> Adjust language settings before starting</li>
                            </ul>

                            <h6><i class="fas fa-keyboard text-info me-2"></i>Keyboard Shortcuts</h6>
                            <ul class="mb-3">
                                <li><kbd>Ctrl + Enter</kbd> - Start/Stop recording</li>
                                <li><kbd>Alt + T</kbd> - Focus on transcript</li>
                                <li><kbd>Enter</kbd> - Submit diagnosis</li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-light border">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Pro Tips</h6>
                        <ul class="mb-0">
                            <li>Position microphone close to both doctor and patient for best results</li>
                            <li>Ensure quiet environment to improve transcription accuracy</li>
                            <li>Use medical terminology for better AI analysis</li>
                            <li>Review transcript before generating AI analysis</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
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

<!-- Include React components for ambient listening -->
@viteReactRefresh
@vite(['resources/js/voice-assistant-main.jsx'])

<!-- Enhanced status indicator and UI script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update recording status visuals
        function updateRecordingStatus(status) {
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('recordingStatusText');

            if (!statusDot || !statusText) return;

            // Reset all classes
            statusDot.className = 'status-dot';
            statusText.textContent = getStatusText(status);

            // Add appropriate class based on status
            switch(status) {
                case 'idle':
                case 'stopped':
                    statusDot.classList.add('active');
                    statusText.innerHTML = '<span class="text-success">Ready to Listen</span>';
                    break;
                case 'connecting':
                    statusDot.classList.add('connecting');
                    statusText.innerHTML = '<span class="text-warning">Connecting...</span>';
                    break;
                case 'recording':
                    statusDot.classList.add('recording');
                    statusText.innerHTML = '<span class="text-danger">LIVE</span>';
                    break;
                case 'disconnected':
                    statusDot.classList.add('error');
                    statusText.innerHTML = '<span class="text-danger">Disconnected</span>';
                    break;
                case 'reconnecting':
                    statusDot.classList.add('connecting');
                    statusText.innerHTML = '<span class="text-warning">Reconnecting...</span>';
                    break;
                default:
                    statusText.innerHTML = '<span class="text-muted">Ready</span>';
            }
        }

        // Function to update accuracy score display
        function updateAccuracyScore(accuracy = 75) {
            const accuracyBar = document.getElementById('accuracyBar');
            const accuracyScore = document.getElementById('accuracyScore');

            if (!accuracyBar || !accuracyScore) return;

            // Calculate percentage based on confidence if available
            const score = Math.round(accuracy);
            accuracyBar.style.width = score + '%';
            accuracyScore.textContent = score + '%';
            accuracyScore.className = 'badge ' +
                (score > 80 ? 'bg-success' :
                 score > 60 ? 'bg-warning text-dark' : 'bg-danger');
        }

        // Helper function to get status text
        function getStatusText(status) {
            const statusMap = {
                'idle': 'Ready to Listen',
                'connecting': 'Connecting...',
                'recording': 'LIVE',
                'stopped': 'Stopped',
                'disconnected': 'Disconnected',
                'reconnecting': 'Reconnecting...'
            };
            return statusMap[status] || status;
        }

        // Listen for status updates from the React component
        window.addEventListener('transcriptUpdate', function(event) {
            const data = event.detail;
            if (data.status) {
                updateRecordingStatus(data.status);
            }

            // Update accuracy score if confidence is provided
            if (data.payload && data.payload.confidence !== undefined) {
                updateAccuracyScore(data.payload.confidence * 100);
            }
        });

        // Listen for WebSocket connection status changes
        window.addEventListener('websocketStatus', function(event) {
            const data = event.detail;
            if (data.status) {
                updateRecordingStatus(data.status);
            }
        });

        // Initialize status indicators
        updateRecordingStatus('idle');
        updateAccuracyScore(75);

        // Add functionality to transcript controls
        const copyTranscriptBtn = document.getElementById('copyTranscriptBtn');
        const clearTranscriptBtn = document.getElementById('clearTranscriptBtn');
        const exportTranscriptBtn = document.getElementById('exportTranscriptBtn');

        if (copyTranscriptBtn) {
            copyTranscriptBtn.addEventListener('click', function() {
                // Get all transcript text content
                const transcriptContainer = document.querySelector('.transcript-container');
                if (transcriptContainer) {
                    const text = transcriptContainer.innerText || transcriptContainer.textContent;
                    navigator.clipboard.writeText(text).then(function() {
                        // Show success feedback
                        const originalHTML = copyTranscriptBtn.innerHTML;
                        copyTranscriptBtn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
                        copyTranscriptBtn.classList.remove('btn-outline-secondary');
                        copyTranscriptBtn.classList.add('btn-success');

                        setTimeout(function() {
                            copyTranscriptBtn.innerHTML = originalHTML;
                            copyTranscriptBtn.classList.add('btn-outline-secondary');
                            copyTranscriptBtn.classList.remove('btn-success');
                        }, 2000);
                    });
                }
            });
        }

        if (clearTranscriptBtn) {
            clearTranscriptBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear the transcript? This cannot be undone.')) {
                    // Clear React transcript component by dispatching custom event
                    window.dispatchEvent(new CustomEvent('clearTranscript'));
                }
            });
        }

        if (exportTranscriptBtn) {
            exportTranscriptBtn.addEventListener('click', function() {
                // Get all transcript content for export
                const transcriptContainer = document.querySelector('.transcript-container');
                if (transcriptContainer) {
                    const text = transcriptContainer.innerText || transcriptContainer.textContent;
                    const blob = new Blob([text], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `transcript-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    // Show success feedback
                    const originalHTML = exportTranscriptBtn.innerHTML;
                    exportTranscriptBtn.innerHTML = '<i class="fas fa-check me-1"></i> Exported!';
                    exportTranscriptBtn.classList.remove('btn-outline-primary');
                    exportTranscriptBtn.classList.add('btn-success');

                    setTimeout(function() {
                        exportTranscriptBtn.innerHTML = originalHTML;
                        exportTranscriptBtn.classList.add('btn-outline-primary');
                        exportTranscriptBtn.classList.remove('btn-success');
                    }, 2000);
                }
            });
        }

        // Add event listener for when React component updates status
        window.addEventListener('statusUpdate', function(event) {
            const status = event.detail.status;
            updateRecordingStatus(status);
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to start/stop recording
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                // Simulate click on the recording button
                const recordingBtn = document.querySelector('.ambient-recorder-container .btn:not(.disabled)');
                if (recordingBtn) {
                    recordingBtn.click();
                }
            }

            // Alt + T to focus on transcript area
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                const transcriptContainer = document.querySelector('.transcript-container');
                if (transcriptContainer) {
                    transcriptContainer.focus();
                    // Scroll to the bottom of the transcript
                    transcriptContainer.scrollTop = transcriptContainer.scrollHeight;
                }
            }
        });

        // Custom Advanced Controls Toggle Implementation
        function initializeAdvancedControls() {
            console.log('Initializing Advanced Controls toggle');

            const toggleBtn = document.getElementById('advancedControlsToggleBtn');
            const advancedControlsDiv = document.getElementById('voiceAssistantAdvancedControls');

            if (toggleBtn && advancedControlsDiv) {
                console.log('Advanced controls elements found, setting up toggle');

                // Track the state of the advanced controls
                let advancedControlsVisible = false;

                // Remove any existing event listeners to prevent duplicates
                const newToggleBtn = toggleBtn.cloneNode(true);
                toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);

                newToggleBtn.addEventListener('click', function() {
                    console.log('Advanced controls toggle button clicked');

                    if (advancedControlsVisible) {
                        // Hide the controls
                        advancedControlsDiv.style.display = 'none';
                        newToggleBtn.innerHTML = '<i class="fas fa-cog me-1"></i> Advanced Controls';
                        advancedControlsVisible = false;
                        console.log('Advanced controls hidden');
                    } else {
                        // Show the controls
                        advancedControlsDiv.style.display = 'block';
                        newToggleBtn.innerHTML = '<i class="fas fa-cog me-1"></i> Advanced Controls (Hide)';
                        advancedControlsVisible = true;
                        console.log('Advanced controls shown');
                    }
                });

                console.log('Advanced controls toggle initialized successfully');
            } else {
                console.log('Advanced controls elements not found:', {
                    toggleBtn: !!toggleBtn,
                    advancedControlsDiv: !!advancedControlsDiv
                });
            }
        }

        // Initialize when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeAdvancedControls);
        } else {
            // DOM is already ready, initialize immediately
            initializeAdvancedControls();
        }
    });
</script>

<!-- Form components are now initialized by the main ambient listening script -->
<!-- This ensures proper timing and prevents conflicts -->
@endsection
