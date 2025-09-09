<!-- resources/views/openai-form.blade.php -->
@extends('master')

@section('title', 'Patients Page')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11 col-xl-10">
            <!-- Page Header -->
            <div class="page-header text-center text-md-start">
                <h2><i class="fas fa-stethoscope me-2"></i>AI Medical Assistant</h2>
                <p>Enter patient information and get AI-powered medical recommendations</p>
            </div>

<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<!-- Include Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/styles/choices.min.css" />

<style>

</style>

            <!-- Main Form Container -->
            <div class="medical-form-container">
                <form id="openaiForm" action="{{ url('/openai/respond') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if(isset($patientToEdit))
                    <input type="hidden" name="edit_patient_id" value="{{ $patientToEdit->id }}">
                @endif

                <!-- Form Progress Indicator -->
                <div class="form-progress-container mb-4" style="padding: 1.5rem; background-color: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                    <div class="progress-steps d-flex justify-content-between" style="position: relative;">
                        <!-- Horizontal line connecting steps -->
                        <div style="content: ''; position: absolute; top: 25px; left: 10%; right: 10%; height: 2px; background-color: #e9ecef; z-index: 0;"></div>
                        <div class="progress-step active" data-step="patient" style="position: relative; z-index: 1; text-align: center; width: 20%;">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #DE6262; color: white; font-size: 1.25rem; margin: 0 auto; border: 2px solid #DE6262; box-shadow: 0 0 0 5px rgba(222, 98, 98, 0.2);">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="step-label mt-2">Patient</div>
                        </div>
                        <div class="progress-step" data-step="vitals" style="position: relative; z-index: 1; text-align: center; width: 20%;">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa; color: #6c757d; font-size: 1.25rem; margin: 0 auto; border: 2px solid #e9ecef;">
                                <i class="fas fa-heart-pulse"></i>
                            </div>
                            <div class="step-label mt-2">Vitals</div>
                        </div>
                        <div class="progress-step" data-step="symptoms" style="position: relative; z-index: 1; text-align: center; width: 20%;">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa; color: #6c757d; font-size: 1.25rem; margin: 0 auto; border: 2px solid #e9ecef;">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="step-label mt-2">Symptoms</div>
                        </div>
                        <div class="progress-step" data-step="diagnosis" style="position: relative; z-index: 1; text-align: center; width: 20%;">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa; color: #6c757d; font-size: 1.25rem; margin: 0 auto; border: 2px solid #e9ecef;">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <div class="step-label mt-2">Diagnosis</div>
                        </div>
                        <div class="progress-step" data-step="analysis" style="position: relative; z-index: 1; text-align: center; width: 20%;">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa; color: #6c757d; font-size: 1.25rem; margin: 0 auto; border: 2px solid #e9ecef;">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="step-label mt-2">AI Analysis</div>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 8px; border-radius: 4px; background-color: #f8f9fa;">
                        <div class="progress-bar" role="progressbar" style="width: 20%; background-color: #DE6262;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="medical-form-card">

                    @if(session('openai_api_error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-triangle"></i> API Key Error:</strong> {{ session('openai_api_error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(session('openai_error'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Error:</strong> {{ session('openai_error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-triangle"></i> Validation Errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <div id="errorMessages"></div>

                    <!-- Patient Selection -->
                    <div class="medical-form-section">
                        <h6><i class="fas fa-user me-2"></i>Patient Information</h6>

                        <!-- Patient Selection -->
                        <div class="mb-4">
                            <label for="existing_patient" class="form-label">Select Existing Patient</label>
                            <select class="form-select" id="existing_patient" name="existing_patient">
                                <option value="">-- Select from your patients or add new --</option>
                                @if(isset($assignedPatients))
                                    @foreach($assignedPatients as $patient)
                                        <option value="{{ $patient->id }}"
                                                data-name="{{ $patient->name }}"
                                                data-email="{{ $patient->email }}"
                                                data-phone="{{ $patient->phone }}"
                                                data-age="{{ $patient->age }}"
                                                data-gender="{{ $patient->gender }}">
                                            {{ $patient->name }} ({{ $patient->email }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text">
                                @if(isset($assignedPatients) && $assignedPatients->count() > 0)
                                    You have {{ $assignedPatients->count() }} patient(s). Select one or add a new patient below.
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
                                    <label for="patient_age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="patient_age" name="patient_age"
                                           value="{{ old('patient_age') }}" min="1" max="150">
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

                    <!-- Enhanced File Upload Section (always visible) -->
                    <div class="medical-form-section mt-4">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0"><i class="fas fa-file-medical  me-2" ></i>Medical Reports</h6>
                            <span class="badge bg-info ms-2">Optional</span>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted mb-2">
                                    Upload lab results, imaging reports, or any medical documents to enhance the AI analysis.
                                </p>
                                <div class="input-group mb-2">
                                    <input type="file" id="reports" name="reports[]" multiple class="form-control" accept="*/*">
                                    <button class="btn btn-primary" type="button" id="add-more-files-btn">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <div id="file-storage-container" style="display: none;"></div>

                                <div id="selected-files-container">
                                    <div id="selected-files" class="selected-files-list">
                                        <div class="text-center text-muted py-2">
                                            <i class="fas fa-file-upload me-2"></i>No files selected yet
                                        </div>
                                    </div>
                                </div>

                                <div id="upload-status" class="mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient History (only shown for existing patients) -->
                    <div class="medical-form-section" id="patient_history_info" style="display: none;">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0 me-2">Patient History</h6>
                            <span id="visit_count_badge" class="badge bg-info ms-2">Visit #1</span>
                        </div>
                        <div class="alert alert-info" id="patient_history_alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="patient_history_text">Select an existing patient to see their history.</span>
                        </div>
                    </div>

                    <!-- Enhanced Patient History Section -->
                    <div class="medical-form-section mt-4">
                        <h6><i class="fas fa-history me-2"></i>Patient History</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="chief_complaint" class="form-label">
                                    <i class="fas fa-exclamation-circle text-danger me-1"></i> Chief Complaint:
                                </label>
                                <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="3"
                                    placeholder="e.g., Persistent chest pain for 2 days">{{ $patientToEdit->chief_complaint ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="symptom_duration" class="form-label">
                                    <i class="fas fa-clock text-info me-1"></i> Duration of Symptoms:
                                </label>
                                <input type="text" name="symptom_duration" id="symptom_duration" class="form-control"
                                    placeholder="e.g., 3 days, 1 week" value="{{ $patientToEdit->symptom_duration ?? '' }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="past_medical_history" class="form-label">
                                    <i class="fas fa-file-medical text-primary me-1"></i> Past Medical History:
                                </label>
                                <textarea name="past_medical_history" id="past_medical_history" class="form-control" rows="3"
                                    placeholder="e.g., Hypertension, past surgery, asthma">{{ $patientToEdit->past_medical_history ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="medication_history" class="form-label">
                                    <i class="fas fa-pills text-warning me-1"></i> Current Medications:
                                </label>
                                <textarea name="medication_history" id="medication_history" class="form-control" rows="3"
                                    placeholder="e.g., Metformin 500mg, daily aspirin">{{ $patientToEdit->medication_history ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="allergies" class="form-label">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i> Known Allergies:
                                </label>
                                <input type="text" name="allergies" id="allergies" class="form-control"
                                    placeholder="e.g., Penicillin, nuts" value="{{ $patientToEdit->allergies ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label for="family_history" class="form-label">
                                    <i class="fas fa-users text-success me-1"></i> Family Medical History:
                                </label>
                                <textarea name="family_history" id="family_history" class="form-control" rows="2"
                                    placeholder="e.g., Diabetes in father, breast cancer in mother">{{ $patientToEdit->family_history ?? '' }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="social_history" class="form-label">
                                    <i class="fas fa-user-friends text-secondary me-1"></i> Lifestyle and Social History:
                                </label>
                                <textarea name="social_history" id="social_history" class="form-control" rows="2"
                                    placeholder="e.g., Smoker, alcohol use, sedentary job">{{ $patientToEdit->social_history ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="visit_type" class="form-label">
                                    <i class="fas fa-calendar-check text-info me-1"></i> Visit Type:
                                </label>
                                <select name="visit_type" id="visit_type" class="form-select">
                                    <option value="">Select visit type</option>
                                    <option value="Initial" {{ isset($patientToEdit) && $patientToEdit->visit_type == 'Initial' ? 'selected' : '' }}>Initial</option>
                                    <option value="Follow-up" {{ isset($patientToEdit) && $patientToEdit->visit_type == 'Follow-up' ? 'selected' : '' }}>Follow-up</option>
                                    <option value="Emergency" {{ isset($patientToEdit) && $patientToEdit->visit_type == 'Emergency' ? 'selected' : '' }}>Emergency</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Vitals -->
                    <div class="medical-form-section mt-4">
                        <h6>Physical Attributes / Vitals</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-weight text-primary me-1"></i> Weight:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="weight" class="form-control" value="{{ $patientToEdit->weight ?? '' }}" placeholder="70.5">
                                    <span class="input-group-text">kg</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-ruler-vertical text-success me-1"></i> Height:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="height" class="form-control" value="{{ $patientToEdit->height ?? '' }}" placeholder="175">
                                    <span class="input-group-text">cm</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-thermometer-half text-danger me-1"></i> Temperature:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="temperature" class="form-control" placeholder="37.2" value="{{ $patientToEdit->temperature ?? '' }}">
                                    <span class="input-group-text">°C</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                        </div>

                        <!-- Vital Signs Row -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-heartbeat text-danger me-1"></i> Heart Rate:
                                </label>
                                <div class="input-group">
                                    <input type="number" name="heart_rate" class="form-control" placeholder="72" value="{{ $patientToEdit->heart_rate ?? '' }}">
                                    <span class="input-group-text">bpm</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-lungs text-info me-1"></i> Respiratory Rate:
                                </label>
                                <div class="input-group">
                                    <input type="number" name="respiratory_rate" class="form-control" placeholder="16" value="{{ $patientToEdit->respiratory_rate ?? '' }}">
                                    <span class="input-group-text">breaths/min</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-wind text-primary me-1"></i> Oxygen Saturation:
                                </label>
                                <div class="input-group">
                                    <input type="number" name="oxygen_saturation" class="form-control" placeholder="98" min="0" max="100" value="{{ $patientToEdit->oxygen_saturation ?? '' }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-heart-pulse text-info me-1"></i> Blood Pressure:
                                </label>
                                <div class="input-group">
                                    <input type="text" name="blood_pressure" class="form-control" placeholder="120/80" value="{{ $patientToEdit->blood_pressure ?? '' }}">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pain and Blood Sugar Row -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-exclamation-circle text-warning me-1"></i> Pain Scale:
                                </label>
                                <select name="pain_scale" class="form-select">
                                    <option value="">Select pain level</option>
                                    @for($i = 0; $i <= 10; $i++)
                                        <option value="{{ $i }}" {{ isset($patientToEdit) && $patientToEdit->pain_scale == $i ? 'selected' : '' }}>
                                            {{ $i }} {{ $i == 0 ? '(No pain)' : ($i == 10 ? '(Worst pain)' : '') }}
                                        </option>
                                    @endfor
                                </select>
                                <small class="form-text text-muted">0 = no pain, 10 = worst pain imaginable</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> Pain Location:
                                </label>
                                <input type="text" name="pain_location" class="form-control" placeholder="e.g., Lower back, Head" value="{{ $patientToEdit->pain_location ?? '' }}">
                                <small class="form-text text-muted">Specify where the pain is located</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-tint text-warning me-1"></i> Blood Sugar:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="blood_sugar" class="form-control" placeholder="85" value="{{ $patientToEdit->blood_sugar ?? '' }}">
                                    <span class="input-group-text">mg/dL</span>
                                </div>
                                <small class="form-text text-muted">Enter numeric value only</small>
                            </div>
                        </div>
                    </div>

                    <!-- Symptoms -->
                    <div class="medical-form-section mt-4">
                        <h6>Symptoms</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-search text-primary me-1"></i> Current Symptoms:
                                </label>
                                <select id="current_symptoms" name="current_symptoms[]" multiple class="form-select">
                                    @foreach($symptoms as $symptom)
                                        <option value="{{ $symptom->id }}"
                                            {{ isset($patientToEdit) && $patientToEdit->symptoms && in_array($symptom->id, json_decode($patientToEdit->symptoms, true) ?: []) ? 'selected' : '' }}>
                                            {{ $symptom->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted mt-1 d-block">
                                    <i class="fas fa-info-circle me-1"></i> Select from the dropdown or add custom symptoms below.
                                </small>

                                <!-- Custom Symptoms Input -->
                                <div class="mt-2">
                                    <label class="form-label">
                                        <i class="fas fa-plus-circle me-1" style="color: #DE6262"></i> Add Custom Symptoms:
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="custom_symptom_input" class="form-control" placeholder="Type a custom symptom...">
                                        <button type="button" id="add_custom_symptom" style="background-color: #DE6262; color: white;">Add</button>
                                    </div>
                                    <div id="custom_symptoms_container" class="mt-2"></div>
                                    <input type="hidden" id="custom_symptoms_data" name="custom_symptoms" value="">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-clipboard-list text-danger me-1"></i> Common Symptoms:
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="symptoms_checkboxes[]" value="fever" class="form-check-input" id="fever">
                                            <label class="form-check-label" for="fever">
                                                <i class="fas fa-thermometer-three-quarters text-danger me-1"></i> Fever
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="symptoms_checkboxes[]" value="cough" class="form-check-input" id="cough">
                                            <label class="form-check-label" for="cough">
                                                <i class="fas fa-head-side-cough text-warning me-1"></i> Cough
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="symptoms_checkboxes[]" value="headache" class="form-check-input" id="headache">
                                            <label class="form-check-label" for="headache">
                                                <i class="fas fa-head-side-headache text-info me-1"></i> Headache
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="symptoms_checkboxes[]" value="fatigue" class="form-check-input" id="fatigue">
                                            <label class="form-check-label" for="fatigue">
                                                <i class="fas fa-battery-quarter text-secondary me-1"></i> Fatigue
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tests and Diagnosis -->
                    <div class="medical-form-section mt-4">
                        <h6>Test Results & Preliminary Diagnosis</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-flask text-info me-1"></i> Test Results:
                                </label>
                                <textarea name="test_results" class="form-control" rows="4" placeholder="e.g., CRP: Elevated at 15 mg/L.
CBC: WBC 12,000/μL, Hgb 13.5 g/dL, Plt 250,000/μL
Urinalysis: Negative for protein, glucose, and blood
X-ray: No abnormalities detected">{{ $patientToEdit->test_results ?? '' }}</textarea>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-test" data-test="CBC">CBC</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-test" data-test="CRP">CRP</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-test" data-test="Urinalysis">Urinalysis</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-test" data-test="X-ray">X-ray</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary quick-test" data-test="CT Scan">CT Scan</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-stethoscope text-success me-1"></i> Preliminary Diagnosis:
                                </label>
                                <textarea name="preliminary_diagnosis" class="form-control" rows="4" placeholder="Enter your initial assessment or suspected diagnosis based on the patient's symptoms and test results." value="{{ $patientToEdit->preliminary_diagnosis ?? '' }}">{{ $patientToEdit->preliminary_diagnosis ?? '' }}</textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i> This will be analyzed by the AI to provide recommendations
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Head-to-Toe Assessment Section -->
                    <div class="medical-form-section mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6><i class="fas fa-user-check me-2"></i>Head-to-Toe Assessment</h6>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#headToToeAssessment" aria-expanded="false">
                                <i class="fas fa-chevron-down me-1"></i> Toggle Assessment
                            </button>
                        </div>

                        <div class="collapse" id="headToToeAssessment">
                            <!-- General Appearance -->
                            <div class="assessment-subsection mb-4" id="general-appearance-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-eye me-2"></i>General Appearance</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="general-appearance-normal" data-section="general-appearance-content">
                                        <label class="form-check-label" for="general-appearance-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="general-appearance-content">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="consciousness_level" class="form-label">Consciousness Level:</label>
                                            <select name="consciousness_level" id="consciousness_level" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="Alert" {{ isset($patientToEdit) && $patientToEdit->consciousness_level == 'Alert' ? 'selected' : '' }}>Alert</option>
                                                <option value="Drowsy" {{ isset($patientToEdit) && $patientToEdit->consciousness_level == 'Drowsy' ? 'selected' : '' }}>Drowsy</option>
                                                <option value="Unresponsive" {{ isset($patientToEdit) && $patientToEdit->consciousness_level == 'Unresponsive' ? 'selected' : '' }}>Unresponsive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="mood_behavior" class="form-label">Mood/Behavior:</label>
                                            <select name="mood_behavior" id="mood_behavior" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="Calm" {{ isset($patientToEdit) && $patientToEdit->mood_behavior == 'Calm' ? 'selected' : '' }}>Calm</option>
                                                <option value="Anxious" {{ isset($patientToEdit) && $patientToEdit->mood_behavior == 'Anxious' ? 'selected' : '' }}>Anxious</option>
                                                <option value="Aggressive" {{ isset($patientToEdit) && $patientToEdit->mood_behavior == 'Aggressive' ? 'selected' : '' }}>Aggressive</option>
                                                <option value="Confused" {{ isset($patientToEdit) && $patientToEdit->mood_behavior == 'Confused' ? 'selected' : '' }}>Confused</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="speech_clarity" class="form-label">Speech Clarity:</label>
                                            <select name="speech_clarity" id="speech_clarity" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="Clear" {{ isset($patientToEdit) && $patientToEdit->speech_clarity == 'Clear' ? 'selected' : '' }}>Clear</option>
                                                <option value="Slurred" {{ isset($patientToEdit) && $patientToEdit->speech_clarity == 'Slurred' ? 'selected' : '' }}>Slurred</option>
                                                <option value="Incoherent" {{ isset($patientToEdit) && $patientToEdit->speech_clarity == 'Incoherent' ? 'selected' : '' }}>Incoherent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="hygiene_level" class="form-label">Hygiene Level:</label>
                                            <select name="hygiene_level" id="hygiene_level" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="Good" {{ isset($patientToEdit) && $patientToEdit->hygiene_level == 'Good' ? 'selected' : '' }}>Good</option>
                                                <option value="Fair" {{ isset($patientToEdit) && $patientToEdit->hygiene_level == 'Fair' ? 'selected' : '' }}>Fair</option>
                                                <option value="Poor" {{ isset($patientToEdit) && $patientToEdit->hygiene_level == 'Poor' ? 'selected' : '' }}>Poor</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- HEENT -->
                            <div class="assessment-subsection mb-4" id="heent-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-head-side-virus me-2"></i>Head, Eyes, Ears, Nose, Mouth (HEENT)</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="heent-normal" data-section="heent-content">
                                        <label class="form-check-label" for="heent-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="heent-content">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="scalp_condition" class="form-label">Scalp Condition:</label>
                                            <input type="text" name="scalp_condition" id="scalp_condition" class="form-control"
                                                placeholder="e.g., Normal, lesions, alopecia" value="{{ $patientToEdit->scalp_condition ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="pupil_reactivity" class="form-label">Pupil Reactivity:</label>
                                            <select name="pupil_reactivity" id="pupil_reactivity" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="PERRLA" {{ isset($patientToEdit) && $patientToEdit->pupil_reactivity == 'PERRLA' ? 'selected' : '' }}>PERRLA</option>
                                                <option value="Unequal" {{ isset($patientToEdit) && $patientToEdit->pupil_reactivity == 'Unequal' ? 'selected' : '' }}>Unequal</option>
                                                <option value="Non-reactive" {{ isset($patientToEdit) && $patientToEdit->pupil_reactivity == 'Non-reactive' ? 'selected' : '' }}>Non-reactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Issues:</label>
                                            <div class="form-check">
                                                <input type="checkbox" name="vision_issues" id="vision_issues" class="form-check-input" value="1"
                                                    {{ isset($patientToEdit) && $patientToEdit->vision_issues ? 'checked' : '' }}>
                                                <label class="form-check-label" for="vision_issues">Vision Issues</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="hearing_issues" id="hearing_issues" class="form-check-input" value="1"
                                                    {{ isset($patientToEdit) && $patientToEdit->hearing_issues ? 'checked' : '' }}>
                                                <label class="form-check-label" for="hearing_issues">Hearing Issues</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <label for="oral_findings" class="form-label">Oral Findings:</label>
                                            <textarea name="oral_findings" id="oral_findings" class="form-control" rows="2"
                                                    placeholder="e.g., Good dentition, dry mucous membranes, thrush">{{ $patientToEdit->oral_findings ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Neurological -->
                            <div class="assessment-subsection mb-4" id="neurological-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-brain me-2"></i>Neurological</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="neurological-normal" data-section="neurological-content">
                                        <label class="form-check-label" for="neurological-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="neurological-content">
                                    <div class="row">
                                    <div class="col-md-3">
                                        <label for="orientation_level" class="form-label">Orientation:</label>
                                        <select name="orientation_level" id="orientation_level" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Oriented x4" {{ isset($patientToEdit) && $patientToEdit->orientation_level == 'Oriented x4' ? 'selected' : '' }}>Oriented x4</option>
                                            <option value="Oriented x3" {{ isset($patientToEdit) && $patientToEdit->orientation_level == 'Oriented x3' ? 'selected' : '' }}>Oriented x3</option>
                                            <option value="Oriented x2" {{ isset($patientToEdit) && $patientToEdit->orientation_level == 'Oriented x2' ? 'selected' : '' }}>Oriented x2</option>
                                            <option value="Disoriented" {{ isset($patientToEdit) && $patientToEdit->orientation_level == 'Disoriented' ? 'selected' : '' }}>Disoriented</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="limb_strength" class="form-label">Limb Strength:</label>
                                        <select name="limb_strength" id="limb_strength" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Equal" {{ isset($patientToEdit) && $patientToEdit->limb_strength == 'Equal' ? 'selected' : '' }}>Equal</option>
                                            <option value="Weak Left" {{ isset($patientToEdit) && $patientToEdit->limb_strength == 'Weak Left' ? 'selected' : '' }}>Weak Left</option>
                                            <option value="Weak Right" {{ isset($patientToEdit) && $patientToEdit->limb_strength == 'Weak Right' ? 'selected' : '' }}>Weak Right</option>
                                            <option value="Paralyzed" {{ isset($patientToEdit) && $patientToEdit->limb_strength == 'Paralyzed' ? 'selected' : '' }}>Paralyzed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="reflexes" class="form-label">Reflexes:</label>
                                        <select name="reflexes" id="reflexes" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Normal" {{ isset($patientToEdit) && $patientToEdit->reflexes == 'Normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="Hyperreflexia" {{ isset($patientToEdit) && $patientToEdit->reflexes == 'Hyperreflexia' ? 'selected' : '' }}>Hyperreflexia</option>
                                            <option value="Hyporeflexia" {{ isset($patientToEdit) && $patientToEdit->reflexes == 'Hyporeflexia' ? 'selected' : '' }}>Hyporeflexia</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="sensation_findings" class="form-label">Sensation:</label>
                                        <textarea name="sensation_findings" id="sensation_findings" class="form-control" rows="2"
                                                  placeholder="e.g., Intact, decreased, numbness">{{ $patientToEdit->sensation_findings ?? '' }}</textarea>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Neck and Chest -->
                            <div class="assessment-subsection mb-4" id="neck-chest-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-lungs me-2"></i>Neck and Chest</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="neck-chest-normal" data-section="neck-chest-content">
                                        <label class="form-check-label" for="neck-chest-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="neck-chest-content">
                                    <div class="row">
                                    <div class="col-md-2">
                                        <label for="trachea_position" class="form-label">Trachea:</label>
                                        <select name="trachea_position" id="trachea_position" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Midline" {{ isset($patientToEdit) && $patientToEdit->trachea_position == 'Midline' ? 'selected' : '' }}>Midline</option>
                                            <option value="Deviated" {{ isset($patientToEdit) && $patientToEdit->trachea_position == 'Deviated' ? 'selected' : '' }}>Deviated</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">JVD:</label>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" name="jvd_present" id="jvd_present" class="form-check-input" value="1"
                                                   {{ isset($patientToEdit) && $patientToEdit->jvd_present ? 'checked' : '' }}>
                                            <label class="form-check-label" for="jvd_present">Present</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="lung_sounds" class="form-label">Lung Sounds:</label>
                                        <select name="lung_sounds" id="lung_sounds" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Clear" {{ isset($patientToEdit) && $patientToEdit->lung_sounds == 'Clear' ? 'selected' : '' }}>Clear</option>
                                            <option value="Crackles" {{ isset($patientToEdit) && $patientToEdit->lung_sounds == 'Crackles' ? 'selected' : '' }}>Crackles</option>
                                            <option value="Wheezes" {{ isset($patientToEdit) && $patientToEdit->lung_sounds == 'Wheezes' ? 'selected' : '' }}>Wheezes</option>
                                            <option value="Diminished" {{ isset($patientToEdit) && $patientToEdit->lung_sounds == 'Diminished' ? 'selected' : '' }}>Diminished</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="heart_sounds" class="form-label">Heart Sounds:</label>
                                        <select name="heart_sounds" id="heart_sounds" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Normal" {{ isset($patientToEdit) && $patientToEdit->heart_sounds == 'Normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="Murmur" {{ isset($patientToEdit) && $patientToEdit->heart_sounds == 'Murmur' ? 'selected' : '' }}>Murmur</option>
                                            <option value="Irregular" {{ isset($patientToEdit) && $patientToEdit->heart_sounds == 'Irregular' ? 'selected' : '' }}>Irregular</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="capillary_refill_time" class="form-label">Cap Refill:</label>
                                        <select name="capillary_refill_time" id="capillary_refill_time" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="< 2s" {{ isset($patientToEdit) && $patientToEdit->capillary_refill_time == '< 2s' ? 'selected' : '' }}>< 2s</option>
                                            <option value="2–3s" {{ isset($patientToEdit) && $patientToEdit->capillary_refill_time == '2–3s' ? 'selected' : '' }}>2–3s</option>
                                            <option value="> 3s" {{ isset($patientToEdit) && $patientToEdit->capillary_refill_time == '> 3s' ? 'selected' : '' }}>> 3s</option>
                                        </select>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Abdomen -->
                            <div class="assessment-subsection mb-4" id="abdomen-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-stomach me-2"></i>Abdomen</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="abdomen-normal" data-section="abdomen-content">
                                        <label class="form-check-label" for="abdomen-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="abdomen-content">
                                    <div class="row">
                                    <div class="col-md-3">
                                        <label for="abdominal_shape" class="form-label">Shape:</label>
                                        <select name="abdominal_shape" id="abdominal_shape" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Flat" {{ isset($patientToEdit) && $patientToEdit->abdominal_shape == 'Flat' ? 'selected' : '' }}>Flat</option>
                                            <option value="Distended" {{ isset($patientToEdit) && $patientToEdit->abdominal_shape == 'Distended' ? 'selected' : '' }}>Distended</option>
                                            <option value="Scarred" {{ isset($patientToEdit) && $patientToEdit->abdominal_shape == 'Scarred' ? 'selected' : '' }}>Scarred</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="bowel_sounds" class="form-label">Bowel Sounds:</label>
                                        <select name="bowel_sounds" id="bowel_sounds" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Normal" {{ isset($patientToEdit) && $patientToEdit->bowel_sounds == 'Normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="Hyperactive" {{ isset($patientToEdit) && $patientToEdit->bowel_sounds == 'Hyperactive' ? 'selected' : '' }}>Hyperactive</option>
                                            <option value="Hypoactive" {{ isset($patientToEdit) && $patientToEdit->bowel_sounds == 'Hypoactive' ? 'selected' : '' }}>Hypoactive</option>
                                            <option value="Absent" {{ isset($patientToEdit) && $patientToEdit->bowel_sounds == 'Absent' ? 'selected' : '' }}>Absent</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="appetite_level" class="form-label">Appetite:</label>
                                        <select name="appetite_level" id="appetite_level" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Good" {{ isset($patientToEdit) && $patientToEdit->appetite_level == 'Good' ? 'selected' : '' }}>Good</option>
                                            <option value="Poor" {{ isset($patientToEdit) && $patientToEdit->appetite_level == 'Poor' ? 'selected' : '' }}>Poor</option>
                                            <option value="None" {{ isset($patientToEdit) && $patientToEdit->appetite_level == 'None' ? 'selected' : '' }}>None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Symptoms:</label>
                                        <div class="form-check">
                                            <input type="checkbox" name="abdominal_tenderness" id="abdominal_tenderness" class="form-check-input" value="1"
                                                   {{ isset($patientToEdit) && $patientToEdit->abdominal_tenderness ? 'checked' : '' }}>
                                            <label class="form-check-label" for="abdominal_tenderness">Tenderness</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="nausea_or_vomiting" id="nausea_or_vomiting" class="form-check-input" value="1"
                                                   {{ isset($patientToEdit) && $patientToEdit->nausea_or_vomiting ? 'checked' : '' }}>
                                            <label class="form-check-label" for="nausea_or_vomiting">Nausea/Vomiting</label>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Genitourinary -->
                            <div class="assessment-subsection mb-4" id="genitourinary-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-kidneys me-2"></i>Genitourinary</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="genitourinary-normal" data-section="genitourinary-content">
                                        <label class="form-check-label" for="genitourinary-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="genitourinary-content">
                                    <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Issues:</label>
                                        <div class="form-check">
                                            <input type="checkbox" name="urination_issues" id="urination_issues" class="form-check-input" value="1"
                                                   {{ isset($patientToEdit) && $patientToEdit->urination_issues ? 'checked' : '' }}>
                                            <label class="form-check-label" for="urination_issues">Urination Issues</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="catheter_present" id="catheter_present" class="form-check-input" value="1"
                                                   {{ isset($patientToEdit) && $patientToEdit->catheter_present ? 'checked' : '' }}>
                                            <label class="form-check-label" for="catheter_present">Catheter Present</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="urine_characteristics" class="form-label">Urine Characteristics:</label>
                                        <textarea name="urine_characteristics" id="urine_characteristics" class="form-control" rows="2"
                                                  placeholder="e.g., Clear yellow, cloudy, hematuria">{{ $patientToEdit->urine_characteristics ?? '' }}</textarea>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Musculoskeletal -->
                            <div class="assessment-subsection mb-4" id="musculoskeletal-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-bone me-2"></i>Musculoskeletal</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="musculoskeletal-normal" data-section="musculoskeletal-content">
                                        <label class="form-check-label" for="musculoskeletal-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="musculoskeletal-content">
                                    <div class="row">
                                    <div class="col-md-3">
                                        <label for="range_of_motion" class="form-label">Range of Motion:</label>
                                        <select name="range_of_motion" id="range_of_motion" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Full" {{ isset($patientToEdit) && $patientToEdit->range_of_motion == 'Full' ? 'selected' : '' }}>Full</option>
                                            <option value="Limited" {{ isset($patientToEdit) && $patientToEdit->range_of_motion == 'Limited' ? 'selected' : '' }}>Limited</option>
                                            <option value="None" {{ isset($patientToEdit) && $patientToEdit->range_of_motion == 'None' ? 'selected' : '' }}>None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gait_stability" class="form-label">Gait Stability:</label>
                                        <select name="gait_stability" id="gait_stability" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Stable" {{ isset($patientToEdit) && $patientToEdit->gait_stability == 'Stable' ? 'selected' : '' }}>Stable</option>
                                            <option value="Unsteady" {{ isset($patientToEdit) && $patientToEdit->gait_stability == 'Unsteady' ? 'selected' : '' }}>Unsteady</option>
                                            <option value="Requires assistance" {{ isset($patientToEdit) && $patientToEdit->gait_stability == 'Requires assistance' ? 'selected' : '' }}>Requires assistance</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="assistive_devices" class="form-label">Assistive Devices:</label>
                                        <input type="text" name="assistive_devices" id="assistive_devices" class="form-control"
                                               placeholder="e.g., Walker, cane, wheelchair" value="{{ $patientToEdit->assistive_devices ?? '' }}">
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Skin -->
                            <div class="assessment-subsection mb-4" id="skin-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-hand-paper me-2"></i>Skin</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="skin-normal" data-section="skin-content">
                                        <label class="form-check-label" for="skin-normal">
                                            Normal
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="skin-content">
                                    <div class="row">
                                    <div class="col-md-3">
                                        <label for="skin_color" class="form-label">Color:</label>
                                        <select name="skin_color" id="skin_color" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Pink" {{ isset($patientToEdit) && $patientToEdit->skin_color == 'Pink' ? 'selected' : '' }}>Pink</option>
                                            <option value="Pale" {{ isset($patientToEdit) && $patientToEdit->skin_color == 'Pale' ? 'selected' : '' }}>Pale</option>
                                            <option value="Cyanotic" {{ isset($patientToEdit) && $patientToEdit->skin_color == 'Cyanotic' ? 'selected' : '' }}>Cyanotic</option>
                                            <option value="Jaundiced" {{ isset($patientToEdit) && $patientToEdit->skin_color == 'Jaundiced' ? 'selected' : '' }}>Jaundiced</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="skin_temperature" class="form-label">Temperature:</label>
                                        <select name="skin_temperature" id="skin_temperature" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Warm" {{ isset($patientToEdit) && $patientToEdit->skin_temperature == 'Warm' ? 'selected' : '' }}>Warm</option>
                                            <option value="Cool" {{ isset($patientToEdit) && $patientToEdit->skin_temperature == 'Cool' ? 'selected' : '' }}>Cool</option>
                                            <option value="Cold" {{ isset($patientToEdit) && $patientToEdit->skin_temperature == 'Cold' ? 'selected' : '' }}>Cold</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Pressure Ulcers:</label>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" name="pressure_ulcers" id="pressure_ulcers" class="form-check-input" value="1"
                                                   {{ isset($patientToEdit) && $patientToEdit->pressure_ulcers ? 'checked' : '' }}>
                                            <label class="form-check-label" for="pressure_ulcers">Present</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="skin_lesions" class="form-label">Lesions:</label>
                                        <textarea name="skin_lesions" id="skin_lesions" class="form-control" rows="2"
                                                  placeholder="e.g., Rash, bruising, wounds">{{ $patientToEdit->skin_lesions ?? '' }}</textarea>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Pain Assessment -->
                            <div class="assessment-subsection mb-4" id="pain-assessment-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Pain Assessment</h5>
                                    <div class="form-check">
                                        <input class="form-check-input section-normal-checkbox" type="checkbox" id="pain-assessment-normal" data-section="pain-assessment-content">
                                        <label class="form-check-label" for="pain-assessment-normal">
                                            No Pain
                                        </label>
                                    </div>
                                </div>
                                <div class="section-content" id="pain-assessment-content">
                                    <div class="row">
                                    <div class="col-md-3">
                                        <label for="pain_score" class="form-label">Pain Score (0-10):</label>
                                        <input type="number" name="pain_score" id="pain_score" class="form-control" min="0" max="10"
                                               placeholder="0-10" value="{{ $patientToEdit->pain_scale ?? '' }}">
                                        <small class="text-muted">0 = no pain, 10 = worst pain</small>
                                    </div>
                                    <div class="col-md-9">
                                        <label for="pain_description" class="form-label">Pain Description:</label>
                                        <textarea name="pain_description" id="pain_description" class="form-control" rows="2"
                                                  placeholder="e.g., Sharp, stabbing pain in right lower quadrant, worse with movement">{{ $patientToEdit->pain_description ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physician Notes Section -->
                    <div class="medical-form-section mt-4">
                        <h6><i class="fas fa-user-md me-2"></i>Clinical Notes</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="physician_notes" class="form-label">
                                    <i class="fas fa-notes-medical text-primary me-1"></i> Doctor Notes or Impression:
                                </label>
                                <textarea name="physician_notes" id="physician_notes" class="form-control" rows="4"
                                    placeholder="e.g., Suspected viral infection. Awaiting lab results.">{{ $patientToEdit->physician_notes ?? '' }}</textarea>
                                <small class="text-muted mt-1">
                                    <i class="fas fa-info-circle me-1"></i> Your clinical impression and observations
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label for="additional_notes" class="form-label">
                                    <i class="fas fa-sticky-note text-secondary me-1"></i> Additional Notes:
                                </label>
                                <textarea name="additional_notes" id="additional_notes" class="form-control" rows="4"
                                    placeholder="Any extra information not covered above">{{ $patientToEdit->additional_notes ?? '' }}</textarea>
                                <small class="text-muted mt-1">
                                    <i class="fas fa-info-circle me-1"></i> Any additional relevant information
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Section -->
                    <div class="submit-section">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-robot me-2"></i>Get AI Analysis
                        </button>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your data is processed securely and confidentially
                            </small>
                        </div>
                    </div>
                    <div id="form-progress" class="progress mt-3 d-none" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                            role="progressbar"
                            style="width: 0%">
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Loading Indicator (Progress Bar) -->
<div id="page-loader" style="display:none; position: fixed; inset: 0; background: rgba(248, 249, 250, 0.9); z-index: 1050; display: none; align-items: center; justify-content: center;">
    <div class="card p-4 p-md-5 bg-white" style="width: min(520px, 92vw); border: 1px solid #e9ecef; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-radius: 14px;">
        <div class="text-center mb-3">
            <div class="spinner-border text-danger mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mb-2" style="font-size: 1.15rem; font-weight: 600; color:#2c3e50;">
                Processing your request
            </div>
            <div class="text-muted" style="font-size: 0.92rem;">This may take a moment. Keep this page open.</div>
        </div>

        <div class="progress mb-2" style="height: 16px; background-color: #eef1f4; border-radius: 10px;">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: linear-gradient(90deg, #DE6262, #ff8a65);"></div>
        </div>
        <div class="text-center" style="font-size: 0.85rem; color:#6c757d;">
            Please wait while the AI analyzes the data...
        </div>
    </div>
</div>


<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: #fff">
                    <i class="fas fa-stethoscope me-2"></i>AI Recommendations
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printResponseBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <!-- AI Response Section with Enhanced Structure -->
                <div class="ai-response-section mb-4">
                    <!-- Level 1: Core Analysis -->
                    <div class="medcura-level1">
                        <div class="level1-header level-header">
                            <i class="fas fa-stethoscope me-2"></i>
                            <span>Core Medical Analysis</span>
                        </div>
                        <div id="openaiReply" class="response-text"></div>
                    </div>

                    <!-- Level 2: Detailed Analysis (Initially Hidden) -->
                    <div class="medcura-level2">
                        <div class="level2-header level-header level2-toggle" onclick="toggleLevel2()">
                            <span>
                                <i class="fas fa-microscope me-2"></i>
                                Detailed Clinical Analysis
                                <div class="toggle-hint">Click to Expand</div>
                            </span>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div id="level2-content" class="level2-content" style="display: none;">
                            <div class="level2-section-header">Advanced Differential Diagnosis</div>
                            <p>This section provides detailed clinical reasoning, alternative diagnoses, and comprehensive management strategies based on current medical guidelines.</p>

                            <div class="level2-section-header">Risk Stratification</div>
                            <p>Detailed risk assessment considering patient-specific factors, comorbidities, and prognostic indicators.</p>

                            <div class="level2-section-header">Evidence-Based Recommendations</div>
                            <p>Treatment recommendations based on latest clinical evidence and best practice guidelines.</p>
                        </div>
                    </div>
                </div>

                <!-- Sources Section - Hidden as requested -->
                <div id="sourcesCitation" class="mt-4" style="display: none;">
                    <div id="sourcesContent" class="sources-list">
                        <!-- Source logos will be populated here but not displayed -->
                    </div>
                </div>

                <!-- Enhanced Chat Continuation Section -->
                <div class="chat-section mt-4">
                    <div class="chat-header">
                        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Follow-up Questions</h6>
                        <small class="text-muted">Ask additional questions about the diagnosis or treatment</small>
                    </div>

                    <div id="chat-messages" class="chat-messages-container">
                        <!-- Additional messages will appear here -->
                    </div>

                    <div class="chat-input-container">
                        <form id="follow-up-form" class="chat-form">
                            @csrf
                            <input type="hidden" id="conversation-id" name="conversation_id" value="{{ session('conversation_id') ?? '' }}">
                            <div class="input-group">
                                <input type="text" id="follow-up-message" name="message" class="form-control"
                                       placeholder="Ask a follow-up question..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    <span class="d-none d-md-inline ms-1">Send</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Manual Diagnosis Section -->
                @if(session('ai_result_id') && session('patient_id'))
                <div class="manual-diagnosis-section mt-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-user-md me-2"></i>Write Manual Diagnosis
                            </h6>
                            <small>Based on the AI analysis above, write your professional diagnosis</small>
                        </div>
                        <div class="card-body">
                            <form id="manual-diagnosis-form" action="{{ route('openai.create-manual-diagnosis') }}" method="POST">
                                @csrf
                                <input type="hidden" name="ai_result_id" value="{{ session('ai_result_id') }}">
                                <input type="hidden" name="patient_id" value="{{ session('patient_id') }}">

                                <div class="mb-3">
                                    <label for="diagnosis_text" class="form-label">
                                        <strong>Your Professional Diagnosis:</strong>
                                    </label>
                                    <textarea name="diagnosis_text" id="diagnosis_text" class="form-control" rows="6"
                                              placeholder="Write your professional diagnosis based on the AI analysis and your clinical judgment..."
                                              required></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        This diagnosis will be saved to the patient's record and the AI analysis will be linked as supporting information.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" onclick="$('#diagnosis_text').val('')">
                                        <i class="fas fa-eraser me-1"></i>Clear
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Save Diagnosis
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS for chat interface -->
<style>

</style>







    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Include Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Include Choices.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/scripts/choices.min.js"></script>

<!-- Include our custom OpenAI JavaScript -->
<script src="{{ asset('js/openai.js') }}"></script>

@if (session('openai_result'))
    <script>
         document.addEventListener('DOMContentLoaded', function () {
            // Show the modal with the full response immediately
            const modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();

            // Hide the inline progress overlay once the modal is shown
            const pageLoader = document.getElementById('page-loader');
            if (pageLoader) {
                pageLoader.style.display = 'none';
            }

            // Get the AI response and display it immediately (no typing animation)
            const aiResponse = @json(session('openai_result'));

            // Format the response to remove markdown symbols and preserve important sections
            let formattedResponse = aiResponse
                // Remove markdown formatting
                .replace(/#{1,6}\s/g, '')  // Remove heading markers
                .replace(/\*\*/g, '')      // Remove bold markers
                .replace(/\*/g, '')        // Remove italic markers
                .replace(/- /g, '• ')      // Replace dashes with bullets

                // Extract PATIENT INFORMATION section if it exists
                let patientInfoSection = '';
                const patientInfoMatch = aiResponse.match(/PATIENT\s+INFORMATION:[\s\S]*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS:)/i);
                if (patientInfoMatch) {
                    patientInfoSection = patientInfoMatch[0];
                }

                // Remove introduction and conclusion sections, but preserve PATIENT INFORMATION
                let processedResponse = aiResponse
                    .replace(/^Based on the provided.*?guidelines,.*?\n\n/s, '')  // Remove intro
                    .replace(/^As a.*?specialist:.*?\n\n/s, '')                  // Remove specialty intro
                    .replace(/\n\nConclusion:.*$/s, '')                          // Remove conclusion
                    .replace(/\n\nNote:.*$/s, '')                                // Remove notes at the end
                    .replace(/^Note:.*\n\n/s, '')                                // Remove notes at the beginning
                    .replace(/\n\nIn summary.*$/s, '')                           // Remove summary
                    .replace(/\n\nSummary.*$/s, '');

                // Extract the diagnosis part (everything from A) POSSIBLE DIAGNOSIS onwards)
                const diagnosisMatch = processedResponse.match(/A\)\s*POSSIBLE\s*DIAGNOSIS:[\s\S]*$/i);
                const diagnosisPart = diagnosisMatch ? diagnosisMatch[0] : processedResponse;

                // Combine the sections in the right order
                formattedResponse = '';
                if (patientInfoSection) {
                    formattedResponse += patientInfoSection + "\n\n";
                }
                formattedResponse += diagnosisPart;

                // Clean up any remaining formatting issues
                formattedResponse = formattedResponse
                    .replace(/\n{3,}/g, '\n\n')  // Replace multiple newlines with double newlines
                    .trim();                      // Remove leading/trailing whitespace

            // Format the response with proper HTML formatting
            const formattedHTML = formatAIResponse(formattedResponse);
            document.getElementById('openaiReply').innerHTML = formattedHTML;

            // Sources section is hidden as requested
            const sourcesMatch = formattedResponse.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
            if (sourcesMatch && sourcesMatch[1].trim()) {
                const sourcesContent = sourcesMatch[1].trim();
                document.getElementById('sourcesContent').innerHTML = formatSources(sourcesContent);
                // Keep sources hidden
                document.getElementById('sourcesCitation').style.display = 'none';
            } else {
                document.getElementById('sourcesCitation').style.display = 'none';
            }

            // Set the conversation ID for follow-up messages
            if (document.getElementById('conversation-id')) {
                document.getElementById('conversation-id').value = @json(session('conversation_id') ?? '');
            }

            // Set up the follow-up form handler - this is already initialized in the external JS file
            // No need to call it again here as it's already initialized in initializeFollowUpChat()
        });
    </script>
    @endif
    @endsection

