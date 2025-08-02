@extends('master')

@section('title', 'Create Manual Diagnosis')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9">
            <!-- Page Header -->
            <div class="page-header text-center text-md-start mb-4">
                <h2><i class="fas fa-stethoscope me-2"></i>Create Manual Diagnosis</h2>
                <p>Provide a diagnosis for your patient with text or voice input</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('diagnosis.store') }}" method="POST" enctype="multipart/form-data" id="diagnosisForm">
                @csrf

                <!-- Patient Information Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Patient Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Patient Selection -->
                        <div class="mb-4">
                            <label for="existing_patient" class="form-label">Select Existing Patient</label>
                            <select class="form-select" id="existing_patient" name="existing_patient">
                                <option value="">-- Select from your patients or add new --</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}"
                                            data-name="{{ $patient->name }}"
                                            data-email="{{ $patient->email }}"
                                            data-phone="{{ $patient->phone }}"
                                            data-age="{{ $patient->age }}"
                                            data-gender="{{ $patient->gender }}">
                                        {{ $patient->name }} ({{ $patient->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                @if($patients->count() > 0)
                                    You have {{ $patients->count() }} patient(s). Select one or add a new patient below.
                                @else
                                    You don't have any patients yet. Add a new patient below.
                                @endif
                            </div>
                        </div>

                        <!-- New Patient Form (shown by default, hidden when existing patient selected) -->
                        <div id="new_patient_form">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Adding New Patient:</strong> Fill in the details below to create a new patient account.
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="patient_name" class="form-label">Patient Name *</label>
                                    <input type="text" class="form-control" id="patient_name" name="patient_name"
                                           value="{{ old('patient_name') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="patient_email" class="form-label">Patient Email *</label>
                                    <input type="email" class="form-control" id="patient_email" name="patient_email"
                                           value="{{ old('patient_email') }}" required>
                                    <div class="form-text">A new account will be created and assigned to you.</div>
                                </div>
                            </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="patient_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="patient_phone" name="patient_phone"
                                       value="{{ old('patient_phone') }}">
                                <div class="form-text">Optional - for SMS notifications</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="patient_age" class="form-label">Age *</label>
                                <input type="number" class="form-control" id="patient_age" name="patient_age"
                                       value="{{ old('patient_age') }}" min="1" max="150" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="patient_gender" class="form-label">Gender *</label>
                                <select class="form-select" id="patient_gender" name="patient_gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('patient_gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('patient_gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('patient_gender') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnosis Input Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Diagnosis Input</h5>
                    </div>
                    <div class="card-body">
                        <!-- Input Method Selection -->
                        <div class="mb-4">
                            <label class="form-label">Input Method</label>
                            <div class="btn-group w-100" role="group" aria-label="Input method">
                                <input type="radio" class="btn-check" name="input_method" id="text_input" value="text" checked>
                                <label class="btn btn-outline-primary" for="text_input">
                                    <i class="fas fa-keyboard me-2"></i>Text Input
                                </label>

                                <input type="radio" class="btn-check" name="input_method" id="voice_input" value="voice">
                                <label class="btn btn-outline-primary" for="voice_input">
                                    <i class="fas fa-microphone me-2"></i>Voice Input
                                </label>

                                <input type="radio" class="btn-check" name="input_method" id="both_input" value="both">
                                <label class="btn btn-outline-primary" for="both_input">
                                    <i class="fas fa-plus me-2"></i>Both
                                </label>
                            </div>
                        </div>

                        <!-- Text Input -->
                        <div id="text_input_section" class="mb-4">
                            <label for="diagnosis_text" class="form-label">Diagnosis Text</label>
                            <textarea class="form-control" id="diagnosis_text" name="diagnosis_text" rows="8"
                                      placeholder="Enter your diagnosis here...">{{ old('diagnosis_text') }}</textarea>
                            <div class="form-text">Provide detailed diagnosis, treatment recommendations, and any follow-up instructions.</div>
                        </div>

                        <!-- Voice Input -->
                        <div id="voice_input_section" class="mb-4" style="display: none;">
                            <label for="voice_file" class="form-label">Voice Recording</label>
                            <div class="voice-input-container">
                                <div class="voice-recorder mb-3">
                                    <button type="button" id="recordBtn" class="btn btn-danger">
                                        <i class="fas fa-microphone me-2"></i>Start Recording
                                    </button>
                                    <button type="button" id="stopBtn" class="btn btn-secondary" disabled>
                                        <i class="fas fa-stop me-2"></i>Stop Recording
                                    </button>
                                    <button type="button" id="playBtn" class="btn btn-info" disabled>
                                        <i class="fas fa-play me-2"></i>Play
                                    </button>
                                    <span id="recordingStatus" class="ms-3 text-muted"></span>
                                </div>

                                <div class="file-upload-section">
                                    <label class="form-label">Or Upload Audio File</label>
                                    <input type="file" class="form-control" id="voice_file" name="voice_file"
                                           accept=".mp3,.wav,.m4a,.ogg" />
                                    <div class="form-text">Supported formats: MP3, WAV, M4A, OGG (Max: 10MB)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Data (Optional) -->
                        <div class="mb-4">
                            <label class="form-label">Additional Patient Data (Optional)</label>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="symptoms" class="form-label">Symptoms</label>
                                    <textarea class="form-control" id="symptoms" name="patient_data[symptoms]" rows="3"
                                              placeholder="List patient symptoms...">{{ old('patient_data.symptoms') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="medical_history" class="form-label">Medical History</label>
                                    <textarea class="form-control" id="medical_history" name="patient_data[medical_history]" rows="3"
                                              placeholder="Relevant medical history...">{{ old('patient_data.medical_history') }}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="medications" class="form-label">Current Medications</label>
                                    <textarea class="form-control" id="medications" name="patient_data[medications]" rows="2"
                                              placeholder="Current medications...">{{ old('patient_data.medications') }}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="allergies" class="form-label">Allergies</label>
                                    <textarea class="form-control" id="allergies" name="patient_data[allergies]" rows="2"
                                              placeholder="Known allergies...">{{ old('patient_data.allergies') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-save me-2"></i>Create Diagnosis
                        </button>
                        <a href="{{ route('diagnosis.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.voice-input-container {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border: 2px dashed #dee2e6;
}

.voice-recorder {
    text-align: center;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    margin-bottom: 15px;
}

.recording {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.card-header {
    background-color: #DE6262;
    color: white;
    border-bottom: none;
}

.btn-outline-primary:checked + label,
.btn-outline-primary.active {
    background-color: #DE6262;
    border-color: #DE6262;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textInputRadio = document.getElementById('text_input');
    const voiceInputRadio = document.getElementById('voice_input');
    const bothInputRadio = document.getElementById('both_input');
    const textSection = document.getElementById('text_input_section');
    const voiceSection = document.getElementById('voice_input_section');
    const diagnosisTextarea = document.getElementById('diagnosis_text');
    const voiceFileInput = document.getElementById('voice_file');

    // Voice recording variables
    let mediaRecorder;
    let audioChunks = [];
    let recordedBlob;

    // Input method change handlers
    function updateInputSections() {
        if (textInputRadio.checked) {
            textSection.style.display = 'block';
            voiceSection.style.display = 'none';
            diagnosisTextarea.required = true;
            voiceFileInput.required = false;
        } else if (voiceInputRadio.checked) {
            textSection.style.display = 'none';
            voiceSection.style.display = 'block';
            diagnosisTextarea.required = false;
            voiceFileInput.required = true;
        } else if (bothInputRadio.checked) {
            textSection.style.display = 'block';
            voiceSection.style.display = 'block';
            diagnosisTextarea.required = false;
            voiceFileInput.required = false;
        }
    }

    textInputRadio.addEventListener('change', updateInputSections);
    voiceInputRadio.addEventListener('change', updateInputSections);
    bothInputRadio.addEventListener('change', updateInputSections);

    // Patient selection handling
    const existingPatientSelect = document.getElementById('existing_patient');
    const newPatientForm = document.getElementById('new_patient_form');
    const patientNameInput = document.getElementById('patient_name');
    const patientEmailInput = document.getElementById('patient_email');
    const patientPhoneInput = document.getElementById('patient_phone');
    const patientAgeInput = document.getElementById('patient_age');
    const patientGenderSelect = document.getElementById('patient_gender');

    existingPatientSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (this.value) {
            // Existing patient selected - hide form and populate hidden fields
            newPatientForm.style.display = 'none';

            // Populate form with selected patient data
            patientNameInput.value = selectedOption.dataset.name || '';
            patientEmailInput.value = selectedOption.dataset.email || '';
            patientPhoneInput.value = selectedOption.dataset.phone || '';
            patientAgeInput.value = selectedOption.dataset.age || '';
            patientGenderSelect.value = selectedOption.dataset.gender || '';

            // Make fields readonly
            patientNameInput.readOnly = true;
            patientEmailInput.readOnly = true;
            patientPhoneInput.readOnly = true;
            patientAgeInput.readOnly = true;
            patientGenderSelect.disabled = true;

            // Remove required attributes since we're using existing patient
            patientNameInput.required = false;
            patientEmailInput.required = false;
            patientAgeInput.required = false;
            patientGenderSelect.required = false;

        } else {
            // No patient selected - show form for new patient
            newPatientForm.style.display = 'block';

            // Clear form
            patientNameInput.value = '';
            patientEmailInput.value = '';
            patientPhoneInput.value = '';
            patientAgeInput.value = '';
            patientGenderSelect.value = '';

            // Make fields editable
            patientNameInput.readOnly = false;
            patientEmailInput.readOnly = false;
            patientPhoneInput.readOnly = false;
            patientAgeInput.readOnly = false;
            patientGenderSelect.disabled = false;

            // Restore required attributes
            patientNameInput.required = true;
            patientEmailInput.required = true;
            patientAgeInput.required = true;
            patientGenderSelect.required = true;
        }
    });

    // Voice recording functionality
    const recordBtn = document.getElementById('recordBtn');
    const stopBtn = document.getElementById('stopBtn');
    const playBtn = document.getElementById('playBtn');
    const recordingStatus = document.getElementById('recordingStatus');

    recordBtn.addEventListener('click', startRecording);
    stopBtn.addEventListener('click', stopRecording);
    playBtn.addEventListener('click', playRecording);

    async function startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };

            mediaRecorder.onstop = () => {
                recordedBlob = new Blob(audioChunks, { type: 'audio/wav' });

                // Create a file from the blob and set it to the file input
                const file = new File([recordedBlob], 'recorded_diagnosis.wav', { type: 'audio/wav' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                voiceFileInput.files = dataTransfer.files;

                playBtn.disabled = false;
                recordingStatus.textContent = 'Recording saved';
                recordingStatus.className = 'ms-3 text-success';
            };

            mediaRecorder.start();
            recordBtn.disabled = true;
            stopBtn.disabled = false;
            recordBtn.classList.add('recording');
            recordingStatus.textContent = 'Recording...';
            recordingStatus.className = 'ms-3 text-danger';

        } catch (error) {
            console.error('Error accessing microphone:', error);
            alert('Error accessing microphone. Please check permissions.');
        }
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());

            recordBtn.disabled = false;
            stopBtn.disabled = true;
            recordBtn.classList.remove('recording');
        }
    }

    function playRecording() {
        if (recordedBlob) {
            const audio = new Audio(URL.createObjectURL(recordedBlob));
            audio.play();
        }
    }

    // Form validation
    document.getElementById('diagnosisForm').addEventListener('submit', function(e) {
        const textInput = diagnosisTextarea.value.trim();
        const voiceFile = voiceFileInput.files.length > 0;

        if (!textInput && !voiceFile) {
            e.preventDefault();
            alert('Please provide either text diagnosis or voice recording.');
            return false;
        }
    });

    // Initialize
    updateInputSections();
});
</script>
@endsection
