@extends('master')

@section('title', 'Create Note')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.badge-soft{padding:0.35rem 0.6rem;border-radius:99px;font-size:0.70rem;font-weight:700;border:1px solid transparent}
.note-label{font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;margin-bottom:0.35rem;text-transform:uppercase}
.note-input,.note-select{border:1px solid #e2e8f0;border-radius:10px;padding:0.6rem 0.9rem;font-size:0.92rem;color:#0f172a;background:#f8fafc;transition:all .2s}
.note-input:focus,.note-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.12);background:#fff;outline:none}
.note-input::placeholder{color:#94a3b8}
.note-type-option{border:1px solid #e2e8f0;background:#f8fafc;color:#475569;font-weight:700;font-size:0.88rem;padding:1rem;border-radius:12px;transition:all .2s;cursor:pointer;text-align:center}
.note-type-option:hover{border-color:#3b82f6;color:#1d4ed8;background:#eff6ff}
.note-type-option small{font-size:0.76rem;font-weight:500;color:#64748b}
.btn-check:checked + .note-type-option{background:#1e293b!important;border-color:#1e293b!important;color:#fff!important;box-shadow:0 4px 12px rgba(15,23,42,0.15)}
.btn-check:checked + .note-type-option small{color:rgba(255,255,255,0.75)!important}
.voice-recorder-container{border:1px dashed #cbd5e1;border-radius:12px;padding:1.5rem;background:#f8fafc}
.recorder-controls .btn{min-width:130px;border-radius:10px;font-weight:600}
.recording-active{border-color:#ef4444!important;background:#fef2f2!important}
.recording-pulse{animation:pulse 1.5s infinite}
@keyframes pulse{0%{opacity:1}50%{opacity:0.5}100%{opacity:1}}
.transcription-enhanced{border-left:3px solid #10b981;background:#f0fdf4}
.transcription-processing{border-left:3px solid #3b82f6;background:#eff6ff}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-plus-circle me-2"></i>Create New Note</h2>
                    <p>Add a text note or record a voice note · auto-transcribed · linked to patient</p>
                </div>
                <a href="{{ route('doctor.notes.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back to Notes</a>
            </div>
        </div>

        <form id="noteForm">
            @csrf

            <!-- Note Type -->
            <div class="table-card">
                <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-pen-nib"></i></div><div><h5>Note Type</h5><p>Choose how you'd like to create this note</p></div></div></div>
                <div class="d-flex flex-column flex-md-row gap-3">
                    <input type="radio" class="btn-check" name="note_type" id="text_note" value="text" checked>
                    <label class="btn note-type-option flex-grow-1" for="text_note">
                        <i class="fas fa-file-lines me-2"></i>Text Note
                        <small class="d-block mt-1">Type your note manually</small>
                    </label>
                    <input type="radio" class="btn-check" name="note_type" id="voice_note" value="voice">
                    <label class="btn note-type-option flex-grow-1" for="voice_note">
                        <i class="fas fa-microphone me-2"></i>Voice Note
                        <small class="d-block mt-1">Record and auto-transcribe</small>
                    </label>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="table-card">
                <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-clipboard-list"></i></div><div><h5>Basic Information</h5><p>Optional details · title · date</p></div></div></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label note-label">Title</label>
                        <input type="text" class="form-control note-input" id="title" name="title" placeholder="Enter note title">
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_date" class="form-label note-label">Appointment Date</label>
                        <input type="date" class="form-control note-input" id="appointment_date" name="appointment_date">
                    </div>
                </div>
            </div>

            <!-- Patient & Appointment -->
            <div class="table-card">
                <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-user-injured"></i></div><div><h5>Patient & Appointment</h5><p>Link this note to a patient record</p></div></div></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label note-label">Patient</label>
                        <select class="form-select note-select" id="patient_id" name="patient_id">
                            <option value="">General Note (No specific patient)</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} - {{ $patient->email }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:0.76rem;color:#64748b">Leave empty for general notes</div>
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_id" class="form-label note-label">Related Appointment</label>
                        <select class="form-select note-select" id="appointment_id" name="appointment_id">
                            <option value="">No specific appointment</option>
                            @foreach($appointments as $appointment)
                                <option value="{{ $appointment->id }}">
                                    {{ $appointment->patient->name ?? 'Unknown Patient' }} - {{ $appointment->appointment_date->format('M j, Y g:i A') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Text Note -->
            <div id="textNoteSection" class="table-card">
                <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-file-alt"></i></div><div><h5>Note Content</h5><p>Write the medical note details</p></div></div></div>
                <textarea class="form-control note-input" id="note_text" name="note_text" rows="8" placeholder="Enter your note content here..."></textarea>
            </div>

            <!-- Voice Note -->
            <div id="voiceNoteSection" class="table-card" style="display:none">
                <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-microphone-lines"></i></div><div><h5>Voice Note</h5><p>Record audio and get a formatted transcription</p></div></div></div>
                <div class="voice-recorder-container">
                    <div class="recorder-status mb-3">
                        <div id="recordingStatus" class="alert d-flex align-items-center gap-2" style="display:none!important;border-radius:10px;margin:0">
                            <i class="fas fa-microphone-alt"></i>
                            <span id="statusText">Ready to record</span>
                            <span id="recordingTimer" class="ms-auto" style="font-family:ui-monospace;font-weight:700"></span>
                        </div>
                    </div>
                    <div class="recorder-controls text-center mb-3 d-flex flex-wrap justify-content-center gap-2">
                        <button type="button" id="startRecording" class="btn btn-success"><i class="fas fa-microphone me-2"></i>Start Recording</button>
                        <button type="button" id="stopRecording" class="btn btn-danger" style="display:none"><i class="fas fa-stop me-2"></i>Stop Recording</button>
                        <button type="button" id="playRecording" class="btn text-white" style="display:none;background:#0ea5e9;border-color:#0ea5e9"><i class="fas fa-play me-2"></i>Play</button>
                        <button type="button" id="clearRecording" class="btn btn-outline-secondary" style="display:none"><i class="fas fa-trash me-2"></i>Clear</button>
                    </div>
                    <div id="audioPlayerContainer" style="display:none" class="mb-3">
                        <audio id="audioPlayer" controls class="w-100" style="border-radius:10px"></audio>
                    </div>
                    <div id="transcriptionSection" style="display:none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label note-label mb-0">Transcription</label>
                            <button type="button" id="transcribeBtn" class="btn btn-sm" style="background:#1e293b;color:#fff;border-radius:8px;font-weight:600"><i class="fas fa-language me-1"></i>Transcribe & Format</button>
                        </div>
                        <div id="transcriptionLoading" class="text-center py-3" style="display:none">
                            <div class="spinner-border" style="color:#1e293b" role="status"><span class="visually-hidden">Processing...</span></div>
                            <div class="mt-2 fw-bold" style="color:#1e293b">Processing audio transcription...</div>
                            <small style="color:#64748b">Auto-detecting language and formatting medical content</small>
                        </div>
                        <textarea class="form-control note-input" id="transcript" name="transcript" rows="8" placeholder="Formatted medical transcription will appear here with organized sections and bullet points..."></textarea>
                        <div class="form-text" style="font-size:0.76rem;color:#64748b"><i class="fas fa-info-circle me-1"></i>The transcription will be automatically formatted with medical sections and preserve the original language. You can edit it before saving.</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="table-card">
                <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Actions</h5><p>Save or cancel</p></div></div></div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('doctor.notes.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600">Cancel</a>
                    <button type="submit" class="btn" style="background:#1e293b;color:#fff;border-radius:10px;padding:0.6rem 1.4rem;font-weight:700" id="saveBtn"><i class="fas fa-save me-2"></i>Save Note</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

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
            this.mediaRecorder.ondataavailable = (event) => { this.audioChunks.push(event.data); };
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
            console.error('Error starting recording:', error);
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
    playRecording() { if (this.audioPlayer.src) this.audioPlayer.play(); }
    clearRecording() {
        this.audioChunks = [];
        this.audioBlob = null;
        if (this.audioUrl) { URL.revokeObjectURL(this.audioUrl); this.audioUrl = null; }
        this.audioPlayer.src = '';
        this.transcriptTextarea.value = '';
        this.transcriptTextarea.className = 'form-control note-input';
        this.hideAudioPlayer();
        this.hideTranscriptionSection();
        this.updateUI();
    }
    async transcribeAudio() {
        if (!this.audioBlob) { alert('No audio recording found'); return; }
        this.showTranscriptionLoading();
        try {
            const reader = new FileReader();
            reader.onload = async () => {
                const base64Audio = reader.result;
                if (!base64Audio || base64Audio.length < 100) { alert('Invalid audio data'); return; }
                const response = await fetch('{{ route("doctor.notes.transcribe-audio") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify({ audio_file: base64Audio })
                });
                const data = await response.json();
                if (data.success) {
                    this.transcriptTextarea.value = data.transcript;
                    document.getElementById('note_text').value = data.transcript;
                    this.showTranscriptionSuccess();
                } else { console.error('Transcription failed:', data); alert('Transcription failed: ' + (data.message || 'Unknown error')); }
            };
            reader.readAsDataURL(this.audioBlob);
        } catch (error) { console.error('Transcription error:', error); alert('Transcription failed: ' + error.message); }
        finally { this.hideTranscriptionLoading(); }
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
    stopTimer() { if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; } }
    updateUI() {
        if (this.isRecording) {
            this.startBtn.style.display = 'none';
            this.stopBtn.style.display = 'inline-block';
            this.playBtn.style.display = 'none';
            this.clearBtn.style.display = 'none';
            this.statusDiv.style.display = 'flex';
            this.statusText.textContent = 'Recording...';
            this.statusDiv.className = 'alert alert-danger d-flex align-items-center gap-2 recording-pulse';
            this.recorderContainer.classList.add('recording-active');
        } else {
            this.startBtn.style.display = this.audioBlob ? 'none' : 'inline-block';
            this.stopBtn.style.display = 'none';
            this.playBtn.style.display = this.audioBlob ? 'inline-block' : 'none';
            this.clearBtn.style.display = this.audioBlob ? 'inline-block' : 'none';
            if (this.audioBlob) {
                this.statusDiv.style.display = 'flex';
                this.statusText.textContent = 'Recording completed';
                this.statusDiv.className = 'alert alert-success d-flex align-items-center gap-2';
            } else { this.statusDiv.style.display = 'none'; }
            this.recorderContainer.classList.remove('recording-active');
        }
    }
    showAudioPlayer() { this.audioPlayerContainer.style.display = 'block'; }
    hideAudioPlayer() { this.audioPlayerContainer.style.display = 'none'; }
    showTranscriptionSection() { this.transcriptionSection.style.display = 'block'; }
    hideTranscriptionSection() { this.transcriptionSection.style.display = 'none'; }
    showTranscriptionLoading() { this.transcriptionLoading.style.display = 'block'; this.transcribeBtn.disabled = true; this.transcriptTextarea.className = 'form-control note-input transcription-processing'; }
    hideTranscriptionLoading() { this.transcriptionLoading.style.display = 'none'; this.transcribeBtn.disabled = false; this.transcriptTextarea.className = 'form-control note-input transcription-enhanced'; }
    getAudioData() {
        if (this.audioBlob) { return new Promise((resolve) => { const reader = new FileReader(); reader.onload = () => resolve(reader.result); reader.readAsDataURL(this.audioBlob); }); }
        return null;
    }
    showTranscriptionSuccess() {
        this.hideTranscriptionLoading();
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
        successDiv.style.borderRadius='10px';
        successDiv.innerHTML = `<i class="fas fa-check-circle me-2"></i><strong>Success!</strong> Audio transcribed and formatted with medical structure.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        const labelDiv = this.transcriptionSection.querySelector('.d-flex');
        labelDiv.parentNode.insertBefore(successDiv, labelDiv.nextSibling);
        setTimeout(() => { if (successDiv.parentNode) successDiv.remove(); }, 5000);
    }
}
let voiceRecorder;
document.addEventListener('DOMContentLoaded', function() {
    voiceRecorder = new VoiceRecorder();
    const textNoteRadio = document.getElementById('text_note');
    const voiceNoteRadio = document.getElementById('voice_note');
    const textNoteSection = document.getElementById('textNoteSection');
    const voiceNoteSection = document.getElementById('voiceNoteSection');
    function switchNoteType() {
        if (voiceNoteRadio.checked) { textNoteSection.style.display = 'none'; voiceNoteSection.style.display = 'block'; }
        else { textNoteSection.style.display = 'block'; voiceNoteSection.style.display = 'none'; voiceRecorder.clearRecording(); }
    }
    textNoteRadio.addEventListener('change', switchNoteType);
    voiceNoteRadio.addEventListener('change', switchNoteType);
    document.getElementById('noteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const noteType = formData.get('note_type');
        if (noteType === 'text') { const noteText = formData.get('note_text'); if (!noteText || !noteText.trim()) { alert('Please enter note content'); return; } }
        else if (noteType === 'voice') { const transcript = formData.get('transcript'); if (!transcript || !transcript.trim()) { alert('Please record audio and transcribe it first'); return; } const audioData = await voiceRecorder.getAudioData(); if (audioData) { if (audioData.length < 100) { alert('Invalid audio data'); return; } formData.append('audio_file', audioData); } }
        const jsonData = {};
        for (let [key, value] of formData.entries()) { if (key !== 'audio_file') jsonData[key] = value; }
        if (formData.has('audio_file')) jsonData.audio_file = formData.get('audio_file');
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        saveBtn.disabled = true;
        try {
            const response = await fetch('{{ route("doctor.notes.store") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: JSON.stringify(jsonData) });
            const data = await response.json();
            if (data.success) window.location.href = '{{ route("doctor.notes.index") }}';
            else { console.error('Error saving note:', data); alert('Error saving note: ' + (data.message || 'Unknown error')); }
        } catch (error) { console.error('Error:', error); alert('Error saving note: ' + error.message); }
        finally { saveBtn.innerHTML = originalText; saveBtn.disabled = false; }
    });
});
</script>
@endpush
