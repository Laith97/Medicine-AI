                <!-- Diagnosis Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="diagnosis-section" class="table-card" style="@if($errors->has('diagnosis_text') || $errors->has('voice_files') || $errors->any()) display: block; @else display: none; @endif">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 fw-bold text-warning">
                                <i class="fas fa-stethoscope me-2"></i>Create Diagnosis
                            </h4>
                            <p class="mb-0 text-muted small">Document medical findings and diagnosis for this appointment</p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleDiagnosisForm()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>

                    <!-- Show validation errors if any -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Context Information -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Appointment Context:</strong> {{ $appointment->patient_name }} - {{ $appointment->reason }}
                        @if($appointment->doctor_notes)
                        <br><small class="text-muted"><strong>Doctor Notes:</strong> {{ Str::limit($appointment->doctor_notes, 100) }}</small>
                        @endif
                    </div>

                    <form id="diagnosisForm" method="POST" action="{{ route('doctor.appointments.create-diagnosis', $appointment) }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Diagnosis Input Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title">
                                    <i class="fas fa-stethoscope me-2"></i>Diagnosis Details
                                </h6>
                                <span class="form-section-badge bg-warning text-dark">Required</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="diagnosis_text" class="form-label fw-semibold">
                                        Diagnosis Text <span class="text-danger">*</span>
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Enter your medical diagnosis, findings, and treatment plan"></i>
                                    </label>
                                    <textarea class="form-control" id="diagnosis_text" name="diagnosis_text" rows="6" placeholder="Enter your medical diagnosis, clinical findings, and treatment recommendations..." required></textarea>
                                    <div class="form-text">
                                        Include symptoms assessment, clinical findings, diagnosis, and treatment recommendations.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Voice Recording Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title">
                                    <i class="fas fa-microphone me-2"></i>Voice Recording (Optional)
                                </h6>
                                <span class="form-section-badge bg-info">Optional</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="voice-recording-container">
                                        <button type="button" id="startRecording" class="btn btn-outline-primary">
                                            <i class="fas fa-microphone me-2"></i>Start Voice Recording
                                        </button>
                                        <button type="button" id="stopRecording" class="btn btn-outline-danger" style="display: none;">
                                            <i class="fas fa-stop me-2"></i>Stop Recording
                                        </button>
                                        <button type="button" id="playRecording" class="btn btn-outline-success" style="display: none;">
                                            <i class="fas fa-play me-2"></i>Play Back
                                        </button>
                                        <span id="recordingStatus" class="ms-3 text-muted"></span>
                                        <audio id="audioPlayback" controls style="display: none; max-width: 300px;"></audio>
                                    </div>
                                    <input type="file" id="voice_files" name="voice_files[]" multiple accept="audio/*" style="display: none;">
                                    <div class="form-text">
                                        Alternatively, you can upload audio files directly.
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="document.getElementById('voice_files').click()">
                                            Upload Files
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Data Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title">
                                    <i class="fas fa-user-md me-2"></i>Additional Patient Information
                                </h6>
                                <span class="form-section-badge bg-info">Optional</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="patient_data_height" class="form-label fw-semibold">
                                        Height (cm)
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Patient's height in centimeters"></i>
                                    </label>
                                    <input type="number" class="form-control" id="patient_data_height" name="patient_data[height]" placeholder="170">
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_data_weight" class="form-label fw-semibold">
                                        Weight (kg)
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Patient's weight in kilograms"></i>
                                    </label>
                                    <input type="number" step="0.1" class="form-control" id="patient_data_weight" name="patient_data[weight]" placeholder="70.5">
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_data_blood_pressure" class="form-label fw-semibold">
                                        Blood Pressure
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Systolic/Diastolic (e.g., 120/80)"></i>
                                    </label>
                                    <input type="text" class="form-control" id="patient_data_blood_pressure" name="patient_data[blood_pressure]" placeholder="120/80">
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_data_temperature" class="form-label fw-semibold">
                                        Temperature (°C)
                                        <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Body temperature in Celsius"></i>
                                    </label>
                                    <input type="number" step="0.1" class="form-control" id="patient_data_temperature" name="patient_data[temperature]" placeholder="36.6">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning btn-lg fw-semibold" onclick="submitDiagnosisForm()">
                                    <i class="fas fa-save me-2"></i>Create Diagnosis
                                </button>
                                <button type="button" class="btn btn-secondary fw-semibold" onclick="toggleDiagnosisForm()">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-shield-alt me-1"></i>
                                Diagnosis will be saved and patient will be notified
                            </div>
                        </div>
                    </form>
                </div>
                @endif

