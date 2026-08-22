@if(auth()->check() && auth()->user()->isDoctor())
<div class="doctor-card">
    <div class="doctor-card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-stethoscope me-2 text-warning"></i>Create Diagnosis</h5>
        <span class="doctor-badge doctor-badge-warning">Required</span>
    </div>
    <div class="doctor-card-body">
        @if($errors->any())
            <div class="alert alert-danger d-flex gap-2"><i class="fas fa-exclamation-circle mt-1"></i><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-info d-flex gap-2 small" style="background:var(--bg-secondary); border:1px solid var(--border-light);">
            <i class="fas fa-info-circle text-primary mt-1"></i>
            <div><strong>{{ $appointment->patient_name }}</strong> — {{ Str::limit($appointment->reason, 90) }} @if($appointment->doctor_notes)<br><span class="text-muted">Notes: {{ Str::limit($appointment->doctor_notes, 80) }}</span>@endif</div>
        </div>

        <form id="diagnosisForm" method="POST" action="{{ route('doctor.appointments.create-diagnosis', $appointment) }}" enctype="multipart/form-data">
            @csrf

            <div class="doctor-form-section mb-4">
                <h6 class="fw-semibold mb-3"><i class="fas fa-file-medical me-2 text-primary"></i>Diagnosis Details <span class="text-danger">*</span></h6>
                <label class="doctor-form-label">Clinical findings & treatment plan</label>
                <textarea class="doctor-form-control form-control" id="diagnosis_text" name="diagnosis_text" rows="6" placeholder="Symptoms, examination, diagnosis, plan..." required></textarea>
                <small class="text-muted">Include symptoms, findings, diagnosis, recommendations.</small>
            </div>

            <div class="doctor-form-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-microphone me-2 text-primary"></i>Voice Recording</h6>
                    <span class="doctor-badge doctor-badge-info">Optional</span>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center p-3 rounded" style="background:var(--gray-50); border:1px dashed var(--border-medium);">
                    <button type="button" id="startRecording" class="doctor-btn doctor-btn-outline doctor-btn-sm"><i class="fas fa-microphone me-1"></i>Start</button>
                    <button type="button" id="stopRecording" class="doctor-btn doctor-btn-danger doctor-btn-sm" style="display:none;"><i class="fas fa-stop me-1"></i>Stop</button>
                    <button type="button" id="playRecording" class="doctor-btn doctor-btn-success doctor-btn-sm" style="display:none;"><i class="fas fa-play me-1"></i>Play</button>
                    <span id="recordingStatus" class="small text-muted"></span>
                    <audio id="audioPlayback" controls style="display:none; max-width:260px;"></audio>
                    <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm ms-auto" onclick="document.getElementById('voice_files').click()"><i class="fas fa-upload me-1"></i>Upload</button>
                    <input type="file" id="voice_files" name="voice_files[]" multiple accept="audio/*" style="display:none;">
                </div>
            </div>

            <div class="doctor-form-section mb-4">
                <h6 class="fw-semibold mb-3"><i class="fas fa-heartbeat me-2 text-primary"></i>Vitals <span class="doctor-badge doctor-badge-info ms-2">Optional</span></h6>
                <div class="row g-3">
                    <div class="col-6 col-md-3"><label class="doctor-form-label">Height (cm)</label><input type="number" class="doctor-form-control form-control" name="patient_data[height]" placeholder="170"></div>
                    <div class="col-6 col-md-3"><label class="doctor-form-label">Weight (kg)</label><input type="number" step="0.1" class="doctor-form-control form-control" name="patient_data[weight]" placeholder="70.5"></div>
                    <div class="col-6 col-md-3"><label class="doctor-form-label">BP</label><input type="text" class="doctor-form-control form-control" name="patient_data[blood_pressure]" placeholder="120/80"></div>
                    <div class="col-6 col-md-3"><label class="doctor-form-label">Temp (°C)</label><input type="number" step="0.1" class="doctor-form-control form-control" name="patient_data[temperature]" placeholder="36.6"></div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end pt-3 border-top">
                <button type="button" class="doctor-btn doctor-btn-outline" onclick="toggleDiagnosisForm()"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="doctor-btn doctor-btn-primary" onclick="submitDiagnosisForm()"><i class="fas fa-save me-1"></i>Create Diagnosis</button>
            </div>
            <small class="text-muted d-block text-end mt-2"><i class="fas fa-shield-alt me-1"></i>Patient will be notified</small>
        </form>
    </div>
</div>
@endif
