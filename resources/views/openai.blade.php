<!-- resources/views/openai-form.blade.php -->
@extends('master')

@section('title', 'Patients Page')

@section('content')

<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<!-- Include Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/styles/choices.min.css" />

<!-- Include Choices.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/scripts/choices.min.js"></script>

<style>
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
</style>

        <div class="container medical-form-container">
            <form id="openaiForm" action="{{ url('/openai/respond') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if(isset($patientToEdit))
                    <input type="hidden" name="edit_patient_id" value="{{ $patientToEdit->id }}">
                @endif
                
                <!-- Form Progress Indicator -->
                <div class="form-progress-container mb-4">
                    <div class="progress-steps d-flex justify-content-between">
                        <div class="progress-step active" data-step="patient">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="step-label mt-2">Patient</div>
                        </div>
                        <div class="progress-step" data-step="vitals">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="step-label mt-2">Vitals</div>
                        </div>
                        <div class="progress-step" data-step="symptoms">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="step-label mt-2">Symptoms</div>
                        </div>
                        <div class="progress-step" data-step="diagnosis">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <div class="step-label mt-2">Diagnosis</div>
                        </div>
                        <div class="progress-step" data-step="analysis">
                            <div class="step-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="step-label mt-2">AI Analysis</div>
                        </div>
                    </div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-gradient" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
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
                    
                    <div id="errorMessages"></div>
                    
                    <!-- Patient Selection -->
                    <div class="medical-form-section">
                        <h4>Patient Selection</h4>
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
                        <h4>Patient Information</h4>
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
                            <h4 class="mb-0"><i class="fas fa-file-medical  me-2" ></i>Medical Reports</h4>
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
                            <h4 class="mb-0 me-2">Patient History</h4>
                            <span id="visit_count_badge" class="badge bg-info ms-2">Visit #1</span>
                        </div>
                        <div class="alert alert-info" id="patient_history_alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="patient_history_text">Select an existing patient to see their history.</span>
                        </div>
                    </div>
        
                    <!-- Vitals -->
                    <div class="medical-form-section mt-4">
                        <h4>Physical Attributes / Vitals</h4>
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">
                                    <i class="fas fa-weight text-primary me-1"></i> Weight:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="weight" class="form-control" value="{{ $patientToEdit->weight ?? '' }}" placeholder="70.5">
                                    <span class="input-group-text">kg</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">
                                    <i class="fas fa-ruler-vertical text-success me-1"></i> Height:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="height" class="form-control" value="{{ $patientToEdit->height ?? '' }}" placeholder="175">
                                    <span class="input-group-text">cm</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">
                                    <i class="fas fa-thermometer-half text-danger me-1"></i> Temperature:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="temperature" class="form-control" placeholder="37.2">
                                    <span class="input-group-text">°C</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-heartbeat text-info me-1"></i> Blood Pressure:
                                </label>
                                <div class="input-group">
                                    <input type="text" name="blood_pressure" class="form-control" placeholder="120/80">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-tint text-warning me-1"></i> Blood Sugar:
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="blood_sugar" class="form-control" placeholder="85">
                                    <span class="input-group-text">mg/dL</span>
                                </div>
                                <small class="form-text text-muted">Enter numeric value only</small>
                            </div>
                        </div>
                    </div>
        
                    <!-- Symptoms -->
                    <div class="medical-form-section mt-4">
                        <h4>Symptoms</h4>
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
                                    <i class="fas fa-info-circle me-1"></i> Type to search or select from the dropdown
                                </small>
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
                        <h4>Test Results & Preliminary Diagnosis</h4>
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
                                <textarea name="preliminary_diagnosis" class="form-control" rows="4" placeholder="Enter your initial assessment or suspected diagnosis based on the patient's symptoms and test results."></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i> This will be analyzed by the AI to provide recommendations
                                </small>
                            </div>
                        </div>
                    </div>
        
                <!-- Submit -->
                <div class="row mt-5">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-deep-red btn-lg px-4">
                            <i class="fa-solid fa-robot me-2"></i>Get Results
                        </button>
                    </div>
                </div>


        
                </div>
            </form>
        </div>

        <div id="page-loader" style="display:none;">
            <div id='css3-spinner-svg-pulse-wrapper'>
                <svg id='css3-spinner-svg-pulse' version='1.2' height='210' width='550'
                     xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>
                    <path id='css3-spinner-pulse' stroke='#DE6262' fill='none' stroke-width='2'
                          stroke-linejoin='round'
                          d='M0,90L250,90Q257,60 262,87T267,95 270,88 273,92t6,35 7,-60T290,127 297,107s2,-11 10,-10 1,1 8,-10T319,95c6,4 8,-6 10,-17s2,10 9,11h210'></path>
                </svg>
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
                <!-- AI Response Section -->
                <div class="ai-response-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-robot me-2"></i>AI Analysis</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div class="ai-summary" style="background-color: #f8f9fa; border-radius: 15px; padding: 20px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05);">
                        <div id="openaiReply" class="response-text"></div>
                    </div>
                </div>
                
                <!-- Sources Section - Hidden as requested -->
                <div id="sourcesCitation" class="mt-4" style="display: none;">
                    <div id="sourcesContent" class="sources-list">
                        <!-- Source logos will be populated here but not displayed -->
                    </div>
                </div>
                
                <!-- Chat continuation section -->
                <hr class="my-4">
                
                <div id="chat-continuation">
                    <h6 class="mb-3"><i class="fas fa-comments me-2"></i>Follow-up Questions</h6>
                    
                    <div id="chat-messages" class="mb-3">
                        <!-- Additional messages will appear here -->
                    </div>
                    
                    <div class="chat-input-container">
                        <form id="follow-up-form" class="d-flex">
                            @csrf
                            <input type="hidden" id="conversation-id" name="conversation_id" value="{{ session('conversation_id') ?? '' }}">
                            <input type="text" id="follow-up-message" name="message" class="form-control" placeholder="Ask a follow-up question..." required>
                            <button type="submit" class="btn btn-primary ms-2">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </form>
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
</style>

  
  
  

        

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


<script>
    document.getElementById('openaiForm').addEventListener('submit', function () {
        document.getElementById('page-loader').style.display = 'block';
    });
    
    // Form progress indicator functionality
    document.addEventListener('DOMContentLoaded', function() {
        const progressSteps = document.querySelectorAll('.progress-step');
        const progressBar = document.querySelector('.progress-bar');
        
        // Find sections by heading text
        function findSectionByHeadingText(text) {
            const headings = document.querySelectorAll('.medical-form-section h4');
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
                
                if (stepName === step) {
                    stepEl.classList.add('active');
                    activeFound = true;
                    progress = (index + 1) * 20; // 20% per step
                } else if (!activeFound) {
                    stepEl.classList.add('completed');
                    stepEl.classList.remove('active');
                } else {
                    stepEl.classList.remove('active', 'completed');
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
            document.getElementById('page-loader').style.display = 'none';
            
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
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="apiKeyErrorModalLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    OpenAI API Key Error
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>The OpenAI API key appears to be invalid or expired. This means:</p>
                                <ul>
                                    <li>You won't be able to get AI-powered responses</li>
                                    <li>Medical analysis features will be unavailable</li>
                                    <li>Chat functionality will not work</li>
                                </ul>
                                <p>Please contact the system administrator to update the API key.</p>
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
     * Format AI response text with proper HTML formatting
     */
    function formatAIResponse(text) {
        if (!text) return '';
        
        // Remove the Sources section from the text before formatting
        const sourcesMatch = text.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
        let cleanedText = text;
        
        if (sourcesMatch) {
            cleanedText = text.replace(sourcesMatch[0], '').trim();
        }
        
        // First, enhance the main section headers to make them more prominent
        // This will convert PATIENT INFORMATION, A) POSSIBLE DIAGNOSIS: etc. to proper headers
        const enhancedText = cleanedText
            .replace(/^(PATIENT\s+INFORMATION:.*$)/gm, '<h4 class="mt-4 section-patient-info" style="color: #6c5ce7; border-left: 4px solid #6c5ce7; padding: 8px 0 8px 15px; background-color: rgba(108, 92, 231, 0.05); border-radius: 0 5px 5px 0;">$1</h4>')
            .replace(/^(MEDICAL\s+REPORTS\s+ANALYSIS:.*$)/gm, '<h5 class="mt-3 section-reports" style="color: #6c5ce7; margin-left: 15px; border-left: 2px solid #6c5ce7; padding: 5px 0 5px 10px;">$1</h5>')
            .replace(/^(A\)\s*POSSIBLE\s*DIAGNOSIS:.*$)/gm, '<h4 class="mt-4 section-diagnosis">$1</h4>')
            .replace(/^(B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS\s*OR\s*IMAGING:.*$)/gm, '<h4 class="mt-4 section-recommendations">$1</h4>')
            .replace(/^(C\)\s*TREATMENT\s*RECOMMENDATIONS:.*$)/gm, '<h4 class="mt-4 section-treatment">$1</h4>')
            .replace(/^(D\)\s*WARNING\s*SIGNS:.*$)/gm, '<h4 class="mt-4 section-warnings">$1</h4>');
        
        // Split the text into lines
        let lines = enhancedText.split('\n');
        let formatted = '';
        let inList = false;
        let listType = '';
        
        // Process each line
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            
            // Skip processing if line is already an HTML header (from our replacement above)
            if (line.startsWith('<h4')) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                formatted += line;
                continue;
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
        
        // Close any open lists
        if (inList) {
            formatted += listType === 'ul' ? '</ul>' : '</ol>';
        }
        
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
        
        // Enhance the styling of the main sections
        formatted = formatted.replace(/<h4 class="mt-4 section-patient-info">/g, 
            '<h4 class="mt-4 section-patient-info" style="color: #6c5ce7; border-left: 4px solid #6c5ce7; padding: 8px 0 8px 15px; background-color: rgba(108, 92, 231, 0.05); border-radius: 0 5px 5px 0;">');
            
        formatted = formatted.replace(/<h5 class="mt-3 section-reports">/g, 
            '<h5 class="mt-3 section-reports" style="color: #6c5ce7; margin-left: 15px; border-left: 2px solid #6c5ce7; padding: 5px 0 5px 10px; background-color: rgba(108, 92, 231, 0.03); border-radius: 0 5px 5px 0;">');
            
        formatted = formatted.replace(/<h4 class="mt-4 section-diagnosis">/g, 
            '<h4 class="mt-4 section-diagnosis" style="color: #DE6262; border-left: 4px solid #DE6262; padding: 8px 0 8px 15px; background-color: rgba(222, 98, 98, 0.05); border-radius: 0 5px 5px 0;">');
            
        formatted = formatted.replace(/<h4 class="mt-4 section-recommendations">/g, 
            '<h4 class="mt-4 section-recommendations" style="color: #3498db; border-left: 4px solid #3498db; padding: 8px 0 8px 15px; background-color: rgba(52, 152, 219, 0.05); border-radius: 0 5px 5px 0;">');
            
        formatted = formatted.replace(/<h4 class="mt-4 section-treatment">/g, 
            '<h4 class="mt-4 section-treatment" style="color: #2ecc71; border-left: 4px solid #2ecc71; padding: 8px 0 8px 15px; background-color: rgba(46, 204, 113, 0.05); border-radius: 0 5px 5px 0;">');
            
        formatted = formatted.replace(/<h4 class="mt-4 section-warnings">/g, 
            '<h4 class="mt-4 section-warnings" style="color: #f39c12; border-left: 4px solid #f39c12; padding: 8px 0 8px 15px; background-color: rgba(243, 156, 18, 0.05); border-radius: 0 5px 5px 0;">');
        
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
                    
                    // Remove required attribute
                    nameInput.required = false;
                    ageInput.required = false;
                    
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
                    searchPlaceholderValue: 'Search...',
                    classNames: {
                        containerInner: 'form-control',
                    }
                });
                
                console.log('Choices.js initialized successfully');
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
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">File Upload Instructions</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <h6><i class="fas fa-info-circle text-primary me-2"></i>How to Add Multiple Files</h6>
                                        <p>You can add files in two ways:</p>
                                        <ul class="list-group list-group-flush mb-3">
                                            <li class="list-group-item">
                                                <strong>Method 1:</strong> Select multiple files at once
                                                <ul class="mt-2">
                                                    <li><strong>Windows:</strong> Hold <kbd>Ctrl</kbd> and click each file</li>
                                                    <li><strong>Mac:</strong> Hold <kbd>⌘ Command</kbd> and click each file</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Method 2:</strong> Add files incrementally
                                                <ul class="mt-2">
                                                    <li>Select one or more files</li>
                                                    <li>Click the <i class="fas fa-plus"></i> button to add more files</li>
                                                    <li>Repeat as needed to add different file types</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6><i class="fas fa-file-medical text-danger me-2"></i>Supported File Types</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item"><strong>Images:</strong> JPG, JPEG, PNG, GIF, BMP, WEBP</li>
                                            <li class="list-group-item"><strong>Documents:</strong> PDF, DOCX, DOC, TXT, RTF</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="alert alert-info">
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
                        document.getElementById('page-loader').style.display = 'flex';
                        
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
</script>
    @endsection