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
                        <button type="button" id="createNewPatientBtn" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>
                            Create Patient
                        </button>
                        <button type="button" id="cancelNewPatientBtn" class="btn btn-secondary">
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

    <!-- Control Panel - Compact Global Settings -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-3">
                    <!-- Language Selector and Global Controls -->
                    <div class="d-flex justify-content-between align-items-center">
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

                        <!-- Global Action Buttons -->
                        <div class="d-flex gap-2">
                            <a href="{{ route('ai.voice-assistant.training') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-graduation-cap me-1"></i>Guide
                            </a>
                            <a href="{{ route('ai.voice-assistant.recorded-voices') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-history me-1"></i>History
                            </a>
                            <a href="{{ route('ai.voice-assistant.performance') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chart-line me-1"></i>Stats
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#ambientListeningHelpModal">
                                <i class="fas fa-question-circle me-1"></i>Help
                            </button>
                        </div>
                    </div>

                    <!-- Advanced Controls Toggle -->
                    <div class="mt-3">
                        <button id="advancedControlsToggleBtn" class="btn btn-outline-primary btn-sm w-100" type="button">
                            <i class="fas fa-cog me-1"></i> Advanced Controls
                        </button>
                        <div id="voiceAssistantAdvancedControls" class="card card-body bg-white p-3 mt-3 border" style="display: none;">
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
            background-color: #DE6262;
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
            border-left-color: #DE6262;
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
            min-height: 400px;
            max-height: 60vh;
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

    </ul>
    
    <style>
        #voiceAssistantTabs .nav-link {
            transition: all 0.3s ease;
        }
        #voiceAssistantTabs .nav-link:not(.active) {
            background-color: #ffffff !important;
            color: #6c757d !important;
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
            box-shadow: 0 2px 4px rgba(222, 98, 98, 0.2) !important;
        }

        /* Enhanced tab-content connection */
        .tab-content {
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Recording state visual emphasis */
        #transcriptCard.recording-active {
            animation: recordingGlow 2s ease-in-out infinite alternate;
            border-color: #DE6262 !important;
            box-shadow: 0 0 20px rgba(222, 98, 98, 0.4) !important;
        }

        @keyframes recordingGlow {
            from {
                box-shadow: 0 0 20px rgba(222, 98, 98, 0.4);
                border-color: #DE6262;
            }
            to {
                box-shadow: 0 0 30px rgba(222, 98, 98, 0.7);
                border-color: #c55252;
            }
        }

        /* Enhanced status dot in header */
        .card-header .status-dot.recording {
            animation: recordingPulse 0.8s infinite;
        }

        @keyframes recordingPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.3); }
        }

        /* Better visual hierarchy */
        .card-header.bg-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Consistent button colors */
        .btn-warning {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #212529 !important;
        }

        .btn-warning:hover:not(:disabled) {
            background-color: #e0a800 !important;
            border-color: #d39e00 !important;
        }

        .btn-info {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
            color: white !important;
        }

        .btn-info:hover:not(:disabled) {
            background-color: #138496 !important;
            border-color: #117a8b !important;
        }

        /* Improved button spacing in header */
        .card-header .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        /* Status text in header */
        .card-header #recordingStatusText {
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>

    <div class="tab-content" id="voiceAssistantTabsContent">
        <!-- Live Session Tab -->
        <div class="tab-pane fade show active" id="transcription-pane" role="tabpanel" aria-labelledby="transcription-tab">
            <!-- Main Content Grid -->
            <div class="row">
        <!-- Left Column: Transcription -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-sm border-0" id="transcriptCard">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-microphone-alt me-2"></i>
                            Real-time Transcript
                        </h5>
                        <div id="transcriptionStatus" class="d-flex align-items-center gap-2">
                            <!-- Status indicators will be inserted here -->
                        </div>
                    </div>

                    <!-- Recording Controls - Now in Header -->
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <!-- Recording Status -->
                        <div class="d-flex align-items-center me-3">
                            <div class="status-indicator me-2">
                                <span class="status-dot" id="statusDot"></span>
                            </div>
                            <span class="text-white fw-bold" id="recordingStatusText">Ready to Listen</span>
                        </div>

                        <!-- Audio Recorder -->
                        <div id="react-audio-recorder-container" style="max-width: 200px;"></div>

                        <!-- Recording Buttons -->
                        <div class="d-flex gap-2">
                            <button id="startRecordingBtn" class="btn btn-success btn-sm d-none" type="button" disabled>
                                <i class="fas fa-microphone me-1"></i>Start
                            </button>
                            <button id="stopRecordingBtn" class="btn btn-danger btn-sm d-none" disabled>
                                <i class="fas fa-stop me-1"></i>Stop
                            </button>
                        </div>

                        <!-- Processing Status -->
                        <div id="processingStatus" style="display: none;" class="ms-3">
                            <div class="spinner-border spinner-border-sm text-light me-2" role="status"></div>
                            <span class="text-light fw-bold">Processing...</span>
                        </div>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button id="generateAnalysisBtn" class="btn btn-warning btn-sm" disabled style="background-color: #ffc107; border-color: #ffc107;">
                            <i class="fas fa-brain me-1"></i>AI Analysis
                        </button>
                        <button id="resetSessionBtn" class="btn btn-secondary btn-sm">
                            <i class="fas fa-redo me-1"></i>Reset
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="transcription-container" style="background-color: #f8f9fa;">
                        <div id="transcriptionContainer">
                            <div id="react-transcript-container"></div>
                            <textarea id="transcriptionArea" class="form-control" style="height: 100%; border: none; background: transparent; resize: none; display: none;" placeholder="Start ambient listening to see transcription here..."></textarea>
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

                    <!-- Additional Patient Data Section -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-3">
                            <i class="fas fa-notes-medical me-2"></i>Additional Patient Data
                            <span class="badge bg-warning text-dark ms-2">Important for AI Prescriptions</span>
                        </h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Required for AI medication suggestions:</strong> Please fill allergies and current medications to enable safe AI prescription recommendations.
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_allergies" class="form-label fw-semibold">
                                    Patient Allergies <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="modal_allergies" rows="2"
                                          placeholder="e.g., Penicillin, Sulfa drugs, or type 'None'"></textarea>
                                <div class="form-text">Separate multiple allergies with commas</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_medications" class="form-label fw-semibold">
                                    Current Medications <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="modal_medications" rows="2"
                                          placeholder="e.g., Metformin 500mg twice daily"></textarea>
                                <div class="form-text">Include dosage and frequency if known</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_symptoms" class="form-label fw-semibold">Symptoms</label>
                                <textarea class="form-control" id="modal_symptoms" rows="2"
                                          placeholder="List patient symptoms..."></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_medical_history" class="form-label fw-semibold">Medical History</label>
                                <textarea class="form-control" id="modal_medical_history" rows="2"
                                          placeholder="Relevant medical history..."></textarea>
                            </div>
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
            const transcriptCard = document.getElementById('transcriptCard');

            if (!statusDot || !statusText) return;

            // Reset all classes
            statusDot.className = 'status-dot';
            statusText.textContent = getStatusText(status);

            // Remove recording emphasis
            if (transcriptCard) {
                transcriptCard.classList.remove('recording-active');
            }

            // Add appropriate class based on status
            switch(status) {
                case 'idle':
                case 'stopped':
                    statusDot.classList.add('active');
                    statusText.innerHTML = '<span class="text-white fw-bold">Ready to Listen</span>';
                    break;
                case 'connecting':
                    statusDot.classList.add('connecting');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #ffc107 !important;">Connecting...</span>';
                    break;
                case 'recording':
                    statusDot.classList.add('recording');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #dc3545 !important;">🔴 LIVE</span>';
                    // Add visual emphasis to transcript card
                    if (transcriptCard) {
                        transcriptCard.classList.add('recording-active');
                    }
                    break;
                case 'disconnected':
                    statusDot.classList.add('error');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #dc3545 !important;">Disconnected</span>';
                    break;
                case 'reconnecting':
                    statusDot.classList.add('connecting');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #ffc107 !important;">Reconnecting...</span>';
                    break;
                default:
                    statusText.innerHTML = '<span class="text-white fw-bold">Ready</span>';
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

                // Enable AI Analysis and Clinical Doc buttons when recording stops
                const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
                const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

                if (data.status === 'stopped') {
                    if (generateAnalysisBtn) {
                        generateAnalysisBtn.disabled = false;
                        generateAnalysisBtn.style.opacity = '1';
                    }
                    if (generateClinicalDocBtn) {
                        generateClinicalDocBtn.disabled = false;
                        generateClinicalDocBtn.style.opacity = '1';
                    }
                }
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

        // Listen for server transcript ready event
        window.addEventListener('serverTranscriptReady', function(event) {
            console.log('Server transcript ready - enabling buttons');
            // Enable AI Analysis and Clinical Doc buttons when server processing completes
            const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
            const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

            if (generateAnalysisBtn) {
                generateAnalysisBtn.disabled = false;
                generateAnalysisBtn.style.opacity = '1';
                generateAnalysisBtn.style.cursor = 'pointer';
                console.log('Analysis button enabled via server transcript');
            }
            if (generateClinicalDocBtn) {
                generateClinicalDocBtn.disabled = false;
                generateClinicalDocBtn.style.opacity = '1';
                generateClinicalDocBtn.style.cursor = 'pointer';
                console.log('Clinical doc button enabled via server transcript');
            }
        });

        // Add click handler for AI Analysis button
        document.getElementById('generateAnalysisBtn').addEventListener('click', function() {
            console.log('AI Analysis button clicked');
            const transcriptContainer = document.getElementById('react-transcript-container');
            const transcript = transcriptContainer ? transcriptContainer.innerText.trim() : '';
            
            if (!transcript || transcript.length < 20) {
                alert('Please record more audio. Transcript is too short for analysis.');
                return;
            }
            
            const patientSelect = document.getElementById('patientSelect');
            if (!patientSelect || !patientSelect.value) {
                alert('Please select a patient first');
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyzing...';
            
            fetch('{{ route("ai.voice-assistant.generate-ai-analysis") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    transcription: transcript,
                    sessionId: sessionId || '',
                    selectedPatient: patientSelect.value,
                    _token: '{{ csrf_token() }}'
                })
            })
            .then(r => {
                if (!r.ok) throw new Error('Server error: ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    // Show in modal
                    const modalHtml = `
                        <div class="modal fade" id="aiAnalysisModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title"><i class="fas fa-brain me-2"></i>AI Medical Analysis</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div style="white-space: pre-wrap; font-family: monospace;">${data.aiAnalysis || JSON.stringify(data, null, 2)}</div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
                    modal.show();
                    document.getElementById('aiAnalysisModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                } else {
                    alert('Error: ' + (data.message || 'Failed'));
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-brain me-1"></i>AI Analysis';
            })
            .catch(e => {
                alert('Error: ' + e.message);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-brain me-1"></i>AI Analysis';
            });
        });

        // Fallback: Enable buttons after recording stops (with delay to ensure status is set)
        window.addEventListener('statusUpdate', function(event) {
            const status = event.detail.status;
            if (status === 'stopped' || status === 'idle') {
                // Don't enable buttons here - wait for serverTranscriptReady event
                console.log('Recording stopped, waiting for server processing...');
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
                const transcriptContainer = document.getElementById('react-transcript-container');
                if (transcriptContainer) {
                    const text = transcriptContainer.innerText || transcriptContainer.textContent;
                    if (!text.trim()) {
                        alert('No transcript to copy');
                        return;
                    }
                    navigator.clipboard.writeText(text).then(function() {
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
                const transcriptContainer = document.getElementById('react-transcript-container');
                if (!transcriptContainer || !transcriptContainer.innerText.trim()) {
                    alert('No transcript to clear');
                    return;
                }
                if (confirm('Are you sure you want to clear the transcript? This cannot be undone.')) {
                    window.dispatchEvent(new CustomEvent('clearTranscript'));
                    transcriptContainer.innerHTML = '';
                }
            });
        }

        if (exportTranscriptBtn) {
            exportTranscriptBtn.addEventListener('click', function() {
                const transcriptContainer = document.getElementById('react-transcript-container');
                if (transcriptContainer) {
                    const text = transcriptContainer.innerText || transcriptContainer.textContent;
                    if (!text.trim()) {
                        alert('No transcript to export');
                        return;
                    }
                    const blob = new Blob([text], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `transcript-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
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
            console.log('Status update received:', status);
            updateRecordingStatus(status);

            // Enable AI Analysis and Clinical Doc buttons when recording stops
            const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
            const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

            console.log('Button states before update:', {
                analysisDisabled: generateAnalysisBtn?.disabled,
                clinicalDisabled: generateClinicalDocBtn?.disabled,
                status: status
            });

            if (status === 'stopped') {
                console.log('Enabling buttons for stopped status');
                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = false;
                    generateAnalysisBtn.style.opacity = '1';
                    console.log('Analysis button enabled');
                }
                if (generateClinicalDocBtn) {
                    generateClinicalDocBtn.disabled = false;
                    generateClinicalDocBtn.style.opacity = '1';
                    console.log('Clinical doc button enabled');
                }
            } else if (status === 'idle' || status === 'recording') {
                // Disable buttons when not stopped
                console.log('Disabling buttons for status:', status);
                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = true;
                    generateAnalysisBtn.style.opacity = '0.6';
                }
                if (generateClinicalDocBtn) {
                    generateClinicalDocBtn.disabled = true;
                    generateClinicalDocBtn.style.opacity = '0.6';
                }
            }

            console.log('Button states after update:', {
                analysisDisabled: generateAnalysisBtn?.disabled,
                clinicalDisabled: generateClinicalDocBtn?.disabled
            });
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

        // AI Analysis functionality handled by button-click-handlers.js
        // Removed duplicate code - using external handler

        // Removed duplicate generateAIAnalysis - using button-click-handlers.js

        // Removed duplicate generateClinicalDoc - button removed from UI

        // Update clinical chart fields with AI results
        function updateClinicalFields(data) {
            if (data.symptoms) {
                const symptomsField = document.getElementById('symptoms');
                if (symptomsField) symptomsField.value = data.symptoms;
            }

            if (data.medical_history) {
                const medicalHistoryField = document.getElementById('medicalHistory');
                if (medicalHistoryField) medicalHistoryField.value = data.medical_history;
            }

            if (data.physical_findings) {
                const physicalFindingsField = document.getElementById('physicalFindings');
                if (physicalFindingsField) physicalFindingsField.value = data.physical_findings;
            }

            if (data.medications) {
                const medicationsField = document.getElementById('medications');
                if (medicationsField) medicationsField.value = data.medications;
            }

            if (data.vital_signs) {
                const vitalSignsField = document.getElementById('vitalSigns');
                if (vitalSignsField) vitalSignsField.value = data.vital_signs;
            }

            if (data.diagnosis) {
                const diagnosisField = document.getElementById('diagnosis');
                if (diagnosisField) diagnosisField.value = data.diagnosis;
            }

            if (data.care_plan) {
                const carePlanField = document.getElementById('carePlan');
                if (carePlanField) carePlanField.value = data.care_plan;
            }
        }

        // Alert function for user feedback
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            if (!alertContainer) return;

            const alertClass = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            const alertHTML = `
                <div class="${alertClass}" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            alertContainer.innerHTML = alertHTML;

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

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
            document.addEventListener('DOMContentLoaded', function() {
                initializeAdvancedControls();
            // Removed - using button-click-handlers.js
                initializeButtonStates();
            });
        } else {
            // DOM is already ready, initialize immediately
            initializeAdvancedControls();
            // Removed - using button-click-handlers.js
            initializeButtonStates();
        }

        // Initialize button states based on available content
        function initializeButtonStates() {
            // Check if there's any transcript content available
            const transcriptContainer = document.querySelector('.transcript-container');
            const transcriptionArea = document.getElementById('transcriptionArea');

            let hasContent = false;
            if (transcriptContainer && transcriptContainer.innerText.trim()) {
                hasContent = true;
            } else if (transcriptionArea && transcriptionArea.value.trim()) {
                hasContent = true;
            }

            // If there's content, enable the buttons
            if (hasContent) {
                console.log('Content detected on page load - enabling buttons');
                const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
                const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = false;
                    generateAnalysisBtn.style.opacity = '1';
                }
                if (generateClinicalDocBtn) {
                    generateClinicalDocBtn.disabled = false;
                    generateClinicalDocBtn.style.opacity = '1';
                }
            }
        }
    });

    // New patient form toggle
    const showNewPatientFormBtn = document.getElementById('showNewPatientFormBtn');
    const hideNewPatientFormBtn = document.getElementById('hideNewPatientFormBtn');
    const cancelNewPatientBtn = document.getElementById('cancelNewPatientBtn');
    const newPatientForm = document.getElementById('newPatientForm');

    if (showNewPatientFormBtn && newPatientForm) {
        showNewPatientFormBtn.addEventListener('click', () => {
            newPatientForm.style.display = 'block';
            newPatientForm.scrollIntoView({ behavior: 'smooth' });
        });
    }

    if (hideNewPatientFormBtn && newPatientForm) {
        hideNewPatientFormBtn.addEventListener('click', () => {
            newPatientForm.style.display = 'none';
        });
    }

    if (cancelNewPatientBtn && newPatientForm) {
        cancelNewPatientBtn.addEventListener('click', () => {
            newPatientForm.style.display = 'none';
        });
    }

    // Create new patient button handler
    const createNewPatientBtn = document.getElementById('createNewPatientBtn');
    if (createNewPatientBtn) {
        createNewPatientBtn.addEventListener('click', function() {
            const nameField = document.getElementById('newPatientName');
            const phoneField = document.getElementById('newPatientPhone');
            const emailField = document.getElementById('newPatientEmail');
            const ageField = document.getElementById('newPatientAge');
            const genderField = document.getElementById('newPatientGender');
            
            const name = nameField ? nameField.value.trim() : '';
            const phone = phoneField ? phoneField.value.trim() : '';
            const email = emailField ? emailField.value.trim() : '';
            const age = ageField ? parseInt(ageField.value) || 25 : 25;
            const gender = genderField ? genderField.value : 'male';
            
            console.log('Creating patient with:', {name, phone, email, age, gender});
            
            if (!name || !phone) {
                alert('Name and phone are required');
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
            
            fetch('{{ route("ai.voice-assistant.create-new-patient") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    newPatientName: name,
                    newPatientPhone: phone,
                    newPatientEmail: email,
                    newPatientAge: age,
                    newPatientGender: gender,
                    _token: '{{ csrf_token() }}'
                })
            })
            .then(r => {
                console.log('Create patient response:', r.status);
                return r.text().then(text => {
                    console.log('Response text:', text.substring(0, 200));
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Not JSON, full response:', text);
                        throw new Error('Server returned HTML instead of JSON');
                    }
                });
            })
            .then(data => {
                console.log('Create patient response:', data);
                if (data.success) {
                    alert('Patient created successfully!');
                    // Add to dropdown
                    const select = document.getElementById('patientSelect');
                    const patientLabel = `${data.patient.name} (${data.patient.age || '?'}y, ${data.patient.gender || 'Unknown'})`;
                    const option = new Option(patientLabel, data.patient.id, true, true);
                    select.add(option);
                    // Hide form
                    document.getElementById('newPatientForm').style.display = 'none';
                    // Clear form
                    if (nameField) nameField.value = '';
                    if (phoneField) phoneField.value = '';
                    if (emailField) emailField.value = '';
                    if (ageField) ageField.value = '';
                    if (genderField) genderField.value = '';
                } else {
                    alert('Error: ' + (data.message || 'Failed to create patient'));
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Patient';
            })
            .catch(e => {
                alert('Error: ' + e.message);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Patient';
            });
        });
    }

    // Enable complete button when diagnosis is filled
    const diagnosisText = document.getElementById('diagnosisText');
    const completeBtn = document.getElementById('completeConsultationBtn');
    if (diagnosisText && completeBtn) {
        diagnosisText.addEventListener('input', function() {
            completeBtn.disabled = this.value.trim().length === 0;
        });
        
        completeBtn.addEventListener('click', function() {
            const diagnosis = diagnosisText.value.trim();
            if (!diagnosis) {
                alert('Please enter your diagnosis first.');
                return;
            }
            
            const patientSelect = document.getElementById('patientSelect');
            const selectedPatient = patientSelect ? patientSelect.value : null;
            const sessionId = window.sessionId || document.querySelector('[data-session-id]')?.getAttribute('data-session-id');
            const transcriptContainer = document.getElementById('react-transcript-container');
            const transcription = transcriptContainer ? (transcriptContainer.innerText || transcriptContainer.textContent || '').trim() : '';
            
            if (!selectedPatient) {
                alert('Please select a patient.');
                return;
            }
            
            if (!transcription) {
                alert('No transcript available. Please record a session first.');
                return;
            }
            
            // Show modal with additional patient data fields
            const modal = new bootstrap.Modal(document.getElementById('completeConsultationModal'));
            document.getElementById('diagnosisPreview').textContent = diagnosis;
            document.getElementById('modalPatientName').textContent = patientSelect.options[patientSelect.selectedIndex].text;
            modal.show();
        });
    }
    
    // Handle modal complete button
    const modalCompleteBtn = document.getElementById('modalCompleteConsultationBtn');
    if (modalCompleteBtn) {
        modalCompleteBtn.addEventListener('click', function() {
            const diagnosisText = document.getElementById('diagnosisText');
            const diagnosis = diagnosisText.value.trim();
            const patientSelect = document.getElementById('patientSelect');
            const selectedPatient = patientSelect ? patientSelect.value : null;
            const sessionId = window.sessionId || document.querySelector('[data-session-id]')?.getAttribute('data-session-id');
            const transcriptContainer = document.getElementById('react-transcript-container');
            const transcription = transcriptContainer ? (transcriptContainer.innerText || transcriptContainer.textContent || '').trim() : '';
            
            // Get additional patient data
            const allergies = document.getElementById('modal_allergies').value.trim();
            const medications = document.getElementById('modal_medications').value.trim();
            const symptoms = document.getElementById('modal_symptoms').value.trim();
            const medicalHistory = document.getElementById('modal_medical_history').value.trim();
            
            modalCompleteBtn.disabled = true;
            modalCompleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            
            fetch('/ai/voice-assistant/complete-consultation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    diagnosisText: diagnosis,
                    selectedPatient: selectedPatient,
                    transcription: transcription,
                    sessionId: sessionId,
                    completionType: 'save_only',
                    patient_data: {
                        allergies: allergies,
                        medications: medications,
                        symptoms: symptoms,
                        medical_history: medicalHistory
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Consultation completed successfully!');
                    if (data.redirectUrl) {
                        window.location.href = data.redirectUrl;
                    } else {
                        location.reload();
                    }
                } else {
                    throw new Error(data.message || 'Failed to complete consultation');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                modalCompleteBtn.disabled = false;
                modalCompleteBtn.innerHTML = '<i class="fas fa-check me-1"></i>Complete Session';
            });
        });
    }

    // Reset button functionality
    const resetBtn = document.getElementById('resetSessionBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset? This will clear the current session.')) {
                location.reload();
            }
        });
    }

    // Show diagnosis form after recording stops
    window.addEventListener('serverTranscriptReady', function() {
        const diagnosisForm = document.getElementById('diagnosisEntryForm');
        if (diagnosisForm) {
            diagnosisForm.style.display = 'block';
            diagnosisForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
</script>

<!-- Form components are now initialized by the main ambient listening script -->
<!-- This ensures proper timing and prevents conflicts -->
@endsection
