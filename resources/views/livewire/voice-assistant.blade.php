<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h1 class="card-title h3 mb-2">🎤 AI Voice Assistant</h1>
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
                        <button wire:click="$set('showNewPatientForm', true)" class="btn btn-outline-primary btn-sm" type="button">
                            <i class="fas fa-user-plus me-1"></i>
                            Add New Patient
                        </button>
                    </div>
                    <select wire:model.live="selectedPatient" class="form-select">
                        <option value="">Select a patient...</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient['id'] }}">{{ $patient['name'] }} ({{ $patient['age'] }}y, {{ ucfirst($patient['gender']) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- New Patient Form Modal -->
    @if($showNewPatientForm)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-user-plus me-2"></i>
                                Create New Patient
                            </h5>
                            <button wire:click="hideNewPatientForm" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" wire:model.live="newPatientName" class="form-control" placeholder="Enter patient's full name">
                                @error('newPatientName') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" wire:model.live="newPatientEmail" class="form-control" placeholder="patient@example.com">
                                @error('newPatientEmail') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Age *</label>
                                <input type="number" wire:model.live="newPatientAge" class="form-control" min="1" max="150" placeholder="Age">
                                @error('newPatientAge') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender *</label>
                                <select wire:model.live="newPatientGender" class="form-select">
                                    <option value="">Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('newPatientGender') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" wire:model.live="newPatientPhone" class="form-control" placeholder="Phone number">
                                @error('newPatientPhone') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> A default password "patient123" will be assigned. Please inform the patient to change it after first login.
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button wire:click="createNewPatient" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Patient
                            </button>
                            <button wire:click="hideNewPatientForm" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Control Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <!-- Recording Status -->
                            <div class="d-flex align-items-center me-4">
                                @if($isRecording)
                                    <span class="badge bg-danger me-2">
                                        <i class="fas fa-circle fa-xs"></i>
                                    </span>
                                    <span class="text-danger fw-bold">Recording...</span>
                                @else
                                    <span class="badge bg-secondary me-2">
                                        <i class="fas fa-circle fa-xs"></i>
                                    </span>
                                    <span class="text-muted">Not Recording</span>
                                @endif
                            </div>

                            <!-- Processing Status -->
                            @if($isProcessing)
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span class="text-primary">Processing...</span>
                                </div>
                            @endif
                        </div>

                        <!-- Language Selector and Hands-Free Toggle -->
                        <div class="d-flex align-items-center gap-3">
                            <!-- Language Selector -->
                            <div class="d-flex align-items-center">
                                <label class="form-label me-2 mb-0 small">Language:</label>
                                <select id="languageSelector" class="form-select form-select-sm" style="width: auto;">
                                    <option value="en">English</option>
                                    <option value="ar">العربية</option>
                                    <option value="fr">Français</option>
                                    <option value="es">Español</option>
                                    <option value="de">Deutsch</option>
                                </select>
                            </div>

                            <!-- Hands-Free Toggle -->
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="isHandsFreeMode" id="handsFreeToggle">
                                <label class="form-check-label" for="handsFreeToggle">
                                    Hands-Free Mode
                                    <i class="fas fa-info-circle text-muted ms-1"
                                       data-bs-toggle="tooltip"
                                       title="When enabled, recording will automatically restart if it stops, allowing continuous voice capture without manual intervention. Disable for manual control of start/stop recording."></i>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Control Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        @if(!$isRecording)
                            <button
                                wire:click="startSession"
                                class="btn btn-success"
                                @if(!$this->canStartRecording) disabled @endif
                                type="button"
                            >
                                <i class="fas fa-microphone me-2"></i>
                                Start Recording
                            </button>
                            @if(!$this->canStartRecording)
                                <small class="text-muted d-block mt-1">Please select a patient first</small>
                            @endif

                        @else
                            <button
                                wire:click="stopSession"
                                class="btn btn-danger"
                            >
                                <i class="fas fa-stop me-2"></i>
                                Stop Recording
                            </button>
                        @endif

                        <button
                            wire:click="generateAnalysis"
                            class="btn btn-primary"
                            @if(empty($transcription) || $isProcessing) disabled @endif
                            onclick="showProgressIndicator()"
                        >
                            @if($isProcessing)
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Generating Analysis...
                            @else
                                <i class="fas fa-brain me-2"></i>
                                Generate AI Analysis
                            @endif
                        </button>

                        <button
                            wire:click="resetSession"
                            class="btn btn-secondary"
                        >
                            <i class="fas fa-redo me-2"></i>
                            Reset
                        </button>

                        <a href="{{ route('voice-assistant.history') }}"
                           class="btn btn-info">
                            <i class="fas fa-history me-2"></i>
                            History
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

                    <!-- AI Analysis Progress Indicator (Livewire-based) -->
                    @if($isProcessing)
                        <div class="mt-3" style="animation: fadeIn 0.5s ease-in;">
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
                                            <p class="mb-0 text-muted small">
                                                @if($processingStage)
                                                    <span style="animation: fadeIn 0.3s ease-in;">{{ $processingStage }}</span>
                                                @else
                                                    Please wait while our AI analyzes the medical data and generates comprehensive insights...
                                                @endif
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
                    @endif
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
    </style>
        <!-- Success Message -->
    @if (session()->has('success'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    @endif
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
                    @if($transcription)
                        <div class="border rounded p-3" style="height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                            <p class="mb-0">{{ $transcription }}</p>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-microphone fa-3x mb-3"></i>
                            <p>Start recording to see transcription here...</p>
                        </div>
                    @endif
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
                            <textarea wire:model="symptoms" class="form-control" rows="2" placeholder="Symptoms will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Medical History</label>
                            <textarea wire:model="medicalHistory" class="form-control" rows="2" placeholder="Medical history will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Physical Findings</label>
                            <textarea wire:model="physicalFindings" class="form-control" rows="2" placeholder="Physical findings will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Medications</label>
                            <textarea wire:model="medications" class="form-control" rows="2" placeholder="Medications will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vital Signs</label>
                            <textarea wire:model="vitalSigns" class="form-control" rows="2" placeholder="Vital signs will be extracted automatically..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diagnosis</label>
                            <textarea wire:model="diagnosis" class="form-control" rows="2" placeholder="Diagnosis suggestions will appear here..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Care Plan</label>
                            <textarea wire:model="carePlan" class="form-control" rows="2" placeholder="Care plan will be generated automatically..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Diagnosis Button -->
    @if($aiAnalysis && !$diagnosisApproved)
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-end">
                    <button
                        type="button"
                        class="btn btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#diagnosisApprovalModal"
                    >
                        <i class="fas fa-check-circle me-2"></i>
                        Approve Diagnosis
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- AI Analysis Section -->
    @if($aiAnalysis)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card @if($diagnosisApproved) border-success @endif">
                    <div class="card-header @if($diagnosisApproved) bg-success text-white @endif">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-robot me-2"></i>
                                AI Clinical Analysis
                            </h5>
                            @if($diagnosisApproved)
                                <span class="badge bg-light text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Approved & Saved
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3" style="max-height: 500px; overflow-y: auto; background-color: #f8f9fa;">
                            <div style="white-space: pre-wrap;">{{ $aiAnalysis }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Diagnosis Approval Modal -->
    <div class="modal fade" id="diagnosisApprovalModal" tabindex="-1" aria-labelledby="diagnosisApprovalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="diagnosisApprovalModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Approve Diagnosis for Patient Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Review Before Approval:</strong> Please carefully review the AI analysis above. Once approved, this diagnosis will be saved to the patient's medical record and they will be able to view it from their account.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Patient:</strong>
                            @php
                                $selectedPatientData = collect($patients)->firstWhere('id', $selectedPatient);
                            @endphp
                            {{ $selectedPatientData ? $selectedPatientData['name'] : 'Unknown' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Session Date:</strong> {{ now()->format('M d, Y - H:i A') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Cancel - Continue Editing
                    </button>
                    <button wire:click="approveDiagnosis" class="btn btn-success" wire:loading.attr="disabled" onclick="setTimeout(hideDiagnosisModal, 100)">
                        <span wire:loading.remove>
                            <i class="fas fa-check-circle me-2"></i>
                            Yes, Approve & Save to Patient Record
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Section -->
    @if($showConfirmation)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Confirm & Save Consultation
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Please review the extracted information and AI analysis before saving to the patient's record.</p>
                        <div class="d-flex gap-2">
                            <button wire:click="confirmAndSave" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>
                                Confirm & Save
                            </button>
                            <button wire:click="$set('showConfirmation', false)" class="btn btn-secondary">
                                <i class="fas fa-edit me-2"></i>
                                Continue Editing
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- JavaScript for Voice Recognition -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug: Check if Livewire is loaded
    console.log('Livewire available:', typeof Livewire !== 'undefined');

    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    let recognition;
    let isListening = false;
    let restartTimeout;
    let finalTranscript = '';
    let lastTranscriptTime = 0;
    let transcriptBuffer = '';
    let bufferTimeout;

    // Language detection and configuration
    const supportedLanguages = {
        'ar': 'ar-SA',  // Arabic (Saudi Arabia)
        'en': 'en-US',  // English (US)
        'fr': 'fr-FR',  // French
        'es': 'es-ES',  // Spanish
        'de': 'de-DE',  // German
    };

    let currentLanguage = 'en-US'; // Default to English

    // Function to detect language from text (simple heuristic)
    function detectLanguage(text) {
        const arabicPattern = /[\u0600-\u06FF]/;
        const englishPattern = /[a-zA-Z]/;

        if (arabicPattern.test(text)) {
            return 'ar-SA';
        } else if (englishPattern.test(text)) {
            return 'en-US';
        }
        return currentLanguage; // Keep current if can't detect
    }

    // Check if browser supports speech recognition
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();

        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = currentLanguage;
        recognition.maxAlternatives = 3; // Get multiple alternatives for better accuracy

        recognition.onresult = function(event) {
            let interimTranscript = '';
            let newFinalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const result = event.results[i];
                const transcript = result[0].transcript;

                if (result.isFinal) {
                    newFinalTranscript += transcript + ' ';

                    // Detect language and switch if needed
                    const detectedLang = detectLanguage(transcript);
                    if (detectedLang !== currentLanguage) {
                        currentLanguage = detectedLang;
                        console.log('Language switched to:', currentLanguage);
                        // Restart recognition with new language
                        if (isListening) {
                            recognition.lang = currentLanguage;
                        }
                    }
                } else {
                    interimTranscript += transcript;
                }
            }

            // Update final transcript
            if (newFinalTranscript) {
                finalTranscript += newFinalTranscript;
                lastTranscriptTime = Date.now();
            }

            // Buffer the transcript to avoid too frequent updates
            transcriptBuffer = finalTranscript + interimTranscript;

            // Clear existing timeout
            if (bufferTimeout) {
                clearTimeout(bufferTimeout);
            }

            // Send transcript after a short delay to batch updates
            bufferTimeout = setTimeout(() => {
                if (transcriptBuffer.trim()) {
                    try {
                        @this.call('handleTranscription', transcriptBuffer.trim());
                    } catch (error) {
                        console.error('Error sending transcription:', error);
                    }
                }
            }, 500); // 500ms delay to batch updates
        };

        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);

            switch(event.error) {
                case 'not-allowed':
                    alert('Microphone access denied. Please allow microphone access and try again.');
                    isListening = false;
                    break;
                case 'no-speech':
                    console.log('No speech detected, continuing...');
                    // Don't show error for no-speech, it's normal
                    break;
                case 'audio-capture':
                    alert('No microphone found. Please check your microphone connection.');
                    isListening = false;
                    break;
                case 'network':
                    console.log('Network error, retrying...');
                    // Will auto-restart if in hands-free mode
                    break;
                case 'aborted':
                    console.log('Speech recognition aborted');
                    break;
                default:
                    console.log('Speech recognition error:', event.error);
            }
        };

        recognition.onstart = function() {
            console.log('Speech recognition started with language:', currentLanguage);
        };

        recognition.onend = function() {
            console.log('Speech recognition ended');

            if (isListening) {
                // Auto-restart in hands-free mode or if still supposed to be listening
                if (@this.isHandsFreeMode || @this.isRecording) {
                    restartTimeout = setTimeout(() => {
                        if (isListening && (@this.isHandsFreeMode || @this.isRecording)) {
                            try {
                                recognition.lang = currentLanguage; // Ensure language is set
                                recognition.start();
                            } catch (error) {
                                console.error('Error restarting recognition:', error);
                                // Try again after a longer delay
                                setTimeout(() => {
                                    if (isListening) {
                                        try {
                                            recognition.start();
                                        } catch (e) {
                                            console.error('Failed to restart recognition:', e);
                                            isListening = false;
                                        }
                                    }
                                }, 2000);
                            }
                        }
                    }, 100);
                }
            }
        };
    } else {
        alert('Your browser does not support speech recognition. Please use Chrome, Edge, or Safari.');
    }

    // Listen for Livewire events
    window.addEventListener('startVoiceRecording', function() {
        if (recognition && !isListening) {
            isListening = true;
            finalTranscript = '';
            transcriptBuffer = '';

            // Clear any existing timeouts
            if (bufferTimeout) clearTimeout(bufferTimeout);
            if (restartTimeout) clearTimeout(restartTimeout);

            try {
                recognition.lang = currentLanguage;
                recognition.start();
                console.log('Voice recording started');
            } catch (error) {
                console.error('Error starting recognition:', error);
                isListening = false;
            }
        }
    });

    window.addEventListener('stopVoiceRecording', function() {
        if (recognition && isListening) {
            isListening = false;

            // Clear timeouts
            if (restartTimeout) clearTimeout(restartTimeout);
            if (bufferTimeout) clearTimeout(bufferTimeout);

            try {
                recognition.stop();
                console.log('Voice recording stopped');

                // Send any remaining buffered transcript
                if (transcriptBuffer.trim()) {
                    @this.call('handleTranscription', transcriptBuffer.trim());
                }
            } catch (error) {
                console.error('Error stopping recognition:', error);
            }
        }
    });

    // Handle hands-free mode changes and AI processing completion
    let previousProcessingState = false;
    document.addEventListener('livewire:updated', function() {
        // Check if recording state changed
        if (@this.isRecording && !isListening) {
            window.dispatchEvent(new CustomEvent('startVoiceRecording'));
        } else if (!@this.isRecording && isListening) {
            window.dispatchEvent(new CustomEvent('stopVoiceRecording'));
        }

        // Check if AI processing just completed
        const currentProcessingState = @this.isProcessing;
        if (previousProcessingState && !currentProcessingState) {
            // AI processing just finished
            console.log('AI analysis completed!');

            // Optional: Play a subtle notification sound
            try {
                // Create a subtle notification sound
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);

                gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.2);
            } catch (error) {
                console.log('Audio notification not available:', error);
            }

            // Hide the JavaScript progress indicator
            hideProgressIndicator();
        }

        // Show/hide JavaScript progress indicator based on processing state
        if (currentProcessingState && !previousProcessingState) {
            // Processing just started - hide JS indicator after a delay since Livewire one should show
            setTimeout(() => {
                hideProgressIndicator();
            }, 500);
        }

        previousProcessingState = currentProcessingState;
    });

    // Add language selector (optional enhancement)
    function setRecognitionLanguage(lang) {
        if (supportedLanguages[lang]) {
            currentLanguage = supportedLanguages[lang];
            if (recognition && isListening) {
                // Restart with new language
                recognition.stop();
                setTimeout(() => {
                    if (isListening) {
                        recognition.lang = currentLanguage;
                        recognition.start();
                    }
                }, 100);
            }
            console.log('Recognition language set to:', currentLanguage);
        }
    }

    // Make function available globally for potential UI controls
    window.setRecognitionLanguage = setRecognitionLanguage;

    // Add event listener for language selector
    document.getElementById('languageSelector').addEventListener('change', function(e) {
        const selectedLang = e.target.value;
        setRecognitionLanguage(selectedLang);
        console.log('Language changed to:', selectedLang);
    });

    // Set initial language to Arabic if user prefers it
    const userLang = navigator.language || navigator.userLanguage;
    if (userLang.startsWith('ar')) {
        document.getElementById('languageSelector').value = 'ar';
        setRecognitionLanguage('ar');
    }

    // Progress indicator functions
    window.showProgressIndicator = function() {
        const indicator = document.getElementById('jsProgressIndicator');
        if (indicator) {
            indicator.style.display = 'block';

            // Simulate progress stages
            const stages = [
                'Initializing AI analysis...',
                'Extracting medical data from transcription...',
                'Analyzing medical content with AI...',
                'Generating comprehensive medical analysis...',
                'Processing results...'
            ];

            let currentStage = 0;
            const stageElement = document.getElementById('jsProcessingStage');

            const updateStage = () => {
                if (currentStage < stages.length && indicator.style.display !== 'none') {
                    stageElement.textContent = stages[currentStage];
                    currentStage++;
                    setTimeout(updateStage, 3000); // Update every 3 seconds
                }
            };

            setTimeout(updateStage, 1000); // Start after 1 second
        }
    };

    window.hideProgressIndicator = function() {
        const indicator = document.getElementById('jsProgressIndicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    };
});

// Function to hide the diagnosis approval modal
function hideDiagnosisModal() {
    const modalElement = document.getElementById('diagnosisApprovalModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
}

// Listen for Livewire updates to close the modal when diagnosis is approved
document.addEventListener('livewire:updated', function () {
    // Check if diagnosis was approved
    if (@this.diagnosisApproved) {
        hideDiagnosisModal();
    }
});
</script>
