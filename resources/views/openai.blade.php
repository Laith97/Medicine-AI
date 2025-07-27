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

<!-- Include Choices.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/scripts/choices.min.js"></script>

<style>
    /* Global Font */
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    .file-upload-wrapper {
        position: relative;
    }

    #selected-files {
        margin-top: 10px;
    }

    .selected-file {
        display: flex;
        align-items: center;
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }

    .selected-file .file-icon {
        margin-right: 8px;
        color: #DE6262;
    }

    .selected-file .file-name {
        flex-grow: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .selected-file .file-remove {
        cursor: pointer;
        color: #6c757d;
        margin-left: 8px;
    }

    .selected-file .file-remove:hover {
        color: #dc3545;
    }

    .file-size {
        color: #6c757d;
        font-size: 0.75rem;
        margin-left: 8px;
    }

    .selected-file .file-remove {
        opacity: 0.7;
        transition: all 0.2s;
    }

    .selected-file .file-remove:hover {
        opacity: 1;
        color: #dc3545;
        transform: scale(1.2);
    }

    .selected-files-list {
        max-height: 200px;
        overflow-y: auto;
        padding-right: 5px;
        margin-bottom: 10px;
        border-radius: 4px;
        background-color: #f8f9fa;
        padding: 8px;
    }

    /* Enhanced Mobile Responsiveness */
    @media (max-width: 768px) {
        .container {
            padding: 0 10px;
        }

        .medical-form-container {
            padding: 0;
        }

        .medical-form-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
        }

        .medical-form-section h6 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .form-control, .form-select {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            height: auto;
            min-height: 38px;
        }

        .input-group-text {
            font-size: 0.8rem;
            padding: 0.5rem 0.6rem;
        }

        /* Make form progress horizontal on mobile */
        .form-progress-container {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .progress-steps {
            flex-direction: row;
            justify-content: space-between;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .progress-step {
            width: auto !important;
            min-width: 60px;
            text-align: center !important;
            flex: none;
        }

        .step-icon {
            width: 35px !important;
            height: 35px !important;
            font-size: 1rem !important;
            margin: 0 auto;
        }

        .step-label {
            font-size: 0.7rem;
            margin-top: 0.3rem !important;
            white-space: nowrap;
        }

        /* Stack form fields on mobile with better spacing */
        .row .col-md-2,
        .row .col-md-3,
        .row .col-md-4,
        .row .col-md-6,
        .row .col-md-8,
        .row .col-md-12 {
            margin-bottom: 0.75rem;
        }

        /* Assessment subsections */
        .assessment-subsection {
            padding: 0.75rem;
            margin-bottom: 1rem;
        }

        .assessment-subsection h5 {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        /* File upload section */
        .input-group .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }

        .selected-files-list {
            max-height: 150px;
            padding: 0.5rem;
        }

        .selected-file {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
        }

        /* Textarea adjustments */
        textarea.form-control {
            min-height: 80px;
        }

        /* Checkbox and radio styling */
        .form-check-input {
            transform: scale(1.1);
        }

        .form-check-label {
            font-size: 0.85rem;
            margin-left: 0.3rem;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            margin-top: 1rem;
            padding: 0.75rem;
            font-size: 1rem;
        }

        /* Modal responsiveness */
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .modal-dialog.modal-xl {
            max-width: calc(100% - 1rem);
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-header {
            padding: 0.75rem 1rem;
        }

        .modal-title {
            font-size: 1.1rem;
        }
    }

    /* Tablet responsiveness */
    @media (max-width: 992px) {
        .medical-form-section {
            padding: 1.25rem;
        }

        .form-control, .form-select {
            font-size: 0.95rem;
        }

        .assessment-subsection {
            padding: 1rem;
        }
    }

    /* Very small screens (phones in landscape) */
    @media (max-width: 576px) {
        .container {
            padding: 0 5px;
        }

        .medical-form-section {
            padding: 0.75rem;
            margin-bottom: 1rem;
        }

        .medical-form-section h6 {
            font-size: 1rem;
            font-weight: 600;
        }

        .form-label {
            font-size: 0.8rem;
        }

        .form-control, .form-select {
            font-size: 0.85rem;
            padding: 0.4rem 0.6rem;
            min-height: 35px;
        }

        .step-icon {
            width: 30px !important;
            height: 30px !important;
            font-size: 0.9rem !important;
        }

        .step-label {
            font-size: 0.65rem;
        }
    }

    /* Professional styling enhancements */
    .medical-form-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .medical-form-section h6 {
        /* color: #2c3e50; */
        border-bottom: 2px solid #DE6262;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .form-label {
        color: #495057;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #DE6262;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
        color: #6c757d;
        font-weight: 500;
    }

    /* Head-to-Toe Assessment Styling */
    .assessment-subsection {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        border-left: 4px solid #DE6262;
    }

    .assessment-subsection h5 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .assessment-subsection .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #495057;
    }

    .assessment-subsection .form-select,
    .assessment-subsection .form-control {
        font-size: 0.875rem;
    }

    .assessment-subsection .form-check-label {
        font-size: 0.875rem;
    }
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
                        <h6>Patient Selection</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <label for="patient_selection" class="form-label">Select Patient:</label>
                                <select id="patient_selection" name="patient_selection" class="form-select">
                                    <option value="new">New Patient</option>
                                    <!-- Patient visit counts are now passed from the controller -->

                                    @foreach($existingPatients as $patient)
                                        @php
                                            $key = $patient->name . '-' . $patient->age . '-' . $patient->gender;
                                            $visitCount = isset($simplifiedVisits[$key]) ? $simplifiedVisits[$key]['count'] : 1;
                                        @endphp
                                        <option value="{{ $patient->id }}">
                                            {{ $patient->name }} ({{ $patient->age }}y, {{ ucfirst($patient->gender) }})
                                            @if($visitCount > 1)
                                                - {{ $visitCount }} visits
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-info-circle"></i> Select "New Patient" for first-time visits or choose an existing patient to access their medical history.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Info (only shown for new patients) -->
                    <div class="medical-form-section" id="new_patient_info">
                        <h6>Patient Information</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="name" class="form-label required">Name:</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ $patientToEdit->name ?? '' }}" required>
                            </div>
                            <div class="col-md-2">
                                <label for="age" class="form-label required">Age:</label>
                                <input type="number" id="age" name="age" class="form-control" value="{{ $patientToEdit->age ?? '' }}" required>
                            </div>
                            <div class="col-md-2">
                                <label for="gender" class="form-label required">Gender:</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="male" {{ isset($patientToEdit) && $patientToEdit->gender == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ isset($patientToEdit) && $patientToEdit->gender == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
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

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Loading Indicator -->
<div id="page-loader" style="display:none;">
    <div class="loader-content">
        <div class="text-center">
            <div class="spinner-border mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="mb-2" style="color: #2c3e50;">Processing Your Request</h5>
            <p class="text-muted mb-0">Our AI is analyzing the patient data...</p>
            <div class="progress mt-3" style="height: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     style="width: 100%; background-color: #DE6262;"></div>
            </div>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS for chat interface -->
<style>
    #chat-messages {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        background-color: #f9f9f9;
    }

    .chat-message {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 12px;
        max-width: 85%;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .user-message {
        background-color: #007bff;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 2px;
    }

    .ai-message {
        background-color: #f0f0f0;
        color: #333;
        border-bottom-left-radius: 2px;
    }

    /* Style for the initial response */
    .response-block {
        background-color: #fff;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e0e0e0;
    }

    /* Style for the response text */
    .response-text {
        white-space: pre-wrap;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.5;
        margin: 0;
        padding: 0;
        font-size: 15px;
        color: #333;
    }

    /* Add a subtle animation for new messages */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chat-message {
        animation: fadeIn 0.3s ease-out;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 5px;
        text-align: right;
    }

    .typing-indicator {
        display: flex;
        padding: 10px 15px;
    }

    .typing-indicator span {
        height: 8px;
        width: 8px;
        background-color: #888;
        border-radius: 50%;
        display: inline-block;
        margin: 0 2px;
        animation: typing 1.4s infinite both;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
        100% { transform: translateY(0); }
    }

    /* Enhanced MedCuraAI Styles for Better Readability */
    .medcura-level1 {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #e8ecef;
    }

    .medcura-level2 {
        background: #f8fafa;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        margin-top: 25px;
        overflow: hidden;
        border: 1px solid #e1e8ed;
    }

    .level-header {
        padding: 18px 25px;
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .level1-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-bottom: none;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .level2-header {
        background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
        color: white;
        border-bottom: none;
        cursor: pointer;
        transition: background 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
    }

    .level2-header:hover {
        background: linear-gradient(135deg, #0056b3 0%, #520dc2 100%);
    }

    /* Section Styling */
    .medcura-section {
        margin: 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .medcura-section:last-child {
        border-bottom: none;
    }

    .section-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #2c3e50;
        padding: 15px 25px;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        border-bottom: 2px solid #DE6262;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-content {
        padding: 20px 25px;
        line-height: 1.7;
        color: #495057;
        background: white;
    }

    .section-content p {
        margin-bottom: 12px;
        font-size: 0.95rem;
    }

    .section-content p:last-child {
        margin-bottom: 0;
    }

    /* Special Section Styling */
    .patient-summary .section-header {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #1565c0;
        border-bottom-color: #2196f3;
    }

    .case-urgency .section-header {
        background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
        color: #e65100;
        border-bottom-color: #ff9800;
    }

    .differential-diagnoses .section-header {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        color: #6a1b9a;
        border-bottom-color: #9c27b0;
    }

    .recommended-tests .section-header {
        background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        color: #2e7d32;
        border-bottom-color: #4caf50;
    }

    .management-plan .section-header {
        background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
        color: #f57f17;
        border-bottom-color: #ffc107;
    }

    .warning-signs .section-header {
        background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        color: #c62828;
        border-bottom-color: #f44336;
    }

    /* Urgency Badge Styling */
    .urgency-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 25px;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 8px 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .urgency-badge.emergency {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        animation: pulse-red 2s infinite;
    }

    .urgency-badge.urgent {
        background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
        color: white;
    }

    .urgency-badge.routine {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        color: white;
    }

    @keyframes pulse-red {
        0% { box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); }
        50% { box-shadow: 0 4px 16px rgba(220, 53, 69, 0.6); }
        100% { box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); }
    }

    /* Bullet Points */
    .bullet-item {
        padding: 6px 0;
        color: #495057;
        font-size: 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .bullet-item::before {
        content: "•";
        color: #DE6262;
        font-weight: bold;
        font-size: 1.1rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* Subsection Headers */
    .subsection-header {
        font-weight: 600;
        color: #2c3e50;
        margin: 15px 0 8px 0;
        padding-bottom: 5px;
        border-bottom: 1px solid #e9ecef;
        font-size: 1rem;
    }

    /* Table Styling */
    .medcura-table {
        margin: 15px 0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .medcura-table table {
        margin: 0;
        width: 100%;
        border-collapse: collapse;
    }

    .medcura-table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        text-align: left;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .medcura-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.9rem;
        vertical-align: top;
    }

    .medcura-table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .medcura-table tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }

    /* Level 2 Toggle */
    .level2-toggle {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .toggle-icon {
        float: right;
        transition: transform 0.3s ease;
        font-size: 1rem;
    }

    .toggle-icon.rotated {
        transform: rotate(180deg);
    }

    .toggle-hint {
        font-size: 0.85rem;
        opacity: 0.8;
        margin-top: 5px;
        font-weight: normal;
    }

    .level2-content {
        padding: 25px;
        background: white;
        border-top: 1px solid #e9ecef;
    }

    .level2-section-header {
        font-weight: 600;
        color: #2c3e50;
        margin: 20px 0 10px 0;
        padding: 10px 0;
        border-bottom: 2px solid #007bff;
        font-size: 1rem;
    }

    /* Mobile Responsiveness for AI Response */
    @media (max-width: 768px) {
        .level-header {
            font-size: 1.1rem;
            padding: 15px 18px;
        }

        .section-header {
            font-size: 1rem;
            padding: 12px 18px;
        }

        .section-content {
            padding: 15px 18px;
        }

        .medcura-table th,
        .medcura-table td {
            padding: 8px 10px;
            font-size: 0.85rem;
        }

        .urgency-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
        }

        .bullet-item {
            font-size: 0.9rem;
        }

        .level2-content {
            padding: 18px;
        }
    }



    /* Additional form layout improvements */
    .medical-form-section .row {
        margin-bottom: 0;
    }

    /* Page header responsiveness */
    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h2 {
        font-size: 2rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: #6c757d;
        margin-bottom: 0;
    }

    /* Form container improvements */
    .medical-form-container {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }

    /* Submit button section */
    .submit-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
        text-align: center;
        border: 1px solid #e9ecef;
    }

    .btn-submit {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.8rem 2.5rem;
        border-radius: 50px;
        font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        transition: all 0.3s ease;
        min-width: 200px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
    }

    .btn-submit:disabled {
        opacity: 0.6;
        transform: none;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
    }

    /* Enhanced mobile responsiveness for header */
    @media (max-width: 768px) {
        .page-header h2 {
            font-size: 1.5rem;
        }

        .page-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .medical-form-container {
            padding: 1rem;
            border-radius: 10px;
        }

        .submit-section {
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .btn-submit {
            width: 100%;
            min-width: auto;
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
        }
    }

    /* Loading indicator improvements */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.95);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    .loader-content {
        text-align: center;
        padding: 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        max-width: 400px;
        margin: 0 1rem;
    }

    .spinner-border {
        color: #DE6262 !important;
    }

    /* Enhanced Chat Section Styling */
    .chat-section {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e8ecef;
    }

    .chat-header {
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f3f4;
        margin-bottom: 1rem;
    }

    .chat-header h6 {
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .chat-messages-container {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }

    .chat-form .input-group {
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .chat-form .form-control {
        border: none;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }

    .chat-form .btn {
        border: none;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        padding: 0.75rem 1.25rem;
        font-weight: 600;
    }

    .chat-form .btn:hover {
        background: linear-gradient(135deg, #c55252 0%, #b04545 100%);
    }

    /* Modal Responsive Enhancements */
    .response-modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .response-modal-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        border: none;
        padding: 1.25rem 1.5rem;
    }

    .response-modal-body {
        padding: 1.5rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    /* Enhanced Responsive Modal Design */
    @media (max-width: 768px) {
        .modal-dialog.modal-xl {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .response-modal-header {
            padding: 1rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .response-modal-header .modal-title {
            font-size: 1.1rem;
            word-break: break-word;
            hyphens: auto;
            line-height: 1.3;
        }

        .response-modal-header > div {
            align-self: flex-end;
        }

        .response-modal-body {
            padding: 1rem;
        }

        /* Fix text display issues in modal body */
        .response-modal-body .ai-response-section,
        .response-modal-body .response-text,
        .response-modal-body .ai-content {
            font-size: 0.9rem !important;
            line-height: 1.5 !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
        }

        .response-modal-body p {
            margin-bottom: 0.8rem !important;
            text-align: left !important;
        }

        .response-modal-body h1, 
        .response-modal-body h2, 
        .response-modal-body h3, 
        .response-modal-body h4,
        .response-modal-body h5,
        .response-modal-body h6 {
            font-size: 1rem !important;
            line-height: 1.3 !important;
            word-break: break-word !important;
            margin-top: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .response-modal-body ul,
        .response-modal-body ol {
            padding-left: 1.2rem !important;
            margin-bottom: 1rem !important;
        }

        .response-modal-body li {
            margin-bottom: 0.5rem !important;
            line-height: 1.4 !important;
            word-break: break-word !important;
        }

        .response-modal-body table {
            font-size: 0.65rem !important;
            display: block !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
        }

        .response-modal-body table th,
        .response-modal-body table td {
            padding: 0.25rem 0.3rem !important;
            min-width: 50px !important;
            line-height: 1.2 !important;
            vertical-align: top !important;
        }

        .response-modal-body table th {
            font-size: 0.6rem !important;
            font-weight: 600 !important;
            background-color: #f8f9fa !important;
        }

        .chat-section {
            padding: 1rem;
        }

        /* Form responsive fixes for OpenAI page */
        .chat-section h1,
        .chat-section h2,
        .chat-section h3,
        .chat-section h4,
        .chat-section h5,
        .chat-section h6 {
            font-size: 1.1rem !important;
            line-height: 1.3 !important;
            margin-bottom: 0.75rem !important;
            word-break: break-word !important;
        }

        .chat-section .form-label,
        .chat-section .col-form-label {
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
            word-break: break-word !important;
        }

        .chat-section .form-control,
        .chat-section .form-select {
            font-size: 0.9rem !important;
            padding: 0.5rem 0.75rem !important;
        }

        .chat-section .btn {
            font-size: 0.85rem !important;
            padding: 0.5rem 1rem !important;
        }

        .chat-section .card-title {
            font-size: 1.1rem !important;
            margin-bottom: 0.75rem !important;
        }

        .chat-section .card-text {
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
        }

        .chat-form .btn {
            padding: 0.75rem 1rem;
        }

        .chat-form .btn span {
            display: none !important;
        }

        .level1-header, .level2-header {
            font-size: 1rem;
            padding: 12px 18px;
            word-break: break-word;
        }

        .level2-content {
            padding: 15px;
        }

        .level2-section-header {
            font-size: 0.95rem;
            margin: 15px 0 8px 0;
            word-break: break-word;
        }

        /* Fix medical section styling for mobile */
        .medical-section .section-header {
            font-size: 0.9rem !important;
            padding: 0.8rem !important;
            word-break: break-word !important;
        }

        .medical-section .section-content {
            padding: 1rem !important;
            font-size: 0.85rem !important;
        }

        /* Fix bullet points for mobile */
        .bullet-item {
            font-size: 0.85rem !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.3rem !important;
        }

        .bullet-item::before {
            margin-top: 0 !important;
        }
    }

    /* Very small screens */
    @media (max-width: 576px) {
        .modal-dialog.modal-xl {
            margin: 0.25rem;
            max-width: calc(100% - 0.5rem);
        }

        .response-modal-header {
            padding: 0.75rem;
        }

        .response-modal-header .modal-title {
            font-size: 1rem !important;
        }

        .response-modal-body {
            padding: 0.75rem;
        }

        /* Extra small screen text fixes */
        .response-modal-body .ai-response-section,
        .response-modal-body .response-text,
        .response-modal-body .ai-content {
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
        }

        .response-modal-body h1, 
        .response-modal-body h2, 
        .response-modal-body h3, 
        .response-modal-body h4,
        .response-modal-body h5,
        .response-modal-body h6 {
            font-size: 0.9rem !important;
        }

        .response-modal-body table {
            font-size: 0.55rem !important;
        }

        .response-modal-body table th,
        .response-modal-body table td {
            padding: 0.15rem 0.2rem !important;
            min-width: 40px !important;
            line-height: 1.1 !important;
        }

        .response-modal-body table th {
            font-size: 0.5rem !important;
            font-weight: 600 !important;
        }

        .chat-section {
            padding: 0.75rem;
        }

        /* Form responsive fixes for very small screens */
        .chat-section h1,
        .chat-section h2,
        .chat-section h3,
        .chat-section h4,
        .chat-section h5,
        .chat-section h6 {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .chat-section .form-label,
        .chat-section .col-form-label {
            font-size: 0.8rem !important;
            margin-bottom: 0.3rem !important;
        }

        .chat-section .form-control,
        .chat-section .form-select {
            font-size: 0.8rem !important;
            padding: 0.4rem 0.6rem !important;
        }

        .chat-section .btn {
            font-size: 0.75rem !important;
            padding: 0.4rem 0.8rem !important;
        }

        .chat-section .card-title {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .chat-section .card-text {
            font-size: 0.8rem !important;
        }

        .level1-header, .level2-header {
            font-size: 0.95rem;
            padding: 10px 15px;
        }

        .section-header {
            font-size: 0.9rem;
            padding: 10px 15px;
        }

        .section-content {
            padding: 12px 15px;
            font-size: 0.9rem;
        }
    }
</style>







    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


<script>
    document.getElementById('openaiForm').addEventListener('submit', function () {
        // Show the Canvas theme's built-in loader using the data-loader-html
        const body = document.body;

        // Create loader overlay with the custom SVG from data-loader-html
        const loaderHTML = body.getAttribute('data-loader-html');
        if (loaderHTML) {
            const loaderOverlay = document.createElement('div');
            loaderOverlay.id = 'canvas-loader-overlay';
            loaderOverlay.innerHTML = loaderHTML;
            loaderOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(44, 62, 80, 0.9);
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
                backdrop-filter: blur(5px);
            `;

            // Style the SVG container
            const svgContainer = loaderOverlay.querySelector('#css3-spinner-svg-pulse-wrapper');
            if (svgContainer) {
                svgContainer.style.cssText = `
                    text-align: center;
                    padding: 20px;
                `;
            }

            document.body.appendChild(loaderOverlay);
        } else {
            // Fallback to our custom loader
            document.getElementById('page-loader').style.display = 'block';
        }
    });

    // Form progress indicator functionality
    document.addEventListener('DOMContentLoaded', function() {
        const progressSteps = document.querySelectorAll('.progress-step');
        const progressBar = document.querySelector('.progress-bar');

        // Find sections by heading text
        function findSectionByHeadingText(text) {
            const headings = document.querySelectorAll('.medical-form-section h6');
            for (let heading of headings) {
                if (heading.textContent.includes(text)) {
                    return heading.closest('.medical-form-section');
                }
            }
            return null;
        }

        const sections = {
            'patient': findSectionByHeadingText('Patient'),
            'vitals': findSectionByHeadingText('Vitals'),
            'symptoms': findSectionByHeadingText('Symptoms'),
            'diagnosis': findSectionByHeadingText('Diagnosis')
        };

        // Function to update progress
        function updateProgress(step) {
            let progress = 0;
            let activeFound = false;

            progressSteps.forEach((stepEl, index) => {
                const stepName = stepEl.getAttribute('data-step');
                const stepIcon = stepEl.querySelector('.step-icon');

                if (stepName === step) {
                    stepEl.classList.add('active');
                    // Apply active styles directly
                    if (stepIcon) {
                        stepIcon.style.backgroundColor = '#DE6262';
                        stepIcon.style.color = 'white';
                        stepIcon.style.borderColor = '#DE6262';
                        stepIcon.style.boxShadow = '0 0 0 5px rgba(222, 98, 98, 0.2)';
                    }
                    activeFound = true;
                    progress = (index + 1) * 20; // 20% per step
                } else if (!activeFound) {
                    stepEl.classList.add('completed');
                    stepEl.classList.remove('active');
                    // Apply completed styles directly
                    if (stepIcon) {
                        stepIcon.style.backgroundColor = '#DE6262';
                        stepIcon.style.color = 'white';
                        stepIcon.style.borderColor = '#DE6262';
                        stepIcon.style.boxShadow = 'none';
                    }
                } else {
                    stepEl.classList.remove('active', 'completed');
                    // Apply inactive styles directly
                    if (stepIcon) {
                        stepIcon.style.backgroundColor = '#f8f9fa';
                        stepIcon.style.color = '#6c757d';
                        stepIcon.style.borderColor = '#e9ecef';
                        stepIcon.style.boxShadow = 'none';
                    }
                }
            });

            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
        }

        // Add click event to step icons for navigation
        progressSteps.forEach(step => {
            step.addEventListener('click', function() {
                const stepName = this.getAttribute('data-step');
                updateProgress(stepName);

                // Scroll to the corresponding section
                if (sections[stepName]) {
                    sections[stepName].scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Initialize with first step active
        updateProgress('patient');

        // Add scroll spy functionality
        window.addEventListener('scroll', function() {
            const scrollPosition = window.scrollY + 200; // Offset for better detection

            // Determine which section is currently in view
            let currentSection = 'patient';

            Object.entries(sections).forEach(([name, section]) => {
                if (section && section.offsetTop <= scrollPosition) {
                    currentSection = name;
                }
            });

            updateProgress(currentSection);
        });

        // Quick test buttons functionality
        const quickTestButtons = document.querySelectorAll('.quick-test');
        const testResultsTextarea = document.querySelector('textarea[name="test_results"]');

        if (quickTestButtons.length > 0 && testResultsTextarea) {
            quickTestButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const testType = this.getAttribute('data-test');
                    let template = '';

                    // Add different templates based on test type
                    switch(testType) {
                        case 'CBC':
                            template = 'CBC: WBC 7,500/μL, RBC 4.8 M/μL, Hgb 14.2 g/dL, Hct 42%, Plt 250,000/μL';
                            break;
                        case 'CRP':
                            template = 'CRP: 0.8 mg/L (Normal range: 0-1.0 mg/L)';
                            break;
                        case 'Urinalysis':
                            template = 'Urinalysis: Color - Yellow, Clarity - Clear, pH 6.0, Specific gravity 1.018, Negative for protein, glucose, ketones, blood, and nitrites';
                            break;
                        case 'X-ray':
                            template = 'Chest X-ray: No acute cardiopulmonary process. Heart size normal. Lungs clear.';
                            break;
                        case 'CT Scan':
                            template = 'CT Scan: No evidence of acute intracranial abnormality. No mass effect or midline shift.';
                            break;
                        default:
                            template = testType + ': ';
                    }

                    // Add the template to the textarea
                    const currentText = testResultsTextarea.value;
                    if (currentText && !currentText.endsWith('\n')) {
                        testResultsTextarea.value += '\n';
                    }

                    testResultsTextarea.value += (currentText ? '' : '') + template;
                    testResultsTextarea.focus();
                });
            });
        }
    });

</script>


@if (session('openai_result'))
    <script>
         document.addEventListener('DOMContentLoaded', function () {
            // Show the modal with the full response immediately
            const modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();

            // Hide the page loader once the modal is shown
            // Hide the Canvas theme loader
            const canvasLoader = document.getElementById('canvas-loader-overlay');
            if (canvasLoader) {
                canvasLoader.remove();
            }

            // Also hide the fallback loader
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

            // Set up the follow-up form handler
            setupFollowUpChat();
        });
    </script>
@endif

<!-- Chat functionality script -->
<script>
    function setupFollowUpChat() {
        const followUpForm = document.getElementById('follow-up-form');
        const chatMessages = document.getElementById('chat-messages');

        if (followUpForm) {
            followUpForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const messageInput = document.getElementById('follow-up-message');
                const message = messageInput.value.trim();
                const conversationId = document.getElementById('conversation-id').value;

                if (!message) return;

                // Add user message to chat
                addChatMessage(message, 'user');

                // Clear input
                messageInput.value = '';

                // Show typing indicator
                const typingIndicator = addTypingIndicator();

                // Send to server
                fetch('{{ route("openai.follow-up") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: conversationId
                    })
                })
                .then(response => {
                    // Check if response is ok before parsing JSON
                    if (!response.ok) {
                        // If it's an API key error (401 Unauthorized)
                        if (response.status === 401) {
                            throw new Error('API_KEY_ERROR');
                        }
                        throw new Error('SERVER_ERROR');
                    }
                    return response.json();
                })
                .then(data => {
                    // Remove typing indicator
                    removeTypingIndicator(typingIndicator);

                    if (data.success) {
                        // Add AI response with typing animation
                        addChatMessage(data.message, 'ai');

                        // Update conversation ID if needed
                        if (data.conversation_id) {
                            document.getElementById('conversation-id').value = data.conversation_id;
                        }
                    } else if (data.api_key_error) {
                        // Show API key error with special styling
                        addErrorMessage(data.message || 'OpenAI API key is invalid or expired. Please contact the administrator.', true);

                        // Also show a modal with more information
                        showApiKeyErrorModal();
                    } else {
                        // Show regular error
                        addErrorMessage(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    // Remove typing indicator
                    removeTypingIndicator(typingIndicator);

                    if (error.message === 'API_KEY_ERROR') {
                        // Show API key error with special styling
                        addErrorMessage('OpenAI API key is invalid or expired. Please contact the administrator.', true);

                        // Also show a modal with more information
                        showApiKeyErrorModal();
                    } else {
                        // Show regular error
                        addErrorMessage('Failed to connect to the server. Please try again later.');
                    }
                    console.error('Error:', error);
                });
            });
        }
    }

    // Function to simulate typing effect
    function typeText(element, text, speed = 10) {
        let i = 0;
        element.textContent = '';

        function typing() {
            if (i < text.length) {
                // Add character by character
                element.textContent += text.charAt(i);
                i++;

                // Scroll to bottom as text is being typed
                const container = element.closest('.modal-body');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }

                // Adjust typing speed based on punctuation
                let delay = speed;
                const char = text.charAt(i-1);
                if (char === '.' || char === '!' || char === '?') {
                    delay = speed * 8; // Pause longer at end of sentences
                } else if (char === ',' || char === ';' || char === ':') {
                    delay = speed * 5; // Pause at commas and other punctuation
                } else if (char === '\n') {
                    delay = speed * 3; // Pause at new lines
                }

                setTimeout(typing, delay);
            }
        }

        typing();
    }

    function addChatMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${sender}-message`;

        // Create message content
        if (sender === 'ai') {
            const pre = document.createElement('pre');
            pre.className = 'response-text';
            pre.style.margin = '0';
            pre.style.whiteSpace = 'pre-wrap';

            // Add empty pre element first
            messageDiv.appendChild(pre);

            // Add timestamp
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const now = new Date();
            timeDiv.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            messageDiv.appendChild(timeDiv);

            // Add to chat
            document.getElementById('chat-messages').appendChild(messageDiv);

            // Format the response to remove markdown symbols and unwanted sections
            let formattedResponse = content
                // Remove markdown formatting
                .replace(/#{1,6}\s/g, '')  // Remove heading markers
                .replace(/\*\*/g, '')      // Remove bold markers
                .replace(/\*/g, '')        // Remove italic markers
                .replace(/- /g, '• ')      // Replace dashes with bullets

                // Remove introduction and conclusion sections
                .replace(/^Based on the provided.*?guidelines,.*?\n\n/s, '')  // Remove intro
                .replace(/^As a.*?specialist:.*?\n\n/s, '')                  // Remove specialty intro
                .replace(/^.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS)/s, '')          // Remove everything before section A
                .replace(/^.*?(?=A\)\s*DIAGNOS[IE]S)/s, '')                  // Alternative section A format
                .replace(/\n\nConclusion:.*$/s, '')                          // Remove conclusion
                .replace(/\n\nNote:.*$/s, '')                                // Remove notes at the end
                .replace(/^Note:.*\n\n/s, '')                                // Remove notes at the beginning
                .replace(/\n\nIn summary.*$/s, '')                           // Remove summary
                .replace(/\n\nSummary.*$/s, '')                                // Remove notes at the beginning

                // Clean up any remaining formatting issues
                .replace(/\n{3,}/g, '\n\n')                                  // Replace multiple newlines with double newlines
                .trim();                                                     // Remove leading/trailing whitespace

            // Start typing animation
            typeText(pre, formattedResponse);
        } else {
            // For user messages, show immediately
            messageDiv.textContent = content;

            // Add timestamp
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const now = new Date();
            timeDiv.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            messageDiv.appendChild(timeDiv);

            // Add to chat
            document.getElementById('chat-messages').appendChild(messageDiv);
        }

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
    }

    function addTypingIndicator() {
        const id = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing-indicator';
        typingDiv.id = id;

        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('span');
            typingDiv.appendChild(dot);
        }

        document.getElementById('chat-messages').appendChild(typingDiv);

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;

        return id;
    }

    function removeTypingIndicator(id) {
        const indicator = document.getElementById(id);
        if (indicator) {
            indicator.remove();
        }
    }

    function addErrorMessage(message, isApiKeyError = false) {
        const errorDiv = document.createElement('div');
        errorDiv.className = isApiKeyError ? 'alert alert-danger' : 'alert alert-warning';

        if (isApiKeyError) {
            // Create icon element
            const icon = document.createElement('i');
            icon.className = 'fas fa-exclamation-triangle me-2';
            errorDiv.appendChild(icon);

            // Create strong element for the title
            const strong = document.createElement('strong');
            strong.textContent = 'API Key Error: ';
            errorDiv.appendChild(strong);

            // Add the message text
            const textNode = document.createTextNode(message);
            errorDiv.appendChild(textNode);
        } else {
            errorDiv.textContent = message;
        }

        document.getElementById('chat-messages').appendChild(errorDiv);

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;

        // Only auto-remove regular errors, not API key errors
        if (!isApiKeyError) {
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    }

    function showApiKeyErrorModal() {
        // Create modal if it doesn't exist
        if (!document.getElementById('apiKeyErrorModal')) {
            const modalHtml = `
                <div class="modal fade" id="apiKeyErrorModal" tabindex="-1" aria-labelledby="apiKeyErrorModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="apiKeyErrorModalLabel" style="word-break: break-word; line-height: 1.3; font-size: 1.1rem;">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    OpenAI API Key Error
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="word-break: break-word; line-height: 1.5; font-size: 0.95rem;">
                                <p style="margin-bottom: 1rem;">The OpenAI API key appears to be invalid or expired. This means:</p>
                                <ul style="padding-left: 1.2rem; margin-bottom: 1rem;">
                                    <li style="margin-bottom: 0.5rem; word-break: break-word;">You won't be able to get AI-powered responses</li>
                                    <li style="margin-bottom: 0.5rem; word-break: break-word;">Medical analysis features will be unavailable</li>
                                    <li style="margin-bottom: 0.5rem; word-break: break-word;">Chat functionality will not work</li>
                                </ul>
                                <p style="margin-bottom: 0;">Please contact the system administrator to update the API key.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);
        }

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('apiKeyErrorModal'));
        modal.show();
    }

    /**
     * Format table from array of table rows
     */
    function formatTable(tableRows) {
        if (!tableRows || tableRows.length === 0) return '';

        let table = '<table class="table table-striped mt-3">';
        let isFirstRow = true;
        let headerAdded = false;

        for (const row of tableRows) {
            let cells = [];

            // Handle different table formats
            if (row.includes('|')) {
                // Pipe-separated format
                cells = row.split('|').map(cell => cell.trim()).filter(cell => cell);
            } else if (row.match(/^(Rank|1|2|3|4|5)\s+/)) {
                // Diagnosis table format without pipes
                const match = row.match(/^(\d+|Rank)\s+(.*?)\s+(\d+%)\s+(.*?)$/);
                if (match) {
                    cells = [match[1], match[2], match[3], match[4]];
                } else {
                    // Try to parse the concatenated format
                    const diagnosisMatch = row.match(/^(\d+)(.*?)(\d+%)(.*?)$/);
                    if (diagnosisMatch) {
                        cells = [diagnosisMatch[1], diagnosisMatch[2], diagnosisMatch[3], diagnosisMatch[4]];
                    }
                }
            } else if (row.includes('RankDiagnosis')) {
                // Header row for the concatenated format
                cells = ['Rank', 'Diagnosis', 'Probability (%)', 'Clinical Reasoning'];
            }

            if (cells.length === 0) continue;

            // Check if this should be a header row
            if (!headerAdded && (cells.some(cell => cell.toLowerCase().includes('rank') || cell.toLowerCase().includes('diagnosis')) || isFirstRow)) {
                table += '<thead><tr>';
                cells.forEach(cell => {
                    table += `<th>${cell}</th>`;
                });
                table += '</tr></thead><tbody>';
                headerAdded = true;
                isFirstRow = false;
            } else {
                // Data row
                table += '<tr>';
                cells.forEach((cell, index) => {
                    // Check if this is a probability cell
                    if (cell.includes('%')) {
                        cell = `<span class="probability">${cell}</span>`;
                    }
                    table += `<td>${cell}</td>`;
                });
                table += '</tr>';
            }
        }

        table += '</tbody></table>';
        return table;
    }

    /**
     * Format AI response text with proper HTML formatting
     */
    function formatAIResponse(text) {
        if (!text) return '';

        // Clean up text: remove excessive whitespace and normalize line breaks
        let cleanedText = text
            .replace(/\r\n/g, '\n')  // Normalize line endings
            .replace(/\n{3,}/g, '\n\n')  // Replace 3+ line breaks with 2
            .replace(/[ \t]{2,}/g, ' ')  // Replace multiple spaces/tabs with single space
            .replace(/^\s+|\s+$/gm, '')  // Trim whitespace from start/end of each line
            .trim();

        // Remove the Sources section from the text before formatting
        const sourcesMatch = cleanedText.match(/(📚\s*SOURCES:|Sources:)([\s\S]*?)(?:$|(?=\n\n\w))/i);
        if (sourcesMatch) {
            cleanedText = cleanedText.replace(sourcesMatch[0], '').trim();
        }

        // Enhanced formatting for any medical response structure
        let formattedHTML = formatMedicalResponse(cleanedText);

        return formattedHTML;
    }

    function formatMedicalResponse(text) {
        if (!text) return '';

        // Professional medical formatting for structured response
        let enhancedText = text
            // Handle the initial CASE URGENCY format at the top
            .replace(/^CASE\s+URGENCY:\s*(EMERGENCY|URGENT|ROUTINE)/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">$1</span></div>')

            // Patient Case Summary Section
            .replace(/^📋\s*PATIENT\s+CASE\s+SUMMARY:?$/gm, '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')

            // Case Urgency Section
            .replace(/^🚨\s*CASE\s+URGENCY:?$/gm, '</div></div><div class="medcura-section case-urgency"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">')

            // A) Differential Diagnosis Section - Handle with or without dashes
            .replace(/^(-{0,3}A\)?\s*(DIFFERENTIAL\s+)?DIAGNOSIS.*?:?|🔬\s*.*?DIAGNOSIS.*?:?)$/gmi, '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header"><i class="fas fa-microscope"></i> A) DIFFERENTIAL DIAGNOSIS</h4><div class="section-content">')

            // B) Investigations Section - Handle with or without dashes
            .replace(/^(-{0,3}B\)?\s*.*?(RECOMMENDED\s+)?(INVESTIGATIONS?|TESTS?|DIAGNOSTIC|WORKUP).*?:?)$/gmi, '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header"><i class="fas fa-vials"></i> B) RECOMMENDED INVESTIGATIONS</h4><div class="section-content">')

            // C) Treatment/Management Section - Handle with or without dashes
            .replace(/^(-{0,3}C\)?\s*.*?(TREATMENT|MANAGEMENT|PLAN|THERAPY|INTERVENTION).*?:?)$/gmi, '</div></div><div class="medcura-section management-plan"><h4 class="section-header"><i class="fas fa-pills"></i> C) MANAGEMENT RECOMMENDATIONS</h4><div class="section-content">')

            // D) Warning Signs Section - Handle with or without dashes
            .replace(/^(-{0,3}D\)?\s*WARNING\s+SIGNS.*?:?|⚠️\s*WARNING\s+SIGNS.*?:?)$/gmi, '</div></div><div class="medcura-section warning-signs"><h4 class="section-header"><i class="fas fa-exclamation-triangle"></i> D) WARNING SIGNS TO MONITOR</h4><div class="section-content">')

            // Handle Summary Format Headers
            .replace(/^OVERALL\s+HEALTH\s+TRAJECTORY:?$/gmi, '<div class="medcura-section patient-summary"><h4 class="section-header"><i class="fas fa-chart-line"></i> OVERALL HEALTH TRAJECTORY</h4><div class="section-content">')

            .replace(/^KEY\s+MEDICAL\s+ISSUES\s+IDENTIFIED:?$/gmi, '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header"><i class="fas fa-stethoscope"></i> KEY MEDICAL ISSUES IDENTIFIED</h4><div class="section-content">')

            .replace(/^IMPORTANT\s+TRENDS\s+IN\s+SYMPTOMS\s+OR\s+TEST\s+RESULTS:?$/gmi, '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header"><i class="fas fa-chart-area"></i> IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS</h4><div class="section-content">')

            .replace(/^TREATMENT\s+EFFECTIVENESS\s+BASED\s+ON\s+VISIT\s+PROGRESSION:?$/gmi, '</div></div><div class="medcura-section management-plan"><h4 class="section-header"><i class="fas fa-clipboard-check"></i> TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION</h4><div class="section-content">')

            .replace(/^RECOMMENDATIONS\s+FOR\s+FUTURE\s+CARE:?$/gmi, '</div></div><div class="medcura-section warning-signs"><h4 class="section-header"><i class="fas fa-user-md"></i> RECOMMENDATIONS FOR FUTURE CARE</h4><div class="section-content">')

            // Handle Sub-sections within the main sections
            .replace(/^(Status:|Reason:|Symptoms:|Vital Signs:|Laboratory Findings:|Immediate Diagnostic Steps:|Critical Interventions:|Long-term Care Considerations:|Lifestyle and Risk Factor Modification:)/gmi, '<div class="subsection-header">$1</div>')

            // General fallback for any remaining letter-based headers
            .replace(/^([A-D]\)\s*[A-Z\s]{5,}:?)$/gmi, function(match, p1) {
                let sectionClass = 'medcura-section';
                let headerText = match.replace(/^[A-D]\)\s*/, '').replace(/:$/, '');
                let letterPrefix = match.charAt(0);
                let icon = '';

                switch(letterPrefix) {
                    case 'A': icon = '<i class="fas fa-microscope"></i>'; sectionClass += ' differential-diagnoses'; break;
                    case 'B': icon = '<i class="fas fa-vials"></i>'; sectionClass += ' recommended-tests'; break;
                    case 'C': icon = '<i class="fas fa-pills"></i>'; sectionClass += ' management-plan'; break;
                    case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; sectionClass += ' warning-signs'; break;
                }

                return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letterPrefix}) ${headerText}</h4><div class="section-content">`;
            })

            // Doctor's Note Section
            .replace(/^🧠\s*DOCTOR'S\s+NOTE:?$/gm, '</div></div><div class="medcura-section doctor-note-section"><h4 class="section-header">🧠 DOCTOR\'S NOTE</h4><div class="section-content">');

        // Split the text into lines for processing
        let lines = enhancedText.split('\n');
        let formatted = '';
        let inList = false;
        let listType = '';
        let inTable = false;
        let tableRows = [];
        let sectionOpened = false;

        // Process each line
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();

            // Skip empty lines
            if (!line) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (inTable) {
                    formatted += formatTable(tableRows);
                    inTable = false;
                    tableRows = [];
                }
                formatted += '<br>';
                continue;
            }

            // Skip processing if line is already HTML (from our replacement above)
            if (line.startsWith('<div') || line.startsWith('</div>') || line.startsWith('<h') || line.startsWith('<hr')) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (inTable) {
                    formatted += formatTable(tableRows);
                    inTable = false;
                    tableRows = [];
                }
                formatted += line;
                if (line.includes('section-content')) {
                    sectionOpened = true;
                }
                continue;
            }

            // Handle table data (pipe-separated)
            if (line.includes('|') && line.split('|').length >= 3) {
                if (!inTable) {
                    if (inList) {
                        formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        inList = false;
                    }
                    inTable = true;
                }
                tableRows.push(line);
                continue;
            } else if (inTable) {
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }

            // Handle numbered lists
            if (/^\d+[\.\)]\s+/.test(line)) {
                if (!inList || listType !== 'ol') {
                    if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    formatted += '<ol class="medical-list">';
                    inList = true;
                    listType = 'ol';
                }
                formatted += '<li class="bullet-item">' + line.replace(/^\d+[\.\)]\s+/, '') + '</li>';
                continue;
            }

            // Handle bullet points
            if (/^[•\-\*]\s+/.test(line) || /^\s*[\-\*]\s+/.test(line)) {
                if (!inList || listType !== 'ul') {
                    if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    formatted += '<ul class="medical-list">';
                    inList = true;
                    listType = 'ul';
                }
                formatted += '<li class="bullet-item">' + line.replace(/^[•\-\*\s]+/, '') + '</li>';
                continue;
            } else if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }

            // Handle urgency levels with special styling
            if (line.match(/^\s*(EMERGENCY|URGENT|ROUTINE)\s*$/i)) {
                const urgency = line.toLowerCase();
                formatted += `<div class="urgency-badge ${urgency}">${line.toUpperCase()}</div>`;
                continue;
            }

            // Regular paragraph
            if (!sectionOpened) {
                // If no section is opened yet, start with a default section
                formatted += '<div class="medcura-section"><div class="section-content">';
                sectionOpened = true;
            }
            formatted += '<p>' + line + '</p>';
        }

        // Close any remaining lists or tables
        if (inList) {
            formatted += listType === 'ul' ? '</ul>' : '</ol>';
        }
        if (inTable) {
            formatted += formatTable(tableRows);
        }

        // Close any open sections
        if (sectionOpened) {
            formatted += '</div></div>';
        }

        return formatted;
    }

    function formatTable(rows) {
        if (!rows || rows.length === 0) return '';

        let tableHtml = '<div class="medcura-table"><table class="table table-striped table-hover">';

        for (let i = 0; i < rows.length; i++) {
            let cells = rows[i].split('|').map(cell => cell.trim()).filter(cell => cell);

            if (cells.length < 2) continue;

            tableHtml += '<tr>';

            if (i === 0) {
                // Header row
                for (let cell of cells) {
                    tableHtml += `<th class="table-header-cell">${cell}</th>`;
                }
            } else {
                // Data rows
                for (let cell of cells) {
                    tableHtml += `<td>${cell}</td>`;
                }
            }

            tableHtml += '</tr>';
        }

        tableHtml += '</table></div>';
        return tableHtml;
    }

    function formatLevel1(text) {
        if (!text) return '';

        let formatted = '<div class="medcura-level1">';

        // Handle Level 1 header
        text = text.replace(/🟢\s*LEVEL\s+1:\s*QUICK\s+CLINICAL\s+SUMMARY/i,
            '<div class="level-header level1-header">🟢 QUICK CLINICAL SUMMARY</div>');

        // Patient Summary Section
        text = text.replace(/📋\s*PATIENT\s+SUMMARY:/i,
            '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT SUMMARY</h4><div class="section-content">');

        // Case Urgency Section
        text = text.replace(/🚨\s*CASE\s+URGENCY:/i,
            '</div></div><div class="medcura-section case-urgency"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">');

        // Top 3 Differential Diagnoses Section
        text = text.replace(/🔍\s*TOP\s+3\s+DIFFERENTIAL\s+DIAGNOSES:/i,
            '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header">🔍 TOP 3 DIFFERENTIAL DIAGNOSES</h4><div class="section-content">');

        // Recommended Tests Section
        text = text.replace(/🧪\s*RECOMMENDED\s+TESTS:/i,
            '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header">🧪 RECOMMENDED TESTS</h4><div class="section-content">');

        // Initial Management Plan Section
        text = text.replace(/💊\s*INITIAL\s+MANAGEMENT\s+PLAN:/i,
            '</div></div><div class="medcura-section management-plan"><h4 class="section-header">💊 INITIAL MANAGEMENT PLAN</h4><div class="section-content">');

        // Warning Signs Section
        text = text.replace(/⚠️\s*WARNING\s+SIGNS:/i,
            '</div></div><div class="medcura-section warning-signs"><h4 class="section-header">⚠️ WARNING SIGNS</h4><div class="section-content">');

        // Process the text line by line
        let lines = text.split('\n');
        let processedText = '';
        let inTable = false;
        let tableRows = [];

        for (let line of lines) {
            // Skip if already HTML
            if (line.includes('<div') || line.includes('</div>') || line.includes('<h4')) {
                if (inTable) {
                    processedText += formatMedCuraTable(tableRows);
                    inTable = false;
                    tableRows = [];
                }
                processedText += line + '\n';
                continue;
            }

            // Handle table rows (for differential diagnoses)
            if (line.includes('|') && line.split('|').length >= 4) {
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                tableRows.push(line);
                continue;
            } else if (inTable) {
                processedText += formatMedCuraTable(tableRows);
                inTable = false;
                tableRows = [];
            }

            // Handle bullet points
            if (/^[\s]*[•\-\*]\s+(.+)$/.test(line)) {
                const itemText = line.replace(/^[\s]*[•\-\*]\s+(.+)$/, '$1');
                processedText += `<div class="bullet-item">• ${itemText}</div>\n`;
            }
            // Handle bold subsections
            else if (/^\*\*(.+?)\*\*/.test(line)) {
                const boldText = line.replace(/^\*\*(.+?)\*\*/, '<strong>$1</strong>');
                processedText += `<div class="subsection-header">${boldText}</div>\n`;
            }
            // Handle urgency levels
            else if (/^\*\*(EMERGENCY|URGENT|ROUTINE)\*\*/.test(line)) {
                const urgencyLevel = line.match(/^\*\*(EMERGENCY|URGENT|ROUTINE)\*\*/)[1];
                const urgencyClass = urgencyLevel.toLowerCase();
                processedText += `<div class="urgency-badge ${urgencyClass}">${urgencyLevel}</div>\n`;
            }
            // Regular text
            else if (line.trim()) {
                processedText += `<p>${line}</p>\n`;
            }
        }

        // Close any remaining table
        if (inTable) {
            processedText += formatMedCuraTable(tableRows);
        }

        formatted += processedText;
        formatted += '</div></div></div>'; // Close last section and level1

        return formatted;
    }

    function formatLevel2(text) {
        if (!text) return '';

        // Create collapsible section
        let formatted = `
            <div class="medcura-level2">
                <div class="level2-toggle" onclick="toggleLevel2()">
                    <div class="level-header level2-header">
                        🔵 DETAILED MEDICAL REPORT
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="toggle-hint">Click to Expand</div>
                </div>
                <div class="level2-content" id="level2-content" style="display: none;">
        `;

        // Remove the header from text
        text = text.replace(/🔵\s*DETAILED\s+MEDICAL\s+REPORT.*?\n/i, '');

        // Process sections
        text = text.replace(/\*\*([^*]+?)\*\*/g, '<div class="level2-section-header">$1</div>');

        // Handle bullet points
        text = text.replace(/^[\s]*[•\-\*]\s+(.+)$/gm, '<div class="bullet-item">• $1</div>');

        // Handle paragraphs
        let lines = text.split('\n');
        let processedText = '';

        for (let line of lines) {
            if (line.includes('<div class="level2-section-header">') ||
                line.includes('<div class="bullet-item">')) {
                processedText += line + '\n';
            } else if (line.trim()) {
                processedText += `<p>${line}</p>\n`;
            }
        }

        formatted += processedText;
        formatted += '</div></div>';

        return formatted;
    }

    function formatMedCuraTable(rows) {
        if (!rows || rows.length === 0) return '';

        let html = '<div class="medcura-table"><table class="table table-striped table-hover">';

        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].split('|').map(cell => cell.trim());
            const tag = i === 0 ? 'th' : 'td';
            const rowClass = i === 0 ? 'table-header' : '';

            html += `<tr class="${rowClass}">`;
            for (let cell of cells) {
                if (i === 0) {
                    html += `<${tag} class="table-header-cell">${cell}</${tag}>`;
                } else {
                    html += `<${tag}>${cell}</${tag}>`;
                }
            }
            html += '</tr>';
        }

        html += '</table></div>';
        return html;
    }

    function toggleLevel2() {
        const content = document.getElementById('level2-content');
        const icon = document.querySelector('.toggle-icon');

        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.textContent = '▲';
        } else {
            content.style.display = 'none';
            icon.textContent = '▼';
        }
    }

    // Legacy function - keeping the rest of the old function for compatibility
    function formatAIResponseOld(text) {

        // Remove the Sources section from the text before formatting
        const sourcesMatch = cleanedText.match(/(📚\s*SOURCES:|Sources:)([\s\S]*?)(?:$|(?=\n\n\w))/i);
        if (sourcesMatch) {
            cleanedText = cleanedText.replace(sourcesMatch[0], '').trim();
        }

        // Professional medical formatting for structured response
        let enhancedText = cleanedText
            // Handle the initial CASE URGENCY format at the top
            .replace(/^CASE\s+URGENCY:\s*(EMERGENCY|URGENT|ROUTINE)/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">$1</span></div>')

            // Fix the concatenated diagnosis table format
            .replace(/RankDiagnosisProbability \(%\)Clinical Reasoning-+/g, 'Rank|Diagnosis|Probability (%)|Clinical Reasoning')
            .replace(/(\d+)([A-Z][^0-9]+?)(\d+%)([^0-9]+?)(?=\d|$)/g, '$1|$2|$3|$4\n')

            // Handle section separators
            .replace(/^---$/gm, '<div class="section-break"></div>')

            // Patient Case Summary Section
            .replace(/^📋\s*PATIENT\s+CASE\s+SUMMARY:?$/gm, '<div class="medical-section patient-section"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')

            // Case Urgency Section
            .replace(/^🚨\s*CASE\s+URGENCY:?$/gm, '</div></div><div class="medical-section urgency-section"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">')

            // A) Differential Diagnosis Section - Handle with or without dashes
            .replace(/^(-{0,3}A\)?\s*(DIFFERENTIAL\s+)?DIAGNOSIS.*?:?|🔬\s*.*?DIAGNOSIS.*?:?)$/gmi, '</div></div><div class="medical-section diagnosis-section"><h4 class="section-header"><i class="fas fa-microscope"></i> A) DIFFERENTIAL DIAGNOSIS</h4><div class="section-content">')

            // B) Investigations Section - Handle with or without dashes
            .replace(/^(-{0,3}B\)?\s*.*?(RECOMMENDED\s+)?(INVESTIGATIONS?|TESTS?|DIAGNOSTIC|WORKUP).*?:?)$/gmi, '</div></div><div class="medical-section investigations-section"><h4 class="section-header"><i class="fas fa-vials"></i> B) RECOMMENDED INVESTIGATIONS</h4><div class="section-content">')

            // C) Treatment/Management Section - Handle with or without dashes
            .replace(/^(-{0,3}C\)?\s*.*?(TREATMENT|MANAGEMENT|PLAN|THERAPY|INTERVENTION).*?:?)$/gmi, '</div></div><div class="medical-section treatment-section"><h4 class="section-header"><i class="fas fa-pills"></i> C) MANAGEMENT RECOMMENDATIONS</h4><div class="section-content">')

            // D) Warning Signs Section - Handle with or without dashes
            .replace(/^(-{0,3}D\)?\s*WARNING\s+SIGNS.*?:?|⚠️\s*WARNING\s+SIGNS.*?:?)$/gmi, '</div></div><div class="medical-section warnings-section"><h4 class="section-header"><i class="fas fa-exclamation-triangle"></i> D) WARNING SIGNS TO MONITOR</h4><div class="section-content">')

            // Specific pattern for the exact format: "---B) RECOMMENDED INVESTIGATIONS:"
            .replace(/^---([ABCD])\)\s*(.+?):\s*$/gmi, function(match, letter, text) {
                let icon = '';
                let sectionClass = 'medical-section';

                switch(letter) {
                    case 'A': icon = '<i class="fas fa-microscope"></i>'; break;
                    case 'B': icon = '<i class="fas fa-vials"></i>'; break;
                    case 'C': icon = '<i class="fas fa-pills"></i>'; break;
                    case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; break;
                }

                return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letter}) ${text.toUpperCase()}</h4><div class="section-content">`;
            })

            // General fallback for any remaining letter-based headers
            .replace(/^([A-D]\)\s*[A-Z\s]{5,}:?)$/gmi, function(match, p1) {
                let sectionClass = 'medical-section';
                let headerText = match.replace(/^[A-D]\)\s*/, '').replace(/:$/, '');
                let letterPrefix = match.charAt(0);
                let icon = '';

                switch(letterPrefix) {
                    case 'A': icon = '<i class="fas fa-microscope"></i>'; break;
                    case 'B': icon = '<i class="fas fa-vials"></i>'; break;
                    case 'C': icon = '<i class="fas fa-pills"></i>'; break;
                    case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; break;
                }

                return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letterPrefix}) ${headerText}</h4><div class="section-content">`;
            })

            // Doctor's Note Section
            .replace(/^🧠\s*DOCTOR'S\s+NOTE:?$/gm, '</div></div><div class="medical-section doctor-note-section"><h4 class="section-header">🧠 DOCTOR\'S NOTE</h4><div class="section-content">')

            // Sources Section (if present)
            .replace(/^📚\s*SOURCES:?$/gm, '</div></div><div class="medical-section sources-section"><h4 class="section-header">📚 SOURCES</h4><div class="section-content">');

        // Split the text into lines
        let lines = enhancedText.split('\n');
        let formatted = '';
        let inList = false;
        let listType = '';
        let inTable = false;
        let tableRows = [];

        // Process each line
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];

            // Skip processing if line is already HTML (from our replacement above)
            if (line.startsWith('<div') || line.startsWith('</div>') || line.startsWith('<h') || line.startsWith('<hr')) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (inTable) {
                    formatted += formatTable(tableRows);
                    inTable = false;
                    tableRows = [];
                }
                formatted += line;
                continue;
            }

            // Check for concatenated diagnosis table
            if (line.includes('RankDiagnosis') && line.includes('Clinical Reasoning')) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                // Create proper table header
                tableRows.push('Rank|Diagnosis|Probability (%)|Clinical Reasoning');
                continue;
            }
            // Check for the concatenated data row (like: 1Abdominal Aortic Aneurysm (AAA)70%Given the symptom...)
            else if (line.match(/^\d+[A-Z][^0-9]*\d+%/)) {
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                    tableRows.push('Rank|Diagnosis|Probability (%)|Clinical Reasoning');
                }
                // Parse the concatenated format
                const match = line.match(/^(\d+)([^0-9]*?)(\d+%)(.*)$/);
                if (match) {
                    const formattedRow = `${match[1]}|${match[2].trim()}|${match[3]}|${match[4].trim()}`;
                    tableRows.push(formattedRow);
                }
                continue;
            }
            // Check for table rows (contains | or table-like structure)
            else if ((line.includes('|') && line.split('|').length > 2) ||
                (line.match(/^(Rank|1|2|3|4|5)\s+(.*?)\s+(\d+%)\s+(.*?)$/))) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                tableRows.push(line);
                continue;
            } else if (inTable) {
                // End of table
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }

            // Check for headers (# Header)
            if (/^#{1,6}\s+(.+)$/.test(line)) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                const headerLevel = line.match(/^(#{1,6})\s+/)[1].length;
                const headerText = line.replace(/^#{1,6}\s+(.+)$/, '$1');
                formatted += `<h${headerLevel}>${headerText}</h${headerLevel}>`;
            }
            // Check for bullet points (* Item or - Item or • Item)
            else if (/^[\s]*[\*\-•]\s+(.+)$/.test(line)) {
                if (!inList || listType !== 'ul') {
                    if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    formatted += '<ul class="mb-3">';
                    inList = true;
                    listType = 'ul';
                }
                const itemText = line.replace(/^[\s]*[\*\-•]\s+(.+)$/, '$1');
                formatted += `<li>${itemText}</li>`;
            }
            // Check for numbered lists (1. Item)
            else if (/^[\s]*\d+\.\s+(.+)$/.test(line)) {
                if (!inList || listType !== 'ol') {
                    if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    formatted += '<ol class="mb-3">';
                    inList = true;
                    listType = 'ol';
                }
                const itemText = line.replace(/^[\s]*\d+\.\s+(.+)$/, '$1');
                formatted += `<li>${itemText}</li>`;
            }
            // Regular text
            else {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }

                // Skip empty lines
                if (line.trim() === '') {
                    formatted += '<br>';
                    continue;
                }

                // Check for section headers with multiple patterns
                const diagnosisPattern = /(DIAGNOS[IE]S|POSSIBLE\s+DIAGNOS[IE]S|DIFFERENTIAL\s+DIAGNOS[IE]S)/i;
                const recommendationsPattern = /(RECOMMENDATIONS|RECOMMENDATIONS\s+FOR\s+TESTS|SUGGESTED\s+TESTS)/i;
                const treatmentPattern = /(TREATMENT|TREATMENT\s+RECOMMENDATIONS|TREATMENT\s+PLAN|MANAGEMENT)/i;
                const warningsPattern = /(WARNINGS|PRECAUTIONS|RED\s+FLAGS|FOLLOW\-UP)/i;

                if (/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS|PRECAUTIONS|MANAGEMENT|FOLLOW).*?$/i.test(line) ||
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS|PRECAUTIONS|MANAGEMENT|FOLLOW).*?$/i.test(line) ||
                    /^[A-Z]\)\s+(POSSIBLE\s+DIAGNOS[IE]S|RECOMMENDATIONS\s+FOR\s+TESTS|TREATMENT\s+RECOMMENDATIONS|WARNINGS|PRECAUTIONS)$/i.test(line)) {

                    let className = '';

                    if (diagnosisPattern.test(line)) {
                        className = 'section-diagnosis';
                    } else if (recommendationsPattern.test(line)) {
                        className = 'section-recommendations';
                    } else if (treatmentPattern.test(line)) {
                        className = 'section-treatment';
                    } else if (warningsPattern.test(line)) {
                        className = 'section-warnings';
                    }

                    formatted += `<h4 class="mt-4 ${className}">${line}</h4>`;
                }
                // Check for subsection headers (often in ALL CAPS or with trailing colon)
                else if (/^[A-Z][A-Z\s\d\-\(\)]{5,}:?$/.test(line)) {
                    formatted += `<p><strong style="font-size: 1.15rem; color: #34495e;">${line}</strong></p>`;
                }
                else {
                    // All other text is formatted as regular paragraphs
                    formatted += `<p>${line}</p>`;
                }
            }
        }

        // Close any open lists or tables
        if (inList) {
            formatted += listType === 'ul' ? '</ul>' : '</ol>';
        }
        if (inTable) {
            formatted += formatTable(tableRows);
        }

        // Close any remaining open divs
        formatted += '</div></div>';

        // Process inline formatting

        // Bold text between ** or __
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/__(.+?)__/g, '<strong>$1</strong>');

        // Italic text between * or _
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>');

        // Highlight important information
        formatted = formatted.replace(/\!\!(.+?)\!\!/g, '<span style="background-color: #ffffcc; padding: 0 3px;">$1</span>');

        // Add some spacing between sections for better readability
        formatted = formatted.replace(/<\/h[1-6]>/g, '$&<div style="height: 10px;"></div>');

        // Close any remaining open divs
        formatted += '</div></div>';

        // Process inline formatting

        // Bold text between ** or __
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/__(.+?)__/g, '<strong>$1</strong>');

        // Italic text between * or _
        formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/_(.+?)_/g, '<em>$1</em>');

        // Code blocks
        formatted = formatted.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
        formatted = formatted.replace(/`(.+?)`/g, '<code>$1</code>');

        return formatted;
    }

    // Format sources to just show the logos of the sites
    function formatSources(sourcesText) {
        if (!sourcesText || sourcesText.trim() === '') {
            return '';
        }

        // Create a simple logo grid
        let html = '<div class="d-flex flex-wrap justify-content-center mt-3">';

        // Add PubMed logo
        if (sourcesText.match(/pubmed|ncbi|nlm|nih\.gov/i)) {
            html += `
                <div class="m-2">
                    <img src="https://cdn.ncbi.nlm.nih.gov/pubmed/images/pubmed-logo.png"
                         alt="PubMed"
                         title="PubMed"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add NEJM logo
        if (sourcesText.match(/nejm|new england journal/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.nejm.org/pb-assets/images/global/social-share/NEJM-Logo-Social-Share.jpg"
                         alt="NEJM"
                         title="New England Journal of Medicine"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add JAMA logo
        if (sourcesText.match(/jama|american medical association/i)) {
            html += `
                <div class="m-2">
                    <img src="https://jamanetwork.com/images/logos/jama-logo.svg"
                         alt="JAMA"
                         title="Journal of the American Medical Association"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add The Lancet logo
        if (sourcesText.match(/lancet/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.thelancet.com/cms/asset/f4e2c7e5-9c1e-4d7c-b0c3-a4b8519eb0c3/lancet-logo.jpg"
                         alt="The Lancet"
                         title="The Lancet"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add BMJ logo
        if (sourcesText.match(/bmj|british medical journal/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.bmj.com/sites/default/files/attachments/bmj-logo.jpg"
                         alt="BMJ"
                         title="British Medical Journal"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add CDC logo
        if (sourcesText.match(/cdc|centers for disease control/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.cdc.gov/homepage/images/cdc-logo.png"
                         alt="CDC"
                         title="Centers for Disease Control and Prevention"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add WHO logo
        if (sourcesText.match(/who|world health/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.who.int/images/default-source/default-album/who-emblem.jpg"
                         alt="WHO"
                         title="World Health Organization"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add Mayo Clinic logo
        if (sourcesText.match(/mayo|clinic/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.mayoclinic.org/-/media/web/gbs/shared/images/socialmedia/mayo-clinic-logo-socialmedia.jpg"
                         alt="Mayo Clinic"
                         title="Mayo Clinic"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Add UpToDate logo
        if (sourcesText.match(/uptodate|wolters kluwer/i)) {
            html += `
                <div class="m-2">
                    <img src="https://www.uptodate.com/sites/default/files/styles/large/public/2022-10/UpToDate_Logo_RGB.png"
                         alt="UpToDate"
                         title="UpToDate"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            `;
        }

        // Always add a generic medical source logo
        html += `
            <div class="m-2">
                <img src="https://cdn-icons-png.flaticon.com/512/3022/3022339.png"
                     alt="Medical Source"
                     title="Medical Source"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;

        html += '</div>';

        return html;
    }

    // Print functionality for response modal
    document.addEventListener('DOMContentLoaded', function() {
        const printResponseBtn = document.getElementById('printResponseBtn');
        if (printResponseBtn) {
            printResponseBtn.addEventListener('click', function() {
                let responseContent = document.getElementById('openaiReply').innerHTML;
                // Sources are hidden as requested
                const sourcesContent = '';

                // The content is already formatted with proper HTML, no need for additional formatting

                // Create a new window for printing
                const printWindow = window.open('', '_blank');

                // Add content to the print window
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Medical Recommendations</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            .header { text-align: center; margin-bottom: 30px; }
                            .content { margin-bottom: 30px; line-height: 1.6; }
                            .sources { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                            h4 { color: #2c3e50; margin-top: 25px; margin-bottom: 15px; }
                            ul, ol { margin-bottom: 20px; }
                            li { margin-bottom: 8px; }
                            @media print {
                                .no-print { display: none; }
                                a { text-decoration: none; color: #000; }
                                h4 { page-break-after: avoid; }
                                ul, ol { page-break-inside: avoid; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>Medical Recommendations</h2>
                            <p>${new Date().toLocaleDateString()}</p>
                        </div>

                        <div class="content">
                            ${responseContent}
                        </div>

                        ${sourcesContent ? `
                        <div class="sources">
                            <h5>Sources</h5>
                            ${sourcesContent}
                        </div>
                        ` : ''}

                        <div class="text-center mt-4 no-print">
                            <button class="btn btn-primary" onclick="window.print()">Print</button>
                            <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
                        </div>
                    </body>
                    </html>
                `);

                // Focus the new window
                printWindow.document.close();
                printWindow.focus();
            });
        }
    });
</script>





    <!-- Patient Selection Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle patient selection
            const patientSelection = document.getElementById('patient_selection');
            const newPatientInfo = document.getElementById('new_patient_info');
            const nameInput = document.getElementById('name');
            const ageInput = document.getElementById('age');
            const genderSelect = document.getElementById('gender');

            const patientHistoryInfo = document.getElementById('patient_history_info');
            const visitCountBadge = document.getElementById('visit_count_badge');
            const patientHistoryText = document.getElementById('patient_history_text');

            // Store patient data for quick access
            const patientData = @json($existingPatients);

            // Store visit counts - simplifiedVisits contains both patient_key and name-age-gender keys
            const patientVisits = @json($simplifiedVisits ?? []);

            // Debug: Log available keys for troubleshooting
            console.log('Available patient visit keys:', Object.keys(patientVisits));
            console.log('Patient data:', patientData);
            console.log('Patient visits:', patientVisits);

            // Function to toggle patient info visibility
            function togglePatientInfo() {
                if (patientSelection.value === 'new') {
                    // Show new patient form
                    newPatientInfo.style.display = 'block';
                    patientHistoryInfo.style.display = 'none';

                    // Make fields required
                    nameInput.required = true;
                    ageInput.required = true;
                } else {
                    // Hide new patient form
                    newPatientInfo.style.display = 'none';

                    // Remove required attribute and clear values
                    nameInput.required = false;
                    nameInput.value = '';
                    ageInput.required = false;
                    ageInput.value = '';
                    genderSelect.value = '';

                    // Show patient history
                    updatePatientHistory(patientSelection.value);
                }
            }

            // Function to update patient history display
            function updatePatientHistory(patientId) {
                console.log('Updating patient history for ID:', patientId);
                const selectedPatient = patientData.find(p => p.id == patientId);

                if (selectedPatient) {
                    console.log('Selected patient:', selectedPatient);

                    // Try multiple key formats to find a match
                    const nameAgeGenderKey = selectedPatient.name + '-' + selectedPatient.age + '-' + selectedPatient.gender;
                    const patientKey = selectedPatient.patient_key;

                    console.log('Trying keys:', { nameAgeGenderKey, patientKey });

                    // Try patient_key first, then name-age-gender
                    let key = null;
                    let visitData = null;

                    if (patientKey && patientVisits[patientKey]) {
                        key = patientKey;
                        visitData = patientVisits[patientKey];
                        console.log('Found visit data using patient_key');
                    } else if (patientVisits[nameAgeGenderKey]) {
                        key = nameAgeGenderKey;
                        visitData = patientVisits[nameAgeGenderKey];
                        console.log('Found visit data using name-age-gender key');
                    } else {
                        key = nameAgeGenderKey;
                        visitData = { count: 1 };
                        console.log('No visit data found, using default');
                    }

                    const visitCount = visitData.count || 1;

                    // Show patient history section
                    patientHistoryInfo.style.display = 'block';

                    // Update visit count badge
                    visitCountBadge.textContent = 'Visit #' + visitCount;
                    console.log('Setting visit count to:', visitCount);

                    // Update history text
                    if (visitCount > 1) {
                        patientHistoryText.innerHTML = `<strong>${selectedPatient.name}</strong> has been seen ${visitCount} time(s) before. This will be visit #${visitCount+1}. Previous medical history will be considered in the analysis.`;
                    } else {
                        patientHistoryText.innerHTML = `This is the second visit for <strong>${selectedPatient.name}</strong>.`;
                    }

                    console.log('Patient history updated successfully');
                } else {
                    patientHistoryInfo.style.display = 'none';
                }
            }

            // Initial toggle
            togglePatientInfo();

            // Add event listener
            patientSelection.addEventListener('change', togglePatientInfo);

            // Validate form before submission
            document.getElementById('openaiForm').addEventListener('submit', function(e) {
                const patientSelectionField = document.getElementById('patient_selection');

                if (patientSelectionField && patientSelectionField.value === '') {
                    e.preventDefault();
                    alert('Please select a patient');
                    return false;
                }
            });
        });
    </script>

    <!-- Initialize Choices.js for symptoms dropdown -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Content Loaded - Initializing Choices.js');
            const element = document.getElementById('current_symptoms');

            if (!element) {
                console.error('Could not find element with ID "current_symptoms"');
                return;
            }

            console.log('Found current_symptoms element:', element);

            try {
                if (typeof Choices === 'undefined') {
                    console.error('Choices.js is not loaded');
                    return;
                }

                console.log('Choices.js is loaded, initializing...');

                const choices = new Choices(element, {
                    removeItemButton: true,
                    placeholderValue: 'Select symptoms...',
                    searchPlaceholderValue: 'Search symptoms...',
                    classNames: {
                        containerInner: 'form-control',
                    }
                });

                console.log('Choices.js initialized successfully');

                // Custom Symptoms Handling
                const customSymptomInput = document.getElementById('custom_symptom_input');
                const addCustomSymptomBtn = document.getElementById('add_custom_symptom');
                const customSymptomsContainer = document.getElementById('custom_symptoms_container');
                const customSymptomsData = document.getElementById('custom_symptoms_data');

                // Array to store custom symptoms
                let customSymptoms = [];

                // Function to add a custom symptom
                function addCustomSymptom() {
                    const symptomText = customSymptomInput.value.trim();

                    if (symptomText.length < 3) {
                        alert('Symptom must be at least 3 characters long');
                        return;
                    }

                    if (customSymptoms.includes(symptomText)) {
                        alert('This symptom has already been added');
                        return;
                    }

                    // Add to array
                    customSymptoms.push(symptomText);

                    // Update hidden input
                    customSymptomsData.value = JSON.stringify(customSymptoms);

                    // Create visual representation
                    const symptomBadge = document.createElement('span');
                    symptomBadge.className = 'badge me-2 mb-2 p-2';
                    symptomBadge.style.backgroundColor = '#DE6262';
                    symptomBadge.style.color = 'white';
                    symptomBadge.innerHTML = `${symptomText} <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove" style="font-size: 0.5rem;"></button>`;

                    // Add remove functionality
                    const closeBtn = symptomBadge.querySelector('.btn-close');
                    closeBtn.addEventListener('click', function() {
                        // Remove from array
                        const index = customSymptoms.indexOf(symptomText);
                        if (index > -1) {
                            customSymptoms.splice(index, 1);
                        }

                        // Update hidden input
                        customSymptomsData.value = JSON.stringify(customSymptoms);

                        // Remove badge
                        symptomBadge.remove();
                    });

                    // Add to container
                    customSymptomsContainer.appendChild(symptomBadge);

                    // Clear input
                    customSymptomInput.value = '';
                    customSymptomInput.focus();

                    console.log('Added custom symptom:', symptomText);
                    console.log('Current custom symptoms:', customSymptoms);
                }

                // Add event listeners
                addCustomSymptomBtn.addEventListener('click', addCustomSymptom);

                customSymptomInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault(); // Prevent form submission
                        addCustomSymptom();
                    }
                });

            } catch (error) {
                console.error('Error initializing Choices.js:', error);
            }
        });
    </script>

<!-- Enhanced File Upload Handler Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('reports');
        const selectedFilesContainer = document.getElementById('selected-files');
        const fileStorageContainer = document.getElementById('file-storage-container');
        const uploadStatus = document.getElementById('upload-status');
        const addMoreFilesBtn = document.getElementById('add-more-files-btn');
        const uploadZone = document.querySelector('.upload-zone');

        // Store all selected files
        let selectedFiles;
        let selectedFilesArray = []; // Fallback for browsers without DataTransfer support

        // Check if DataTransfer is supported
        const isDataTransferSupported = (function() {
            try {
                return !!new DataTransfer();
            } catch (e) {
                return false;
            }
        })();

        if (isDataTransferSupported) {
            selectedFiles = new DataTransfer();
            console.log('Using DataTransfer API for file handling');
        } else {
            console.log('DataTransfer API not supported, using fallback');
        }

        // Add drag and drop functionality to upload zone
        if (uploadZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, preventDefaults, false);
            });

            // Highlight drop zone when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, unhighlight, false);
            });

            // Handle dropped files
            uploadZone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function highlight() {
                uploadZone.classList.add('border-primary');
                uploadZone.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            }

            function unhighlight() {
                uploadZone.classList.remove('border-primary');
                uploadZone.style.backgroundColor = '';
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    if (isDataTransferSupported) {
                        Array.from(files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = Array.from(selectedFiles.files).some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFiles.items.add(file);
                            }
                        });

                        // Update the file input with all files
                        fileInput.files = selectedFiles.files;
                    } else {
                        // For browsers without DataTransfer support
                        Array.from(files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = selectedFilesArray.some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFilesArray.push(file);
                            }
                        });
                    }

                    updateFileListDisplay();

                    // Show success message
                    uploadStatus.innerHTML = `
                        <div class="alert alert-success py-2 px-3 fade show">
                            <i class="fas fa-check-circle me-2"></i> Files added successfully!
                            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;

                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        const alert = uploadStatus.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                }
            }
        }

        // Initialize tooltip
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle file info button click
        const fileInfoBtn = document.getElementById('file-info-btn');
        if (fileInfoBtn) {
            fileInfoBtn.addEventListener('click', function() {
                // Create modal for file upload instructions
                const modalHtml = `
                    <div class="modal fade" id="fileUploadInfoModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" style="word-break: break-word; line-height: 1.3; font-size: 1.1rem;">File Upload Instructions</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" style="word-break: break-word; line-height: 1.5; font-size: 0.95rem;">
                                    <div class="mb-3">
                                        <h6 style="word-break: break-word; font-size: 1rem;"><i class="fas fa-info-circle text-primary me-2"></i>How to Add Multiple Files</h6>
                                        <p style="margin-bottom: 0.8rem;">You can add files in two ways:</p>
                                        <ul class="list-group list-group-flush mb-3">
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;">
                                                <strong>Method 1:</strong> Select multiple files at once
                                                <ul class="mt-2" style="padding-left: 1.2rem;">
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;"><strong>Windows:</strong> Hold <kbd>Ctrl</kbd> and click each file</li>
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;"><strong>Mac:</strong> Hold <kbd>⌘ Command</kbd> and click each file</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;">
                                                <strong>Method 2:</strong> Add files incrementally
                                                <ul class="mt-2" style="padding-left: 1.2rem;">
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;">Select one or more files</li>
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;">Click the <i class="fas fa-plus"></i> button to add more files</li>
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;">Repeat as needed to add different file types</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="mb-3">
                                        <h6 style="word-break: break-word; font-size: 1rem;"><i class="fas fa-file-medical text-danger me-2"></i>Supported File Types</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;"><strong>Images:</strong> JPG, JPEG, PNG, GIF, BMP, WEBP</li>
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;"><strong>Documents:</strong> PDF, DOCX, DOC, TXT, RTF</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-info" style="word-break: break-word; font-size: 0.9rem;">
                                        <i class="fas fa-robot me-2"></i> The AI will analyze <strong>all uploaded files together</strong> to provide a comprehensive analysis.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Add modal to body if it doesn't exist
                if (!document.getElementById('fileUploadInfoModal')) {
                    const modalContainer = document.createElement('div');
                    modalContainer.innerHTML = modalHtml;
                    document.body.appendChild(modalContainer);
                }

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('fileUploadInfoModal'));
                modal.show();
            });
        }

        // Function to get all selected files
        function getSelectedFiles() {
            if (isDataTransferSupported) {
                return selectedFiles.files;
            } else {
                return selectedFilesArray;
            }
        }

        // Function to get the count of selected files
        function getSelectedFilesCount() {
            if (isDataTransferSupported) {
                return selectedFiles.files.length;
            } else {
                return selectedFilesArray.length;
            }
        }

        // Function to update the file list display
        function updateFileListDisplay() {
            selectedFilesContainer.innerHTML = '';

            const files = getSelectedFiles();
            const filesCount = getSelectedFilesCount();

            if (filesCount > 0) {
                // Create a container for file items
                const fileList = document.createElement('div');

                // Function to create file item element with improved styling
                function createFileItem(file, index) {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'selected-file';

                    // Determine file type and icon
                    let fileIcon = 'fa-file';
                    let iconColor = 'text-secondary';

                    // Get file extension
                    const fileExt = file.name.split('.').pop().toLowerCase();

                    // Set icon based on file type
                    if (file.type.match(/image\/.*/)) {
                        fileIcon = 'fa-file-image';
                        iconColor = 'text-primary';
                    } else if (file.type === 'application/pdf' || fileExt === 'pdf') {
                        fileIcon = 'fa-file-pdf';
                        iconColor = 'text-danger';
                    } else if (file.type.match(/.*word.*/) || ['doc', 'docx'].includes(fileExt)) {
                        fileIcon = 'fa-file-word';
                        iconColor = 'text-info';
                    } else if (file.type === 'text/plain' || fileExt === 'txt') {
                        fileIcon = 'fa-file-lines';
                        iconColor = 'text-secondary';
                    } else if (['xls', 'xlsx', 'csv'].includes(fileExt)) {
                        fileIcon = 'fa-file-excel';
                        iconColor = 'text-success';
                    } else if (['ppt', 'pptx'].includes(fileExt)) {
                        fileIcon = 'fa-file-powerpoint';
                        iconColor = 'text-warning';
                    } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(fileExt)) {
                        fileIcon = 'fa-file-archive';
                        iconColor = 'text-secondary';
                    } else if (['mp3', 'wav', 'ogg'].includes(fileExt)) {
                        fileIcon = 'fa-file-audio';
                        iconColor = 'text-info';
                    } else if (['mp4', 'avi', 'mov', 'wmv'].includes(fileExt)) {
                        fileIcon = 'fa-file-video';
                        iconColor = 'text-danger';
                    } else if (['html', 'htm', 'xml', 'json', 'js', 'css', 'php'].includes(fileExt)) {
                        fileIcon = 'fa-file-code';
                        iconColor = 'text-primary';
                    }

                    // Format file size
                    const fileSize = file.size < 1024 * 1024
                        ? Math.round(file.size / 1024) + ' KB'
                        : Math.round(file.size / (1024 * 1024) * 10) / 10 + ' MB';

                    // Create file item HTML with improved styling
                    fileItem.innerHTML = `
                        <span class="file-icon ${iconColor}"><i class="fas ${fileIcon}"></i></span>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${fileSize}</span>
                        <span class="file-remove" data-index="${index}" title="Remove file"><i class="fas fa-times-circle"></i></span>
                    `;

                    // Add event listener to remove button
                    const removeBtn = fileItem.querySelector('.file-remove');
                    removeBtn.addEventListener('click', function() {
                        const fileIndex = parseInt(this.getAttribute('data-index'));
                        removeFile(fileIndex);
                    });

                    return fileItem;
                }

                // Add all files to the list
                Array.from(files).forEach((file, index) => {
                    fileList.appendChild(createFileItem(file, index));
                });

                selectedFilesContainer.appendChild(fileList);

                // Check total size
                let totalSize = 0;
                for (let i = 0; i < filesCount; i++) {
                    totalSize += files[i].size;
                }

                // Add file count and total size info
                const fileInfo = document.createElement('div');
                fileInfo.className = 'd-flex justify-content-between align-items-center mt-3';

                // Format total size
                const formattedTotalSize = totalSize < 1024 * 1024
                    ? Math.round(totalSize / 1024) + ' KB'
                    : Math.round(totalSize / (1024 * 1024) * 10) / 10 + ' MB';

                fileInfo.innerHTML = `
                    <div class="text-muted">
                        <small>${filesCount} file(s) selected (${formattedTotalSize})</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button">
                        <i class="fas fa-times me-1"></i> Clear All
                    </button>
                `;

                selectedFilesContainer.appendChild(fileInfo);

                // Add event listener to clear all button
                const clearAllBtn = fileInfo.querySelector('button');
                clearAllBtn.addEventListener('click', function() {
                    if (isDataTransferSupported) {
                        selectedFiles = new DataTransfer();
                        fileInput.files = selectedFiles.files;
                    } else {
                        selectedFilesArray = [];
                        fileInput.value = '';
                    }
                    updateFileListDisplay();

                    // Show status message
                    uploadStatus.innerHTML = `
                        <div class="alert alert-info py-2 px-3 fade show">
                            <i class="fas fa-info-circle me-2"></i> All files cleared
                            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;

                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        const alert = uploadStatus.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                });

                // Display warning if total size is large
                if (totalSize > 20 * 1024 * 1024) { // 20MB
                    const warning = document.createElement('div');
                    warning.className = 'alert alert-warning py-2 px-3 mt-2';
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Large files may take longer to process';
                    selectedFilesContainer.appendChild(warning);
                }
            } else {
                // No files selected
                selectedFilesContainer.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-file-upload me-2"></i>No files selected yet
                    </div>
                `;
            }
        }

        // Function to remove a file by index
        function removeFile(index) {
            if (isDataTransferSupported) {
                const newFiles = new DataTransfer();

                Array.from(selectedFiles.files)
                    .filter((_, i) => i !== index)
                    .forEach(file => newFiles.items.add(file));

                selectedFiles = newFiles;
                fileInput.files = selectedFiles.files;
            } else {
                selectedFilesArray = selectedFilesArray.filter((_, i) => i !== index);

                // We can't update the file input directly in this case
                // The user will need to reselect files if they want to submit
                if (selectedFilesArray.length === 0) {
                    fileInput.value = '';
                }
            }
            updateFileListDisplay();
        }

        // Handle file input change
        if (fileInput && selectedFilesContainer) {
            fileInput.addEventListener('change', function() {
                // Add newly selected files to our collection
                if (this.files.length > 0) {
                    if (isDataTransferSupported) {
                        Array.from(this.files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = Array.from(selectedFiles.files).some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFiles.items.add(file);
                            }
                        });

                        // Update the file input with all files
                        fileInput.files = selectedFiles.files;
                    } else {
                        // For browsers without DataTransfer support
                        // We'll store the files in our array and display them
                        // But we can't modify the file input directly
                        Array.from(this.files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = selectedFilesArray.some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFilesArray.push(file);
                            }
                        });

                        // Show a warning for browsers without DataTransfer support
                        if (!document.getElementById('dataTransferWarning')) {
                            const warning = document.createElement('div');
                            warning.id = 'dataTransferWarning';
                            warning.className = 'alert alert-warning py-1 px-2 mt-2';
                            warning.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> Your browser has limited support for file uploads. For best results, use Chrome, Edge, or Firefox.</small>';
                            fileStorageContainer.parentNode.insertBefore(warning, fileStorageContainer);
                        }
                    }

                    updateFileListDisplay();
                }
            });

            // Add "Add More Files" button handler
            if (addMoreFilesBtn) {
                addMoreFilesBtn.addEventListener('click', function() {
                    // Reset the file input to allow selecting the same file again
                    fileInput.value = '';
                    fileInput.click();
                });
            }

            // Add form submit handler to show upload status
            const form = document.getElementById('openaiForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const filesCount = getSelectedFilesCount();

                    if (filesCount > 0) {
                        // For browsers without DataTransfer support, we need to handle this differently
                        if (!isDataTransferSupported && selectedFilesArray.length > 0) {
                            // If the current file input doesn't match our stored files, we need to warn the user
                            if (fileInput.files.length !== selectedFilesArray.length) {
                                e.preventDefault();
                                alert('Please reselect all files before submitting. Your browser requires selecting all files at once.');
                                return;
                            }
                        }

                        // Show loading indicator
                        const existingLoader = document.getElementById('canvas-loader-overlay');
                        if (!existingLoader) {
                            const body = document.body;
                            const loaderHTML = body.getAttribute('data-loader-html');
                            if (loaderHTML) {
                                const loaderOverlay = document.createElement('div');
                                loaderOverlay.id = 'canvas-loader-overlay';
                                loaderOverlay.innerHTML = loaderHTML;
                                loaderOverlay.style.cssText = `
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    width: 100%;
                                    height: 100%;
                                    background: rgba(44, 62, 80, 0.9);
                                    z-index: 9999;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    backdrop-filter: blur(5px);
                                `;

                                const svgContainer = loaderOverlay.querySelector('#css3-spinner-svg-pulse-wrapper');
                                if (svgContainer) {
                                    svgContainer.style.cssText = `
                                        text-align: center;
                                        padding: 20px;
                                    `;
                                }

                                document.body.appendChild(loaderOverlay);
                            } else {
                                document.getElementById('page-loader').style.display = 'flex';
                            }
                        }

                        // Update status
                        uploadStatus.innerHTML = `
                            <div class="alert alert-info py-1 px-2">
                                <small><i class="fas fa-spinner fa-spin"></i> Uploading and analyzing ${filesCount} file(s)...</small>
                            </div>
                        `;
                    }
                });
            }
        }
    });

    // Toggle function for Level 2 detailed report
    function toggleLevel2() {
        const content = document.getElementById('level2-content');
        const icon = document.querySelector('.toggle-icon');
        const hint = document.querySelector('.toggle-hint');

        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            icon.textContent = '▲';
            icon.classList.add('rotated');
            hint.textContent = 'Click to Collapse';
        } else {
            content.style.display = 'none';
            icon.textContent = '▼';
            icon.classList.remove('rotated');
            hint.textContent = 'Click to Expand';
        }
    }
</script>

<!-- Head-to-Toe Assessment Normal Checkbox Handler -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all normal checkboxes
        const normalCheckboxes = document.querySelectorAll('.section-normal-checkbox');

        // Add event listener to each checkbox
        normalCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const sectionContentId = this.getAttribute('data-section');
                const sectionContent = document.getElementById(sectionContentId);

                if (this.checked) {
                    // If checked, hide the section content using vanilla JavaScript
                    sectionContent.style.display = 'none';

                    // Clear all inputs in this section
                    const inputs = sectionContent.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.checked = false;
                        } else if (input.tagName === 'SELECT') {
                            input.selectedIndex = 0;
                        } else {
                            input.value = '';
                        }
                    });

                    // Add a hidden input to indicate this section is normal
                    const sectionId = this.id.replace('-normal', '');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = sectionId + '_status';
                    hiddenInput.value = 'normal';
                    hiddenInput.id = sectionId + '_status';
                    sectionContent.parentNode.appendChild(hiddenInput);
                } else {
                    // If unchecked, show the section content using vanilla JavaScript
                    sectionContent.style.display = 'block';

                    // Remove the hidden input if it exists
                    const sectionId = this.id.replace('-normal', '');
                    const hiddenInput = document.getElementById(sectionId + '_status');
                    if (hiddenInput) {
                        hiddenInput.parentNode.removeChild(hiddenInput);
                    }
                }
            });
        });
    });
</script>
    @endsection