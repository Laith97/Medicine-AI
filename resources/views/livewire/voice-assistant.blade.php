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

    <!-- Patient Selection -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <label class="form-label fw-bold">Select Patient</label>
                    <select wire:model="selectedPatient" class="form-select">
                        <option value="">Select a patient...</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient['id'] }}">{{ $patient['name'] }} ({{ $patient['age'] }}y, {{ ucfirst($patient['gender']) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

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

                        <!-- Hands-Free Toggle -->
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="isHandsFreeMode" id="handsFreeToggle">
                            <label class="form-check-label" for="handsFreeToggle">
                                Hands-Free Mode
                            </label>
                        </div>
                    </div>

                    <!-- Control Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        @if(!$isRecording)
                            <button
                                wire:click="startSession"
                                class="btn btn-success"
                                @if(!$selectedPatient) disabled @endif
                            >
                                <i class="fas fa-microphone me-2"></i>
                                Start Recording
                            </button>
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
                            @if(empty($transcription)) disabled @endif
                        >
                            <i class="fas fa-brain me-2"></i>
                            Generate AI Analysis
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
                </div>
            </div>
        </div>
    </div>

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

    <!-- AI Analysis Section -->
    @if($aiAnalysis)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-robot me-2"></i>
                            AI Clinical Analysis
                        </h5>
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
    let recognition;
    let isListening = false;
    let restartTimeout;

    // Check if browser supports speech recognition
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();

        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        let finalTranscript = '';

        recognition.onresult = function(event) {
            let interimTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript + ' ';
                } else {
                    interimTranscript += transcript;
                }
            }

            // Send the complete transcript to Livewire
            const completeTranscript = finalTranscript + interimTranscript;
            @this.call('handleTranscription', completeTranscript);
        };

        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);
            if (event.error === 'not-allowed') {
                alert('Microphone access denied. Please allow microphone access and try again.');
            }
        };

        recognition.onend = function() {
            if (isListening && @this.isHandsFreeMode) {
                // Auto-restart in hands-free mode
                restartTimeout = setTimeout(() => {
                    if (isListening) {
                        recognition.start();
                    }
                }, 100);
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
            recognition.start();
        }
    });

    window.addEventListener('stopVoiceRecording', function() {
        if (recognition && isListening) {
            isListening = false;
            clearTimeout(restartTimeout);
            recognition.stop();
        }
    });

    // Handle hands-free mode changes
    document.addEventListener('livewire:updated', function() {
        if (@this.isRecording && !isListening) {
            window.dispatchEvent(new CustomEvent('startVoiceRecording'));
        } else if (!@this.isRecording && isListening) {
            window.dispatchEvent(new CustomEvent('stopVoiceRecording'));
        }
    });
});
</script>
