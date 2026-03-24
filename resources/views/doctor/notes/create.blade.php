@extends('master')

@section('title', 'Create Note')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Create New Note</h2>
                    <p>Add a text note or record a voice note</p>
                </div>
                <a href="{{ route('doctor.notes.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Notes
                </a>
            </div>
        </div>

        <!-- Note Form -->
        <div class="table-card">
            <form id="noteForm">
                @csrf

                <!-- Note Type Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Note Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="note_type" id="text_note" value="text" checked>
                            <label class="btn btn-outline-primary" for="text_note">
                                <i class="fas fa-file-text me-2"></i>Text Note
                            </label>

                            <input type="radio" class="btn-check" name="note_type" id="voice_note" value="voice">
                            <label class="btn btn-outline-info" for="voice_note">
                                <i class="fas fa-microphone me-2"></i>Voice Note
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Title (Optional)</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Enter note title">
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_date" class="form-label">Appointment Date (Optional)</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date">
                    </div>
                </div>

                <!-- Patient Selection -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label">Patient (Optional)</label>
                        <select class="form-select" id="patient_id" name="patient_id">
                            <option value="">General Note (No specific patient)</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} - {{ $patient->email }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Leave empty for general notes</div>
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_id" class="form-label">Related Appointment (Optional)</label>
                        <select class="form-select" id="appointment_id" name="appointment_id">
                            <option value="">No specific appointment</option>
                            @foreach($appointments as $appointment)
                                <option value="{{ $appointment->id }}">
                                    {{ $appointment->patient->name ?? 'Unknown Patient' }} - {{ $appointment->appointment_date->format('M j, Y g:i A') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Text Note Section -->
                <div id="textNoteSection" class="mb-4">
                    <label for="note_text" class="form-label fw-bold">Note Content</label>
                    <textarea class="form-control" id="note_text" name="note_text" rows="8" placeholder="Enter your note content here..."></textarea>
                </div>

                <!-- Voice Note Section -->
                <div id="voiceNoteSection" class="mb-4" style="display: none;">
                    <label class="form-label fw-bold">Voice Note</label>

                    <!-- Recording Controls -->
                    <div class="voice-recorder-container">
                        <div class="recorder-status mb-3">
                            <div id="recordingStatus" class="alert alert-info" style="display: none;">
                                <i class="fas fa-microphone-alt me-2"></i>
                                <span id="statusText">Ready to record</span>
                                <span id="recordingTimer" class="ms-2"></span>
                            </div>
                        </div>

                        <div class="recorder-controls text-center mb-3">
                            <button type="button" id="startRecording" class="btn btn-success btn-lg me-2">
                                <i class="fas fa-microphone me-2"></i>Start Recording
                            </button>
                            <button type="button" id="stopRecording" class="btn btn-danger btn-lg me-2" style="display: none;">
                                <i class="fas fa-stop me-2"></i>Stop Recording
                            </button>
                            <button type="button" id="playRecording" class="btn btn-info btn-lg me-2" style="display: none;">
                                <i class="fas fa-play me-2"></i>Play
                            </button>
                            <button type="button" id="clearRecording" class="btn btn-warning btn-lg" style="display: none;">
                                <i class="fas fa-trash me-2"></i>Clear
                            </button>
                        </div>

                        <!-- Audio Player -->
                        <div id="audioPlayerContainer" style="display: none;" class="mb-3">
                            <audio id="audioPlayer" controls class="w-100"></audio>
                        </div>

                        <!-- Transcription Section -->
                        <div id="transcriptionSection" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">Transcription</label>
                                <button type="button" id="transcribeBtn" class="btn btn-sm btn-primary">
                                    <i class="fas fa-language me-1"></i>Transcribe & Format
                                </button>
                            </div>
                            <div id="transcriptionLoading" class="text-center py-3" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Processing...</span>
                                </div>
                                <div class="mt-2">
                                    <div class="fw-bold">Processing audio transcription...</div>
                                    <small class="text-muted">Auto-detecting language and formatting medical content</small>
                                </div>
                            </div>
                            <textarea class="form-control" id="transcript" name="transcript" rows="8" placeholder="Formatted medical transcription will appear here with organized sections and bullet points..."></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                The transcription will be automatically formatted with medical sections and preserve the original language. You can edit it before saving.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('doctor.notes.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary-custom" id="saveBtn">
                        <i class="fas fa-save me-2"></i>Save Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '📝';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Button styles within header */
.dashboard-header .btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    transition: all 0.3s ease;
}

.dashboard-header .btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}

.voice-recorder-container {
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem;
    background-color: #f8f9fa;
}

.recorder-controls .btn {
    min-width: 140px;
}

.recording-active {
    border-color: #dc3545 !important;
    background-color: #fff5f5 !important;
}

.recording-pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

#recordingTimer {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

.transcription-enhanced {
    border-left: 4px solid #28a745;
    background-color: #f8fff9;
}

.transcription-processing {
    border-left: 4px solid #007bff;
    background-color: #f8f9ff;
}
</style>
@endpush

@push('scripts')
<script>
class VoiceRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.audioBlob = null;
        this.audioUrl = null;
        this.isRecording = false;
        this.recordingStartTime = null;
        this.timerInterval = null;

        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        this.startBtn = document.getElementById('startRecording');
        this.stopBtn = document.getElementById('stopRecording');
        this.playBtn = document.getElementById('playRecording');
        this.clearBtn = document.getElementById('clearRecording');
        this.transcribeBtn = document.getElementById('transcribeBtn');
        this.statusDiv = document.getElementById('recordingStatus');
        this.statusText = document.getElementById('statusText');
        this.timerSpan = document.getElementById('recordingTimer');
        this.audioPlayer = document.getElementById('audioPlayer');
        this.audioPlayerContainer = document.getElementById('audioPlayerContainer');
        this.transcriptionSection = document.getElementById('transcriptionSection');
        this.transcriptionLoading = document.getElementById('transcriptionLoading');
        this.transcriptTextarea = document.getElementById('transcript');
        this.recorderContainer = document.querySelector('.voice-recorder-container');
    }

    bindEvents() {
        this.startBtn.addEventListener('click', () => this.startRecording());
        this.stopBtn.addEventListener('click', () => this.stopRecording());
        this.playBtn.addEventListener('click', () => this.playRecording());
        this.clearBtn.addEventListener('click', () => this.clearRecording());
        this.transcribeBtn.addEventListener('click', () => this.transcribeAudio());
    }

    async startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];

            this.mediaRecorder.ondataavailable = (event) => {
                this.audioChunks.push(event.data);
            };

            this.mediaRecorder.onstop = () => {
                this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                this.audioUrl = URL.createObjectURL(this.audioBlob);
                this.audioPlayer.src = this.audioUrl;
                this.showAudioPlayer();
                this.showTranscriptionSection();
            };

            this.mediaRecorder.start();
            this.isRecording = true;
            this.recordingStartTime = Date.now();
            this.startTimer();
            this.updateUI();

        } catch (error) {
            // console.error('Error starting recording:', error);
            alert('Error accessing microphone. Please check permissions.');
        }
    }

    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
            this.isRecording = false;
            this.stopTimer();
            this.updateUI();
        }
    }

    playRecording() {
        if (this.audioPlayer.src) {
            this.audioPlayer.play();
        }
    }

    clearRecording() {
        this.audioChunks = [];
        this.audioBlob = null;
        if (this.audioUrl) {
            URL.revokeObjectURL(this.audioUrl);
            this.audioUrl = null;
        }
        this.audioPlayer.src = '';
        this.transcriptTextarea.value = '';
        this.transcriptTextarea.className = 'form-control'; // Reset styling
        this.hideAudioPlayer();
        this.hideTranscriptionSection();
        this.updateUI();
    }

    async transcribeAudio() {
        if (!this.audioBlob) {
            alert('No audio recording found');
            return;
        }

        this.showTranscriptionLoading();

        try {
            const reader = new FileReader();
            reader.onload = async () => {
                const base64Audio = reader.result;

                // Validate base64 audio data
                if (!base64Audio || base64Audio.length < 100) {
                    alert('Invalid audio data');
                    return;
                }

                const response = await fetch('{{ route("doctor.notes.transcribe-audio") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        audio_file: base64Audio
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.transcriptTextarea.value = data.transcript;
                    document.getElementById('note_text').value = data.transcript;

                    // Show success message
                    this.showTranscriptionSuccess();
                } else {
                    // console.error('Transcription failed:', data);
                    alert('Transcription failed: ' + (data.message || 'Unknown error'));
                }
            };

            reader.readAsDataURL(this.audioBlob);

        } catch (error) {
            // console.error('Transcription error:', error);
            alert('Transcription failed: ' + error.message);
        } finally {
            this.hideTranscriptionLoading();
        }
    }

    startTimer() {
        this.timerInterval = setInterval(() => {
            const elapsed = Date.now() - this.recordingStartTime;
            const seconds = Math.floor(elapsed / 1000);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            this.timerSpan.textContent = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }, 1000);
    }

    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    updateUI() {
        if (this.isRecording) {
            this.startBtn.style.display = 'none';
            this.stopBtn.style.display = 'inline-block';
            this.playBtn.style.display = 'none';
            this.clearBtn.style.display = 'none';
            this.statusDiv.style.display = 'block';
            this.statusText.textContent = 'Recording...';
            this.statusDiv.className = 'alert alert-danger recording-pulse';
            this.recorderContainer.classList.add('recording-active');
        } else {
            this.startBtn.style.display = this.audioBlob ? 'none' : 'inline-block';
            this.stopBtn.style.display = 'none';
            this.playBtn.style.display = this.audioBlob ? 'inline-block' : 'none';
            this.clearBtn.style.display = this.audioBlob ? 'inline-block' : 'none';

            if (this.audioBlob) {
                this.statusDiv.style.display = 'block';
                this.statusText.textContent = 'Recording completed';
                this.statusDiv.className = 'alert alert-success';
            } else {
                this.statusDiv.style.display = 'none';
            }

            this.recorderContainer.classList.remove('recording-active');
        }
    }

    showAudioPlayer() {
        this.audioPlayerContainer.style.display = 'block';
    }

    hideAudioPlayer() {
        this.audioPlayerContainer.style.display = 'none';
    }

    showTranscriptionSection() {
        this.transcriptionSection.style.display = 'block';
    }

    hideTranscriptionSection() {
        this.transcriptionSection.style.display = 'none';
    }

    showTranscriptionLoading() {
        this.transcriptionLoading.style.display = 'block';
        this.transcribeBtn.disabled = true;
        this.transcriptTextarea.className = 'form-control transcription-processing';
    }

    hideTranscriptionLoading() {
        this.transcriptionLoading.style.display = 'none';
        this.transcribeBtn.disabled = false;
        this.transcriptTextarea.className = 'form-control transcription-enhanced';
    }

    getAudioData() {
        if (this.audioBlob) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.readAsDataURL(this.audioBlob);
            });
        }
        return null;
    }

    showTranscriptionSuccess() {
        // Hide loading indicator
        this.hideTranscriptionLoading();

        // Show a brief success message
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> Audio transcribed and formatted with medical structure.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert after the transcription section label
        const labelDiv = this.transcriptionSection.querySelector('.d-flex');
        labelDiv.parentNode.insertBefore(successDiv, labelDiv.nextSibling);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 5000);
    }
}

// Initialize recorder
let voiceRecorder;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize voice recorder
    voiceRecorder = new VoiceRecorder();

    // Handle note type switching
    const textNoteRadio = document.getElementById('text_note');
    const voiceNoteRadio = document.getElementById('voice_note');
    const textNoteSection = document.getElementById('textNoteSection');
    const voiceNoteSection = document.getElementById('voiceNoteSection');

    function switchNoteType() {
        if (voiceNoteRadio.checked) {
            textNoteSection.style.display = 'none';
            voiceNoteSection.style.display = 'block';
        } else {
            textNoteSection.style.display = 'block';
            voiceNoteSection.style.display = 'none';
            voiceRecorder.clearRecording();
        }
    }

    textNoteRadio.addEventListener('change', switchNoteType);
    voiceNoteRadio.addEventListener('change', switchNoteType);

    // Handle form submission
    document.getElementById('noteForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const noteType = formData.get('note_type');

        // Validate required fields
        if (noteType === 'text') {
            const noteText = formData.get('note_text');
            if (!noteText || !noteText.trim()) {
                alert('Please enter note content');
                return;
            }
        } else if (noteType === 'voice') {
            const transcript = formData.get('transcript');
            if (!transcript || !transcript.trim()) {
                alert('Please record audio and transcribe it first');
                return;
            }

            // Add audio data
            const audioData = await voiceRecorder.getAudioData();
            if (audioData) {
                // Validate audio data
                if (audioData.length < 100) {
                    alert('Invalid audio data');
                    return;
                }
                formData.append('audio_file', audioData);
            }
        }

        // Convert FormData to JSON
        const jsonData = {};
        for (let [key, value] of formData.entries()) {
            // Skip the audio_file if it's already been added as a blob
            if (key !== 'audio_file') {
                jsonData[key] = value;
            }
        }

        // Add audio data to JSON if it exists
        if (formData.has('audio_file')) {
            jsonData.audio_file = formData.get('audio_file');
        }

        // Submit form
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        saveBtn.disabled = true;

        try {
            const response = await fetch('{{ route("doctor.notes.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(jsonData)
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = '{{ route("doctor.notes.index") }}';
            } else {
                // console.error('Error saving note:', data);
                alert('Error saving note: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            // console.error('Error:', error);
            alert('Error saving note: ' + error.message);
        } finally {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });
});
</script>
@endpush
