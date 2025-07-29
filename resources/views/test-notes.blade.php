<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Voice Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-microphone me-2"></i>Voice Notes Test</h3>
                    </div>
                    <div class="card-body">
                        <!-- Recording Controls -->
                        <div class="mb-4">
                            <h5>Voice Recording</h5>
                            <div class="d-flex gap-2 mb-3">
                                <button id="startRecording" class="btn btn-success">
                                    <i class="fas fa-microphone me-2"></i>Start Recording
                                </button>
                                <button id="stopRecording" class="btn btn-danger" style="display: none;">
                                    <i class="fas fa-stop me-2"></i>Stop Recording
                                </button>
                                <button id="playRecording" class="btn btn-info" style="display: none;">
                                    <i class="fas fa-play me-2"></i>Play
                                </button>
                                <button id="transcribeBtn" class="btn btn-primary" style="display: none;">
                                    <i class="fas fa-language me-2"></i>Transcribe
                                </button>
                            </div>

                            <div id="recordingStatus" class="alert alert-info" style="display: none;">
                                <span id="statusText">Ready to record</span>
                                <span id="recordingTimer" class="ms-2"></span>
                            </div>

                            <audio id="audioPlayer" controls class="w-100 mb-3" style="display: none;"></audio>
                        </div>

                        <!-- Transcription -->
                        <div class="mb-4">
                            <h5>Transcription</h5>
                            <div id="transcriptionLoading" class="text-center py-3" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Transcribing...</span>
                                </div>
                                <div class="mt-2">Transcribing audio...</div>
                            </div>
                            <textarea id="transcript" class="form-control" rows="6" placeholder="Transcription will appear here..."></textarea>
                        </div>

                        <!-- Test Results -->
                        <div class="mb-4">
                            <h5>Test Results</h5>
                            <div id="testResults" class="alert alert-secondary">
                                No tests run yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class VoiceRecorderTest {
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
                this.logTest('Voice Recorder Test initialized');
            }

            initializeElements() {
                this.startBtn = document.getElementById('startRecording');
                this.stopBtn = document.getElementById('stopRecording');
                this.playBtn = document.getElementById('playRecording');
                this.transcribeBtn = document.getElementById('transcribeBtn');
                this.statusDiv = document.getElementById('recordingStatus');
                this.statusText = document.getElementById('statusText');
                this.timerSpan = document.getElementById('recordingTimer');
                this.audioPlayer = document.getElementById('audioPlayer');
                this.transcriptionLoading = document.getElementById('transcriptionLoading');
                this.transcriptTextarea = document.getElementById('transcript');
                this.testResults = document.getElementById('testResults');
            }

            bindEvents() {
                this.startBtn.addEventListener('click', () => this.startRecording());
                this.stopBtn.addEventListener('click', () => this.stopRecording());
                this.playBtn.addEventListener('click', () => this.playRecording());
                this.transcribeBtn.addEventListener('click', () => this.transcribeAudio());
            }

            logTest(message) {
                console.log(message);
                this.testResults.innerHTML += `<div>${new Date().toLocaleTimeString()}: ${message}</div>`;
            }

            async startRecording() {
                try {
                    this.logTest('Requesting microphone access...');
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.logTest('Microphone access granted');

                    this.mediaRecorder = new MediaRecorder(stream);
                    this.audioChunks = [];

                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                        this.logTest(`Audio chunk received: ${event.data.size} bytes`);
                    };

                    this.mediaRecorder.onstop = () => {
                        this.audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);
                        this.audioPlayer.src = this.audioUrl;
                        this.audioPlayer.style.display = 'block';
                        this.transcribeBtn.style.display = 'inline-block';
                        this.logTest(`Recording completed: ${this.audioBlob.size} bytes`);
                    };

                    this.mediaRecorder.start();
                    this.isRecording = true;
                    this.recordingStartTime = Date.now();
                    this.startTimer();
                    this.updateUI();
                    this.logTest('Recording started');

                } catch (error) {
                    this.logTest(`Error starting recording: ${error.message}`);
                    console.error('Error starting recording:', error);
                }
            }

            stopRecording() {
                if (this.mediaRecorder && this.isRecording) {
                    this.mediaRecorder.stop();
                    this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                    this.isRecording = false;
                    this.stopTimer();
                    this.updateUI();
                    this.logTest('Recording stopped');
                }
            }

            playRecording() {
                if (this.audioPlayer.src) {
                    this.audioPlayer.play();
                    this.logTest('Playing recording');
                }
            }

            async transcribeAudio() {
                if (!this.audioBlob) {
                    this.logTest('No audio recording found');
                    return;
                }

                this.showTranscriptionLoading();
                this.logTest('Starting transcription...');

                try {
                    const reader = new FileReader();
                    reader.onload = async () => {
                        const base64Audio = reader.result;
                        this.logTest(`Audio converted to base64: ${base64Audio.length} characters`);

                        const response = await fetch('/doctor/notes/transcribe-audio', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                audio_file: base64Audio
                            })
                        });

                        this.logTest(`API Response: ${response.status} ${response.statusText}`);

                        const data = await response.json();

                        if (data.success) {
                            this.transcriptTextarea.value = data.transcript;
                            this.logTest(`Transcription successful: "${data.transcript.substring(0, 50)}..."`);
                        } else {
                            this.logTest(`Transcription failed: ${data.message || 'Unknown error'}`);
                        }
                    };

                    reader.readAsDataURL(this.audioBlob);

                } catch (error) {
                    this.logTest(`Transcription error: ${error.message}`);
                    console.error('Transcription error:', error);
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
                    this.statusDiv.style.display = 'block';
                    this.statusText.textContent = 'Recording...';
                    this.statusDiv.className = 'alert alert-danger';
                } else {
                    this.startBtn.style.display = this.audioBlob ? 'none' : 'inline-block';
                    this.stopBtn.style.display = 'none';
                    this.playBtn.style.display = this.audioBlob ? 'inline-block' : 'none';

                    if (this.audioBlob) {
                        this.statusDiv.style.display = 'block';
                        this.statusText.textContent = 'Recording completed';
                        this.statusDiv.className = 'alert alert-success';
                    } else {
                        this.statusDiv.style.display = 'none';
                    }
                }
            }

            showTranscriptionLoading() {
                this.transcriptionLoading.style.display = 'block';
                this.transcribeBtn.disabled = true;
            }

            hideTranscriptionLoading() {
                this.transcriptionLoading.style.display = 'none';
                this.transcribeBtn.disabled = false;
            }
        }

        // Initialize the test when page loads
        document.addEventListener('DOMContentLoaded', function() {
            new VoiceRecorderTest();
        });
    </script>
</body>
</html>
